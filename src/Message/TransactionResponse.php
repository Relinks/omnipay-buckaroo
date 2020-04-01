<?php
declare(strict_types=1);
namespace Omnipay\Buckaroo\Message;

/**
 * Class TransactionResponse
 * @package Omnipay\Buckaroo\Message
 * @see https://support.buckaroo.nl/categorie%C3%ABn/transacties/status
 * @see https://support.buckaroo.nl/categorie%C3%ABn/transacties/refund
 */
class TransactionResponse extends AbstractResponse
{
    /**
     * {@inheritdoc}
     */
    public function getMessage(): ?string
    {
        return $this->data['Status']['Code']['Description'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionReference(): string
    {
        return $this->data['Key'] ?? '';
    }
}
