<?php
declare(strict_types=1);
namespace Omnipay\Buckaroo\Message;

use Omnipay\Common\Exception\InvalidRequestException;
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
    /**
     * @return array
     * @throws InvalidRequestException
     */
    public function getData(): array
    {
        $data = parent::getData();

        $this->validate('AmountCredit', 'Invoice', 'OriginalTransactionKey', 'paymentMethod');

        $data['Currency'] = $this->getCurrency();
        $data['AmountCredit'] = $this->getAmountCredit();
        $data['Invoice'] = $this->getInvoice();
        $data['OriginalTransactionKey'] = $this->getOriginalTransactionKey();
        $data['Services'] = $this->getServices();
        if (! empty($this->getPushURL())) {
            $data['PushURL'] = $this->getPushURL();
        }

        return $data;
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

    public function getAmountCredit(): string
    {
        return $this->getParameter('AmountCredit');
    }

    public function setInvoice(string $invoice)
    {
        return $this->setParameter('Invoice', $invoice);
    }

    public function getInvoice(): string
    {
        return $this->getParameter('Invoice') ?? '';
    }

    public function setOriginalTransactionKey(string $transactionKey)
    {
        return $this->setParameter('OriginalTransactionKey', $transactionKey);
    }

    public function getOriginalTransactionKey(): string
    {
        return $this->getParameter('OriginalTransactionKey') ?? '';
    }

    private function getServices(): array
    {
        $paymentMethod = $this->getParameter('paymentMethod');

        return [
            'ServiceList' => [
                [
                    'Name' => $paymentMethod,
                    'Action' => 'Refund',
                ],
            ],
        ];
    }

    public function getPushURL(): string
    {
        return $this->getParameter('PushURL') ?? '';
    }

    public function setPushURL(string $pushURL)
    {
        return $this->setParameter('PushURL', $pushURL);
    }
}
