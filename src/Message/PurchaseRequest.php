<?php
declare(strict_types=1);

namespace Omnipay\Buckaroo\Message;

use Omnipay\Common\Exception\RuntimeException;
use Omnipay\Common\Message\ResponseInterface;
use Rorix\Core\Site\Model\Site;
use Throwable;

class PurchaseRequest extends AbstractRequest
{
    /**
     * @return string|null
     */
    public function getEncryptedKey(): ?string
    {
        return $this->getParameter('encryptedKey');
    }

    /**
     * @param string|null $encryptedKey
     *
     * @return PurchaseRequest
     */
    public function setEncryptedKey(?string $encryptedKey): PurchaseRequest
    {
        $this->setParameter('encryptedKey', $encryptedKey);

        return $this;
    }

    /**
     * @return array|null
     */
    public function getTranfserCustomerData(): ?array
    {
        return $this->getParameter('transferCustomerData');
    }

    /**
     * @param array|null $encryptedKey
     *
     * @return PurchaseRequest
     */
    public function setTransferCustomerdata(?array $transferCustomerData): PurchaseRequest
    {
        $this->setParameter('transferCustomerData', $transferCustomerData);

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSiteId(): ?int
    {
        return $this->getParameter('siteId');
    }

    /**
     * @param int|null $siteId
     *
     * @return PurchaseRequest
     */
    public function setSiteId(?int $siteId): PurchaseRequest
    {
        $this->setParameter('siteId', $siteId);

        return $this;
    }

    public function setRedirectCallable(callable $redirectCallable)
    {
        $this->setParameter('redirectCallable', $redirectCallable);

        return $this;
    }

    public function getRedirectCallable(): ?callable
    {
        return $this->getParameter('redirectCallable');
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

        $services = $this->getServices($this->getPaymentMethod());

        $data = array_merge($data, $services);

        $data['ClientIP'] = [
            // 0 = IPV4
            // 1 = IPV6
            'Type' => (int)filter_var($this->getClientIp(), FILTER_FLAG_IPV6),
            'Address' => $this->getClientIp(),
        ];
        $data['Currency'] = $this->getCurrency();
        $data['AmountDebit'] = $this->getAmount();
        $data['Invoice'] = $this->getTransactionId();
        $data['Description'] = $this->getDescription();
        $data['ReturnUrl'] = $this->getReturnUrl();
        $data['ReturnURLCancel'] = $this->getCancelUrl();
        $data['ReturnURLError'] = $this->getCancelUrl();
        $data['ReturnURLReject'] = $this->getRejectUrl();
        $data['PushUrl'] = $this->getNotifyUrl();
        $data['redirectCallable'] = $this->getRedirectCallable();


        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function sendData($data): ResponseInterface
    {
        ksort($data);
        $jsonData = json_encode($data);

        $endpoint = $this->getEndpoint('/Transaction');

        try {
            $response = $this->httpClient->request(
                'POST',
                $endpoint,
                [
                    'Authorization' => 'hmac ' . $this->generateAuthorizationToken($jsonData, $endpoint),
                    'Content-Type' => 'application/json',
                    'Culture' => $this->getCulture(),
                ],
                $jsonData
            );

            $respData = json_decode((string) $response->getBody(), true);
        } catch (Throwable $t) {
            throw new RuntimeException('Could not send the request', 0, $t);
        }

        $purchaseResponse =  new PurchaseResponse($this, $respData);
        $purchaseResponse->setCallableFunctionRedirect($this->getRedirectCallable());

        return $purchaseResponse;
    }

    /**
     * @SuppressWarnings(CyclomaticComplexity)
     * @SuppressWarnings(ExcessiveMethodLength)
     *
     * @param string $paymentMethod
     *
     * @return array
     */
    private function getServices(string $paymentMethod): array
    {
        $data = [];

        switch ($paymentMethod) {
            case 'ideal':
                if ($this->getIssuer()) {
                    $data['Services'] = [
                        'ServiceList' => [
                            [
                                'Name' => $this->getPaymentMethod(),
                                'Action' => 'Pay',
                                'Parameters' => [
                                    [
                                        'Name' => 'issuer',
                                        'Value' => $this->getParameter('issuer'),
                                    ],
                                ],
                            ],
                        ],
                    ];
                } else {
                    $data['ContinueOnIncomplete'] = 1;
                    $data['Services'] = [
                        'ServiceList' => [
                            [
                                'Name' => $this->getPaymentMethod(),
                                'Action' => 'Pay',
                            ],
                        ],
                    ];
                }
                break;
            case 'creditcard':
                if ($this->getIssuer() && $this->getEncryptedKey()) {
                    $data['Services'] = [
                        'ServiceList' => [
                            [
                                'Name' => $this->getParameter('issuer'),
                                'Action' => 'PayEncrypted',
                                "Version" => 0,
                                'Parameters' => [
                                    [
                                        'Name' => 'EncryptedCardData',
                                        "GroupType" => '',
                                        "GroupID" => '',
                                        'Value' => $this->getParameter('encryptedKey'),
                                    ],
                                ],
                            ],
                        ],
                    ];
                } else {
                    //MSFR has cartebleuevisa and cartebancaire as an extra option
                    $selectableServices = $this->getSiteId() == Site::MSFR
                        ? 'visa, mastercard, maestro, cartebleuevisa, cartebancaire' : 'visa, mastercard';

                    $data['ServicesSelectableByClient'] = $selectableServices;
                    $data['ContinueOnIncomplete'] = 1;
                    $data['Services'] = [
                        'ServiceList' => [
                            [],
                        ],
                    ];
                }
                break;
            case 'paypal':
                $data['Services'] = [
                    'ServiceList' => [
                        [
                            'Name' => $this->getPaymentMethod(),
                            'Action' => 'Pay',
                        ],
                    ],
                ];
                break;
            case 'bancontactmrcash':
                if ($this->getEncryptedKey()) {
                    $data['Services'] = [
                        'ServiceList' => [
                            [
                                'Name' => $this->getPaymentMethod(),
                                'Action' => 'PayEncrypted',
                                "Version" => 0,
                                'Parameters' => [
                                    [
                                        'Name' => 'EncryptedCardData',
                                        "GroupType" => '',
                                        "GroupID" => '',
                                        'Value' => $this->getParameter('encryptedKey'),
                                    ],
                                ],
                            ],
                        ],
                    ];
                } else {
                    $data['Services'] = [
                        'ServiceList' => [
                            [
                                'Name' => $this->getPaymentMethod(),
                                'Action' => 'Pay',
                            ],
                        ],
                    ];
                }
                break;
            case 'transfer':
                try {
                    $transferCustomerData = $this->getParameter('transferCustomerData');
                    $data['Services'] = [
                        'ServiceList' => [
                            [
                                'Name' => $this->getPaymentMethod(),
                                'Action' => 'Pay',
                                'Parameters' => [
                                    [
                                        'Name' => 'CustomerFirstName',
                                        'Value' => $transferCustomerData['firstName'],
                                    ],
                                    [
                                        'Name' => 'CustomerLastName',
                                        'Value' => $transferCustomerData['lastName'],
                                    ],
                                    [
                                        'Name' => 'CustomerGender',
                                        'Value' => $transferCustomerData['gender'],
                                    ],
                                    [
                                        'Name' => 'CustomerCountry',
                                        'Value' => $transferCustomerData['country'],
                                    ],
                                    [
                                        'Name' => 'SendMail',
                                        'Value' => $transferCustomerData['sendMail'],
                                    ],
                                    [
                                        'Name' => 'CustomerEmail',
                                        'Value' => $transferCustomerData['email'],
                                    ],
                                    [
                                        'Name' => 'DateDue',
                                        'Value' => $transferCustomerData['dueDate']->format('Y-m-d'),
                                    ],
                                ],
                            ],
                        ],
                    ];
                } catch (Throwable $t) {
                    throw new InvalidRequestException('Incomplete billing address');
                }
        }

        return $data;
    }
}
