<?php
namespace Omnipay\Buckaroo\Message;

use DateTime;
use DateInterval;
use Omnipay\Common\Exception\RuntimeException;
use Throwable;

class DataRequest extends AbstractRequest
{
    public function setRedirectCallable(callable $redirectCallable)
    {
        $this->setParameter('redirectCallable', $redirectCallable);

        return $this;
    }

    public function getRedirectCallable():callable
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

        $services = $this->getServices();

        $data = array_merge($data, $services);

        $data['ClientIP'] = [
            // 0 = IPV4
            // 1 = IPV6
            'Type' => (int)filter_var($this->getClientIp(), FILTER_FLAG_IPV6),
            'Address' => $this->getClientIp(),
        ];
        $data['Currency'] = $this->getCurrency();
        $data['AmountDebit'] = $this->getAmount();
//        $data['Invoice'] = $this->getTransactionId();
        $data['ReturnUrl'] = $this->getReturnUrl();
        $data['ReturnURLCancel'] = $this->getCancelUrl();
        $data['ReturnURLError'] = $this->getCancelUrl();
        $data['ReturnURLReject'] = $this->getRejectUrl();
        $data['PushUrl'] = $this->getNotifyUrl();
        $data['redirectCallable'] = $this->getRedirectCallable();

        return $data;
    }

    public function sendData($data)
    {
        ksort($data);
        $jsonData = json_encode($data);

        $endpoint = $this->getEndpoint('/DataRequest');

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
        }

        return $data;
    }
}
