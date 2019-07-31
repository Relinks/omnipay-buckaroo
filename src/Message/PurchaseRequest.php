<?php
declare(strict_types=1);

namespace Omnipay\Buckaroo\Message;

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
        $data['ReturnUrl'] = $this->getReturnUrl();
        $data['ReturnURLCancel'] = $this->getCancelUrl();
        $data['ReturnURLError'] = $this->getCancelUrl();
        $data['ReturnURLReject'] = $this->getCancelUrl();
        $data['PushUrl'] = $this->getNotifyUrl();

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
                ],
                $jsonData
            );

            $respData = json_decode((string) $response->getBody(), true);
        } catch (Throwable $t) {
            throw new RuntimeException('Could not send the request', 0, $t);
        }

        return new PurchaseResponse($this, $respData);
    }

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
                    $data['ServicesSelectableByClient'] = 'ideal';
                    $data['ContinueOnIncomplete'] = 1;
                    $data['Services'] = [
                        'ServiceList' => [
                            [],
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
                    $data['ServicesSelectableByClient'] = 'visa, mastercard';
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
            case 'mistercash':
                $data['ServicesSelectableByClient'] = 'bancontactmrcash';
                $data['ContinueOnIncomplete'] = 1;
                $data['Services'] = [
                    'ServiceList' => [
                        [],
                    ],
                ];
        }

        return $data;
    }
}
