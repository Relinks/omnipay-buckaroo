<?php
namespace Omnipay\Buckaroo\Message;

class StatusRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     *
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function getData(): array
    {
        $data = parent::getData();

        $this->validate('transactionReference');

        $data['transactionReference'] = $this->getTransactionReference();

        return $data;
    }

    public function sendData($data)
    {
        $endpoint = $this->getEndpoint('/Transaction/Status/'.$this->getTransactionReference());
        $jsonData = "";
        try {
            $response = $this->httpClient->request(
                'GET',
                $endpoint,
                [
                    'Authorization' => 'hmac ' . $this->generateAuthorizationToken($jsonData, $endpoint),
                    'Content-Type' => 'application/json',
                ]
            );
            $respData = json_decode((string) $response->getBody(), true);
        } catch (Throwable $t) {
            throw new RuntimeException('Could not send the request', 0, $t);
        }

        return new StatusResponse($this, $respData);
    }
}
