<?php
namespace Omnipay\Buckaroo\Message;

use DateTime;
use DateInterval;
use Omnipay\Common\Exception\RuntimeException;
use Omnipay\Common\Message\ResponseInterface;
use Throwable;

class DataRequest extends AbstractRequest
{
    public function setRedirectCallable(callable $redirectCallable)
    {
        $this->setParameter('redirectCallable', $redirectCallable);

        return $this;
    }

    public function getRedirectCallable(): callable
    {
        return $this->getParameter('redirectCallable');
    }

    /**
     * @return array|null
     */
    public function getCustomerData(): ?array
    {
        return $this->getParameter('customerData');
    }

    /**
     * @param array|null $customerData
     *
     * @return DataRequest
     */
    public function setCustomerdata(?array $customerData): DataRequest
    {
        $this->setParameter('customerData', $customerData);

        return $this;
    }

    /**
     * @return array|null
     */
    public function getOrderLines(): ?array
    {
        return $this->getParameter('orderLines');
    }

    /**
     * @param array|null $orderLines
     *
     * @return DataRequest
     */
    public function setOrderLines(?array $orderLines): DataRequest
    {
        $this->setParameter('orderLines', $orderLines);

        return $this;
    }

    /**
     * @return string
     */
    public function getOperatingCountry(): string
    {
        return $this->getParameter('operatingCountry') ?? 'NL';
    }

    /**
     * @param string $operatingCountry
     *
     * @return DataRequest
     */
    public function setOperatingCountry(string $operatingCountry): DataRequest
    {
        $this->setParameter('operatingCountry', $operatingCountry);

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->getParameter('locale') ?? 'nl-NL';
    }

    /**
     * @param string $locale
     *
     * @return DataRequest
     */
    public function setLocale(string $locale): DataRequest
    {
        $this->setParameter('locale', $locale);

        return $this;
    }

    /**
     * @return bool
     */
    public function getUpdateReservation(): bool
    {
        return (bool)$this->getParameter('updateReservation');
    }

    /**
     * @param bool $updateReservation
     *
     * @return $this
     */
    public function setUpdateReservation(bool $updateReservation): DataRequest
    {
        $this->setParameter('updateReservation', $updateReservation);

        return $this;
    }

    /**
     * @return string
     */
    public function getReservationNumber(): string
    {
        return $this->getParameter('reservationNumber') ?? '';
    }

