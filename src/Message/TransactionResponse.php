<?php
declare(strict_types=1);
namespace Omnipay\Buckaroo\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * Class TransactionResponse
 * @package Omnipay\Buckaroo\Message
 */
class TransactionResponse extends AbstractResponse
{
    private const SUCCESS = 190;
    private const PENDING_INPUT = 790;
    private const PENDING = 791;
    private const PENDING_APPROVAL = 794;

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
        // Pending Processing (791): The transaction is being processed.
        $pendingStatuses = [
            self::PENDING_INPUT,
            self::PENDING,
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
