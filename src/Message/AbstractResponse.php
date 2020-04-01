<?php
declare(strict_types=1);

namespace Omnipay\Buckaroo\Message;

use Omnipay\Common\Message\AbstractResponse as CommonAbstractResponse;

/**
 * Class AbstractResponse
 * @see https://support.buckaroo.nl/categorie%C3%ABn/transacties/status
 */
abstract class AbstractResponse extends CommonAbstractResponse
{
    protected const SUCCESS = 190; // Success
    protected const FAILED_TRANSACTION = 490; // Transaction failed
    protected const FAILED_VALIDATION = 491; // Request had validation errors
    protected const FAILED_TECH_ERROR = 492; // Transaction failed due to technical errors
    protected const FAILED_DENIED = 690; // Transaction denied by (third party) payment provider
    protected const PENDING_INPUT = 790; // Awaiting input
    protected const PENDING_PROCESSING = 791; // Processing is taking place
    protected const PENDING_CUSTOMER_ACTION = 792; // Requires user action
    protected const PENDING_ON_HOLD = 793; // Most likely not enough credit for a refund
    protected const PENDING_APPROVAL = 794; // Awaiting manual approval
    protected const CANCELLED_BY_USER = 890; // Cancelled by user
    protected const CANCELLED_BY_MERCHANT = 891; // Cancelled by merchant (us)

    /**
     * @inheritDoc
     */
    public function isSuccessful(): bool
    {
        // Success (190): The transaction has succeeded and the payment has been received/approved.
        return ((int) $this->getCode()) === self::SUCCESS;
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
        return in_array((int) $this->getCode(), $pendingStatuses);
    }

    /**
     * {@inheritdoc}
     */
    public function isCancelled(): bool
    {
        $cancelledStatuses = [
            self::CANCELLED_BY_USER,
            self::CANCELLED_BY_MERCHANT,
        ];
        return in_array((int) $this->getCode(), $cancelledStatuses);
    }

    /**
     * {@inheritdoc}
     *  result is being casted to string because buckaroo returns an integer value.
     *  The abstract classes enforces a string value.
     */
    public function getCode(): ?string
    {
        $status = $this->data['Status'] ?? [];
        $code = $status['Code'] ?? [];
        $code = $code['Code'] ?? null;
        return $code === null ? null : (string) $code;
    }
}