    /**
     * @param string $reservatioNNumber
     *
     * @return $this
     */
    public function setReservationNumber(?string $reservatioNNumber): DataRequest
    {
        $this->setParameter('reservationNumber', $reservatioNNumber);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function getData(): array
    {
        $data = parent::getData();

        $this->validate('paymentMethod', 'amount', 'returnUrl', 'clientIp');
        $services = $this->getServices();
        $data = array_merge($data, $services);

        $data['ClientIP'] = [
            // 0 = IPV4
            // 1 = IPV6
            'Type' => (int)filter_var($this->getClientIp(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6),
            'Address' => $this->getClientIp(),
        ];
        $data['Currency'] = $this->getCurrency();
        $data['AmountDebit'] = $this->getAmount();
        $data['Invoice'] = $this->getTransactionId();
        $data['ReturnUrl'] = $this->getReturnUrl();
        $data['ReturnURLCancel'] = $this->getCancelUrl();
        $data['ReturnURLError'] = $this->getCancelUrl();
        $data['ReturnURLReject'] = $this->getRejectUrl();
        $data['PushUrl'] = $this->getNotifyUrl();
        $data['PushURLFailure'] = $this->getNotifyUrl();
        $data['redirectCallable'] = $this->getRedirectCallable();
        $data['updateReservation'] = $this->getUpdateReservation();

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function sendData($data): ResponseInterface
    {
        ksort($data);
        $jsonData = json_encode($data);

        $endpoint = $this->getEndpoint('/DataRequest');

        $headers = [
            'Authorization' => 'hmac ' . $this->generateAuthorizationToken($jsonData, $endpoint),
            'Content-Type' => 'application/json',
            'Culture' => $this->getLocale(),
        ];

        try {
            $response = $this->httpClient->request(
                'POST',
                $endpoint,
                $headers,
                $jsonData
            );

            $respData = json_decode((string) $response->getBody(), true);
        } catch (Throwable $t) {
            throw new RuntimeException('Could not send the request', 0, $t);
        }

        $dataResponse = new DataResponse($this, $respData);
        $dataResponse->setCallableFunctionRedirect($this->getRedirectCallable());

        return $dataResponse;
    }

    private function getServices()
    {
        $data = [];

        if($this->getIssuer() == 'idealqr'){
            $expirationDate  = new DateTime();
            $expirationDate->add(new DateInterval("P21D"));
            $data['Services'] = [
                'ServiceList' => [
                    [
                        'Name' => $this->getIssuer(),
                        'Action' => 'Generate',
                        'Parameters' => [
                            [
                                'Name' => 'Description',
                                'Value' => 'Betaling Sanitairwinkel',
                            ],
                            [
                                'Name' => 'PurchaseId',
                                'Value' => $this->getTransactionId(),
                            ],
                            [
                                'Name' => 'IsOneOff',
                                'Value' => 'false',
                            ],
                            [
                                'Name' => 'Amount',
                                'Value' => $this->getAmount(),
                            ],
                            [
                                'Name' => 'ImageSize',
                                'Value' => 2000,
                            ],
                            [
                                'Name' => 'AmountIsChangeable',
                                'Value' => 'false',
                            ],
                            [
                                'Name' => 'Expiration',
                                'Value' => $expirationDate->format('Y-m-d'),
                            ],
                        ],
                    ],
                ],
            ];
        } elseif ($this->getPaymentMethod() == 'klarnakp') {
            $customerData = $this->getCustomerData();
            $shippingSameAsBilling = $customerData['billingAddress'] == $customerData['shippingAddress'];
            $data['Services'] = [
                'ServiceList' => [
                    [
                        'Name' => $this->getPaymentMethod(),
                        'Action' => $this->getUpdateReservation() ? 'UpdateReservation' : 'Reserve',
                        'Parameters' => [
                            [
                                'Name' => 'BillingFirstName',
                                'Value' => $customerData['firstName'],
                            ],
                            [
                                'Name' => 'BillingLastName',
                                'Value' => $customerData['lastName'],
                            ],
                            [
                                'Name' => 'BillingStreet',
                                'Value' => $customerData['billingAddress']['street'],
                            ],
                            [
                                'Name' => 'BillingHouseNumber',
                                'Value' => $customerData['billingAddress']['houseNumber'],
                            ],
                            [
                                'Name' => 'BillingHouseNumberSuffix',
                                'Value' => $customerData['billingAddress']['houseNumberExtension'],
                            ],
                            [
                                'Name' => 'BillingPostalCode',
                                'Value' => $customerData['billingAddress']['postalCode'],
                            ],
                            [
                                'Name' => 'BillingCity',
                                'Value' => $customerData['billingAddress']['city'],
                            ],
                            [
                                'Name' => 'BillingCountry',
                                'Value' => $customerData['billingAddress']['country'],
                            ],
                            [
                                'Name' => 'BillingCellPhoneNumber',
                                'Value' => $customerData['billingAddress']['phoneNumber'],
                            ],
                            [
                                'Name' => 'BillingEmail',
                                'Value' => $customerData['billingAddress']['email'],
                            ],
                            [
                                'Name' => 'ShippingFirstName',
                                'Value' => $customerData['firstName'],
                            ],
                            [
                                'Name' => 'ShippingLastName',
                                'Value' => $customerData['lastName'],
                            ],
                            [
                                'Name' => 'ShippingStreet',
                                'Value' => $customerData['shippingAddress']['street'],
                            ],
                            [
                                'Name' => 'ShippingHouseNumber',
                                'Value' => $customerData['shippingAddress']['houseNumber'],
                            ],
                            [
                                'Name' => 'ShippingHouseNumberSuffix',
                                'Value' => $customerData['shippingAddress']['houseNumberExtension'],
                            ],
                            [
                                'Name' => 'ShippingPostalCode',
                                'Value' => $customerData['shippingAddress']['postalCode'],
                            ],
                            [
                                'Name' => 'ShippingCity',
                                'Value' => $customerData['shippingAddress']['city'],
                            ],
                            [
                                'Name' => 'ShippingCountry',
                                'Value' => $customerData['shippingAddress']['country'],
                            ],
                            [
                                'Name' => 'ShippingPhoneNumber',
                                'Value' => $customerData['shippingAddress']['houseNumber'],
                            ],
                            [
                                'Name' => 'ShippingEmail',
                                'Value' => $customerData['shippingAddress']['email'],
                            ],
                            [
                                'Name' => 'Gender',
                                'Value' => (string)$customerData['gender'],
                            ],
                            [
                                'Name' => 'OperatingCountry',
                                'Value' => $this->getOperatingCountry(),
                            ],
                            [
                                'Name' => 'Pno',
                                'Value' => $customerData['dateOfBirth'] ? $customerData['dateOfBirth']->format('dmY') : "",
                            ],
                            [
                                'Name' => 'ShippingSameAsBilling',
                                'Value' => $shippingSameAsBilling ? 'true' : 'false',
                            ],
                        ],
                    ],
                ],
            ];

            if ($this->getUpdateReservation()) {
                $reservationNumber = [
                    [
                        'Name' => 'ReservationNumber',
                        'Value' => $this->getReservationNumber(),
                    ],
                ];
                $data['Services']['ServiceList'][0]['Parameters'] = array_merge($data['Services']['ServiceList'][0]['Parameters'], $reservationNumber);
            }

            foreach ($this->getOrderLines() as $id => $orderLine) {
                $orderLineData = [
                    [
                        'Name' => 'ArticleNumber',
                        'GroupType' => 'Article',
                        'GroupId' => (string)$id,
                        'Value' => $orderLine['ArticleNumber'],
                    ],
                    [
                        'Name' => 'ArticlePrice',
                        'GroupType' => 'Article',
                        'GroupId' => (string)$id,
                        'Value' => $orderLine['ArticlePrice'],
                    ],
                    [
                        'Name' => 'ArticleQuantity',
                        'GroupType' => 'Article',
                        'GroupId' => (string)$id,
                        'Value' => $orderLine['Quantity'],
                    ],
                    [
                        'Name' => 'ArticleTitle',
                        'GroupType' => 'Article',
                        'GroupId' => (string)$id,
                        'Value' => mb_substr($orderLine['ArticleTitle'], 0, 100),
                    ],
                    [
                        'Name' => 'ArticleVat',
                        'GroupType' => 'Article',
                        'GroupId' => (string)$id,
                        'Value' => $orderLine['ArticleVat'],
                    ],
                    [
                        'Name' => 'ArticleType',
                        'GroupType' => 'Article',
                        'GroupId' => (string)$id,
                        'Value' => $orderLine['ArticleType'],
                    ],
                ];
                $data['Services']['ServiceList'][0]['Parameters'] = array_merge($data['Services']['ServiceList'][0]['Parameters'], $orderLineData);
            }
        }
        return $data;
    }
}
