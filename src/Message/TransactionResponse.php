<?php
declare(strict_types=1);
namespace Omnipay\Buckaroo\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * Class TransactionResponse
 * @package Omnipay\Buckaroo\Message
 * @see https://support.buckaroo.nl/categorie%C3%ABn/transacties/status
 * @see https://support.buckaroo.nl/categorie%C3%ABn/transacties/refund
 */
class TransactionResponse extends AbstractResponse
{
    private const SUCCESS = 190;
    private const PENDING_INPUT = 790; // Awaiting input
    private const PENDING_PROCESSING = 791; // Processing is taking place
    private const PENDING_CUSTOMER_ACTION = 792; // Requires user action
    private const PENDING_ON_HOLD = 793; // Most likely not enough credit for a refund
    private const PENDING_APPROVAL = 794; // Awaiting manual approval

    /**
     * @inheritDoc
     */
    public function isSuccessful(): bool
    {
        // Success (190): The transaction has succeeded and the payment has been received/approved.
        return $this->getCode() === self::SUCCESS;
    }

    /**
     * @inheritDoc
     */
    public function isPending(): bool
    {
        $pendingStatuses = [
            self::PENDING_INPUT,
            self::PENDING_PROCESSING,
            self::PENDING_CUSTOMER_ACTION,
            self::PENDING_ON_HOLD,
            self::PENDING_APPROVAL
        ];
        return in_array($this->getCode(), $pendingStatuses);
    }

    /**
     * {@inheritdoc}
     *  result is being casted to string because buckaroo returns an integer value.
     *  The abstract classes enforces a string value.
     */
    public function getCode(): ?int
    {
        $status = $this->data['Status'] ?? [];
        $code = $status['Code'] ?? [];
        $code = $code['Code'] ?? null;
        return $code === null ? null : (int) $code;
    }

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
