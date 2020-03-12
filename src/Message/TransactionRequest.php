<?php
declare(strict_types=1);
namespace Omnipay\Buckaroo\Message;

use Omnipay\Common\Exception\RuntimeException;
use Throwable;

/**
 * Currently used for IDeal refunds only. Change if needed for other purposes
 *
 * Class TransactionRequest
 * @package Omnipay\Buckaroo\Message
 */
class TransactionRequest extends AbstractRequest
{
    public function getData(): array
    {
        $data = parent::getData();

        $data['Services'] = $this->getServices();

    }

    /**
     * @inheritDoc
     */
    public function sendData($data): TransactionResponse
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

        return new TransactionResponse($this, $respData);
    }

    public function setAmountCredit(string $amount)
    {
        return $this->setParameter('AmountCredit', $amount);
    }

    public function setInvoice(string $invoice)
    {
        return $this->setParameter('Invoice', $invoice);
    }

    public function setOriginalTransactionKey(string $transactionKey)
    {
        return $this->setParameter('OriginalTransactionKey', $transactionKey);
    }

    private function getServices(): array
    {
        return [
            'ServiceList' => [
                'Name' => 'Ideal',
                'Action' => 'Refund',
            ],
        ];
    }
}
