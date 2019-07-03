<?php
declare(strict_types=1);

namespace Omnipay\Buckaroo\Message;

use Omnipay\Common\Exception\RuntimeException;
use Omnipay\Common\Message\ResponseInterface;
use Throwable;

class PurchaseRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     *
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function getData(): array
    {
        $data = parent::getData();

        $this->validate('paymentMethod', 'amount', 'returnUrl', 'clientIp');

        switch ($this->getPaymentMethod()) {
            case 'ideal':
                $this->validate('issuer');

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
                break;
                // TODO: Add other payment methods
        }

        $data['ClientIP'] = [
            // 0 = IPV4
            // 1 = IPV6
            'Type' => (int)filter_var($this->getClientIp(), FILTER_FLAG_IPV6),
            'Address' => $this->getClientIp(),
        ];
        $data['Currency'] = $this->getCurrency();
        $data['AmountDebit'] = $this->getAmount();
        $data['Invoice'] = $this->getTransactionId();

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
}
