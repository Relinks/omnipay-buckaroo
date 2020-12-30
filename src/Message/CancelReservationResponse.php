<?php
declare(strict_types=1);
namespace Omnipay\Buckaroo\Message;

/**
 * Class CancelReservationResponse
 * @package Omnipay\Buckaroo\Message
 */
class CancelReservationResponse extends AbstractResponse
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
