<?php

declare(strict_types=1);

namespace Omnipay\Buckaroo\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Exception\RuntimeException;
use Omnipay\Common\Message\ResponseInterface;
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
    public function getCustomerData(): ?array
    {
        return $this->getParameter('customerData');
    }

    /**
     * @param array|null $customerData
     *
     * @return PurchaseRequest
     */
    public function setCustomerdata(?array $customerData): PurchaseRequest
    {
        $this->setParameter('customerData', $customerData);

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
     * @return string|null
     */
    public function getAvailablePaymentMethods(): ?string
    {
        return $this->getParameter('availablePaymentMethods');
    }

    /**
     * @param string|null $availablePaymentMethods
     *
     * @return PurchaseRequest
     */
    public function setAvailablePaymentMethods(?string $availablePaymentMethods): PurchaseRequest
    {
        $this->setParameter('availablePaymentMethods', $availablePaymentMethods);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryMethod(): ?string
    {
        return $this->getParameter('deliveryMethod');
    }

    /**
     * @param string|null $deliveryMethod
     *
     * @return $this
     */
    public function setDeliveryMethod(?string $deliveryMethod): PurchaseRequest
    {
        $this->setParameter('deliveryMethod', $deliveryMethod);

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
     * @return $this
     */
    public function setOrderLines(?array $orderLines): PurchaseRequest
    {
        $this->setParameter('orderLines', $orderLines);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getReservationNumber(): ?string
    {
        return $this->getParameter('reservationNumber');
    }

    /**
     * @param string|null $reservationNumber
     *
     * @return $this
     */
    public function setReservationNumber(?string $reservationNumber): PurchaseRequest
    {
        $this->setParameter('reservationNumber', $reservationNumber);

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

        if ($this->isPayPerMail()) {
            $services = $this->getPayperMailServices();
        } else {
            $services = $this->getServices($this->getPaymentMethod());
        }

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
        $data['Description'] = $this->getDescription();
        $data['ReturnUrl'] = $this->getReturnUrl();
        $data['ReturnURLCancel'] = $this->getCancelUrl();
        $data['ReturnURLError'] = $this->getCancelUrl();
        $data['ReturnURLReject'] = $this->getRejectUrl();
        $data['PushUrl'] = $this->getNotifyUrl();
        $data['redirectCallable'] = $this->getRedirectCallable();
        $data['availablePaymentMethods'] = $this->getAvailablePaymentMethods();

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

    public function getPayperMailServices()
    {
        $data = [];
        $customerData = $this->getParameter('customerData');

        $data['Services'] = [
            'ServiceList' => [
                [
                    'Name' => 'payperemail',
                    'Action' => 'PaymentInvitation',
                    'Parameters' => [
                        [
                            'Name' => 'customergender',
                            'Value' => '1',
                        ],
                        [
                            'Name' => 'MerchantSendsEmail',
                            'Value' => 'false',
                        ],
                        [
                            'Name' => 'ExpirationDate',
                            'Value' => $customerData['dueDate']->format('Y-m-d'),
                        ],
                        [
                            'Name' => 'PaymentMethodsAllowed',
                            'Value' => $this->getAvailablePaymentMethods(),
                        ],
                        [
                            'Name' => 'Attachment',
                            'Value' => '',
                        ],
                        [
                            'Name' => 'CustomerEmail',
                            'Value' => $customerData['email'],
                        ],
                        [
                            'Name' => 'CustomerFirstName',
                            'Value' => $customerData['firstName'],
                        ],
                        [
                            'Name' => 'CustomerLastName',
                            'Value' => $customerData['lastName'],
                        ],
                    ],
                ],
            ],
        ];

        return $data;
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
                    $selectableServices = $this->getSiteId() == 7
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
                    $transferCustomerData = $this->getParameter('customerData');
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
                break;
            case 'Tinka':
                $customerData = $this->getCustomerData();
                $data['Services'] = [
                    'ServiceList' => [
                        [
                            'Name' => $this->getPaymentMethod(),
                            'Action' => 'Pay',
                            'Parameters' => [
                                [
                                    'Name' => 'PaymentMethod',
                                    'Value' => 'Credit',
                                ],
                                [
                                    'Name' => 'DeliveryMethod',
                                    'Value' => $this->getDeliveryMethod(),
                                ],
                                [
                                    'Name' => 'LastName',
                                    'Value' => $customerData['lastName'],
                                ],
                                [
                                    'Name' => 'Gender',
                                    'Value' => (string) $customerData['gender'],
                                ],
                                [
                                    'Name' => 'Email',
                                    'GroupType' => 'BillingCustomer',
                                    'Value' => $customerData['email'],
                                ],
                                [
                                    'Name' => 'Street',
                                    'GroupType' => 'BillingCustomer',
                                    'Value' => $customerData['billingAddress']['street'],
                                ],
                                [
                                    'Name' => 'StreetNumber',
                                    'GroupType' => 'BillingCustomer',
                                    'Value' => $customerData['billingAddress']['houseNumber'],
                                ],
                                [
                                    'Name' => 'StreetNumberAdditional',
                                    'GroupType' => 'BillingCustomer',
                                    'Value' => $customerData['billingAddress']['houseNumberExtension'],
                                ],
                                [
                                    'Name' => 'PostalCode',
                                    'GroupType' => 'BillingCustomer',
                                    'Value' => $customerData['billingAddress']['postalCode'],
                                ],
                                [
                                    'Name' => 'City',
                                    'GroupType' => 'BillingCustomer',
                                    'Value' => $customerData['billingAddress']['city'],
                                ],
                                [
                                    'Name' => 'Email',
                                    'GroupType' => 'ShippingCustomer',
                                    'Value' => $customerData['email'],
                                ],
                                [
                                    'Name' => 'Street',
                                    'GroupType' => 'ShippingCustomer',
                                    'Value' => $customerData['shippingAddress']['street'],
                                ],
                                [
                                    'Name' => 'StreetNumber',
                                    'GroupType' => 'ShippingCustomer',
                                    'Value' => $customerData['shippingAddress']['houseNumber'],
                                ],
                                [
                                    'Name' => 'StreetNumberAdditional',
                                    'GroupType' => 'ShippingCustomer',
                                    'Value' => $customerData['shippingAddress']['houseNumberExtension'],
                                ],
                                [
                                    'Name' => 'PostalCode',
                                    'GroupType' => 'ShippingCustomer',
                                    'Value' => $customerData['shippingAddress']['postalCode'],
                                ],
                                [
                                    'Name' => 'City',
                                    'GroupType' => 'ShippingCustomer',
                                    'Value' => $customerData['shippingAddress']['city'],
                                ],
                            ],
                        ],
                    ],
                ];
                foreach ($this->getOrderLines() as $id => $orderLine) {
                    $orderLineData = [
                        [
                            'Name' => 'UnitCode',
                            'GroupType' => 'Article',
                            'GroupId' => (string) $id,
                            'Value' => $orderLine['UnitCode'],
                        ],
                        [
                            'Name' => 'UnitGrossPrice',
                            'GroupType' => 'Article',
                            'GroupId' => (string) $id,
                            'Value' => $orderLine['UnitGrossPrice'],
                        ],
                        [
                            'Name' => 'Quantity',
                            'GroupType' => 'Article',
                            'GroupId' => (string) $id,
                            'Value' => $orderLine['Quantity'],
                        ],
                        [
                            'Name' => 'Description',
                            'GroupType' => 'Article',
                            'GroupId' => (string) $id,
                            'Value' => mb_substr($orderLine['Description'], 0, 100),
                        ],
                    ];
                    $data['Services']['ServiceList'][0]['Parameters'] = array_merge($data['Services']['ServiceList'][0]['Parameters'], $orderLineData);
                }
                break;
            case 'klarnakp':
                $data['Services'] = [
                    'ServiceList' => [
                        [
                            'Name' => $this->getPaymentMethod(),
                            'Action' => 'Pay',
                            'Parameters' => [
                                [
                                    'Name' => 'ReservationNumber',
                                    'Value' => $this->getReservationNumber(),
                                ],
                            ],
                        ],
                    ],
                ];
                break;
            case 'giropay':
                $data['Services'] = [
                    'ServiceList' => [
                        [
                            'Name' => $this->getPaymentMethod(),
                            'Action' => 'Pay',
                            'Parameters' => [],
                        ],
                    ],
                ];
                break;
            case 'Sofortueberweisung':
                $data['Services'] = [
                    'ServiceList' => [
                        [
                            'Name' => 'Sofortueberweisung',
                            'Action' => 'Pay',
                        ],
                    ],
                ];
                break;
            case 'idealin3':
                $customerData = $this->getCustomerData();
                $data['Services'] = [
                    'ServiceList' => [
                        [
                            'Name' => $this->getPaymentMethod(),
                            'Action' => 'Pay',
                            'Parameters' => [
//                                [// Optional
//                                    "Name" => "InvoiceDate",
//                                    "GroupType" => "",
//                                    "GroupID" => "",
//                                    "Value" => "2023-12-01"
//                                ],
//                                [// Optional
//                                    "Name" => "InvoiceUrl",
//                                    "GroupType" => "",
//                                    "GroupID" => "",
//                                    "Value" => "https://www.buckaroo.nl"
//                                ],
                            ],
                        ],
                    ],
                ];
                foreach ($this->getOrderLines() as $id => $orderLine) {
                    $orderLineData = [
                        [// Optional
                            "Name" => "Identifier",
                            'GroupType' => 'Article',
                            'GroupId' => (string) $id,
                            'Value' => $orderLine['ArticleNumber'],
                        ],
//                        [// Optional
//                            "Name" => "Type",
//                            "GroupType" => "Article",
//                            "GroupID" => (string) $id,
//                            "Value" => "Physical"
//                        ],
                        [// Mandatory
                            "Name" => "Description",
                            "GroupType" => "Article",
                            "GroupID" => (string) $id,
                            "Value" => mb_substr($orderLine['ArticleDescription'], 0, 100),
                        ],
//                        [// Optional
//                            "Name" => "Category",
//                            "GroupType" => "Article",
//                            "GroupID" => (string) $id,
//                            "Value" => "Toy Car"
//                        ],
                        [// Mandatory
                            "Name" => "Quantity",
                            "GroupType" => "Article",
                            "GroupID" => (string) $id,
                            "Value" => $orderLine['Quantity']
                        ],
                        [// Mandatory
                            "Name" => "GrossUnitPrice",
                            "GroupType" => "Article",
                            "GroupID" => (string) $id,
                            "Value" => $orderLine['ArticlePrice']
                        ],
//                        [// Optional
//                            "Name" => "QuantityDescription",
//                            "GroupType" => "Article",
//                            "GroupID" => (string) $id,
//                            "Value" => "Pieces"
//                        ],
//                        [// Optional
//                            "Name" => "VatPercentage",
//                            "GroupType" => "Article",
//                            "GroupID" => (string) $id,
//                            "Value" => "21"
//                        ],
//                        [// Optional
//                            "Name" => "VatAmount",
//                            "GroupType" => "Article",
//                            "GroupID" => (string) $id,
//                            "Value" => "0.01"
//                        ],
//                        [// Optional
//                            "Name" => "DeliveryDate",
//                            "GroupType" => "Article",
//                            "GroupID" => (string) $id,
//                            "Value" => "2023-11-11"
//                        ],
                        [// Mandatory
                            "Name" => "CustomerNumber",
                            "GroupType" => "BillingCustomer",
                            "GroupID" => (string) $id,
                            "Value" => $customerData['id']
                        ],
//                        [// Optional
//                            "Name" => "FirstName",
//                            "GroupType" => "BillingCustomer",
//                            "GroupID" => (string) $id,
//                            "Value" => "John"
//                        ],
                        [// Mandatory
                            "Name" => "LastName",
                            "GroupType" => "BillingCustomer",
                            "GroupID" => (string) $id,
                            "Value" => $customerData['lastName'],
                        ],
//                        [// Optional
//                            "Name" => "Initials",
//                            "GroupType" => "BillingCustomer",
//                            "GroupID" => (string) $id,
//                            "Value" => "JD"
//                        ],
//                        [// Optional
//                            "Name" => "BirthDate",
//                            "GroupType" => "BillingCustomer",
//                            "GroupID" => (string) $id,
//                            "Value" => "1990-01-01"
//                        ],
                        [// Mandatory
                            "Name" => "Phone",
                            "GroupType" => "BillingCustomer",
                            "GroupID" => (string) $id,
                            "Value" => $customerData['billingAddress']['phoneNumber']
                        ],
                        [// Mandatory
                            "Name" => "Email",
                            "GroupType" => "BillingCustomer",
                            "GroupID" => (string) $id,
                            "Value" => $customerData['billingAddress']['email']
                        ],
//                        [// Optional
//                            "Name" => "Fax",
//                            "GroupType" => "BillingCustomer",
//                            "GroupID" => (string) $id,
//                            "Value" => "0301234567"
//                        ],
                        [// Mandatory
                            "Name" => "Category",
                            "GroupType" => "BillingCustomer",
                            "GroupID" => (string) $id,
                            "Value" => $customerData['type']
                        ],
//                        [// Optional
//                            "Name" => "CareOf",
//                            "GroupType" => "BillingCustomer",
//                            "GroupID" => (string) $id,
//                            "Value" => "John Doe"
//                        ],
                        [// Mandatory
                            "Name" => "Street",
                            "GroupType" => "BillingCustomer",
                            "GroupID" => (string) $id,
                            "Value" => $customerData['billingAddress']['street']
                        ],
                        [// Mandatory
                            "Name" => "StreetNumber",
                            "GroupType" => "BillingCustomer",
                            "GroupID" => (string) $id,
                            "Value" => $customerData['billingAddress']['houseNumber']
                        ],
                        [// Optional
                            "Name" => "StreetNumberSuffix",
                            "GroupType" => "BillingCustomer",
                            "GroupID" => (string) $id,
                            "Value" => $customerData['billingAddress']['houseNumberExtension']
                        ],
                        [// Mandatory
                            "Name" => "PostalCode",
                            "GroupType" => "BillingCustomer",
                            "GroupID" => (string) $id,
                            "Value" => $customerData['billingAddress']['postalCode']
                        ],
                        [// Mandatory
                            "Name" => "City",
                            "GroupType" => "BillingCustomer",
                            "GroupID" => (string) $id,
                            "Value" => $customerData['billingAddress']['city']
                        ],
//                        [// Optional
//                            "Name" => "Region",
//                            "GroupType" => "BillingCustomer",
//                            "GroupID" => (string) $id,
//                            "Value" => "Utrecht"
//                        ],
                        [// Mandatory
                            "Name" => "CountryCode",
                            "GroupType" => "BillingCustomer",
                            "GroupID" => (string) $id,
                            "Value" => $customerData['billingAddress']['country']
                        ],
//                        [// Optional
//                            "Name" => "CareOf",
//                            "GroupType" => "ShippingCustomer",
//                            "GroupID" => (string) $id,
//                            "Value" => "Jane Doe"
//                        ],
                        [// Mandatory
                            "Name" => "Street",
                            "GroupType" => "ShippingCustomer",
                            "GroupID" => (string) $id,
                            "Value" => $customerData['shippingAddress']['street']
                        ],
                        [// Mandatory
                            "Name" => "StreetNumber",
                            "GroupType" => "ShippingCustomer",
                            "GroupID" => (string) $id,
                            "Value" => $customerData['shippingAddress']['houseNumber']
                        ],
                        [// Optional
                            "Name" => "StreetNumberSuffix",
                            "GroupType" => "ShippingCustomer",
                            "GroupID" => (string) $id,
                            "Value" => $customerData['shippingAddress']['houseNumberExtension']
                        ],
                        [// Mandatory
                            "Name" => "City",
                            "GroupType" => "ShippingCustomer",
                            "GroupID" => (string) $id,
                            "Value" => $customerData['shippingAddress']['city']
                        ],
                        [// Mandatory
                            "Name" => "PostalCode",
                            "GroupType" => "ShippingCustomer",
                            "GroupID" => (string) $id,
                            "Value" => $customerData['shippingAddress']['postalCode']
                        ],
//                        [// Optional
//                            "Name" => "Region",
//                            "GroupType" => "ShippingCustomer",
//                            "GroupID" => (string) $id,
//                            "Value" => "Utrecht"
//                        ],
                        [// Mandatory
                            "Name" => "CountryCode",
                            "GroupType" => "ShippingCustomer",
                            "GroupID" => (string) $id,
                            "Value" => $customerData['shippingAddress']['country']
                        ],
//                        [// Optional
//                            "Name" => "CompanyName",
//                            "GroupType" => "BillingCustomer",
//                            "GroupID" => (string) $id,
//                            "Value" => "none"
//                        ],
//                        [// Optional
//                            "Name" => "CocNumber", // Chain of Custody
//                            "GroupType" => "BillingCustomer",
//                            "GroupID" => (string) $id,
//                            "Value" => "none"
//                        ]
                    ];
                    $data['Services']['ServiceList'][0]['Parameters'] = array_merge($data['Services']['ServiceList'][0]['Parameters'], $orderLineData);
                }
                break;
        }

        return $data;
    }
}
