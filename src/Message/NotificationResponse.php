<?php
declare(strict_types=1);

namespace Omnipay\Buckaroo\Message;

use Omnipay\Common\Message\NotificationInterface;

class NotificationResponse implements NotificationInterface
{
    /** @var array  */
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Gateway Reference
     *
     * @return string|null A reference provided by the gateway to represent this transaction
     */
    public function getTransactionReference(): ?string
    {
        return $this->data['brq_transactions'] ?? null;
    }

    /**
     * Was the transaction successful?
     *
     * @return string Transaction status, one of {@see STATUS_COMPLETED}, {@see #STATUS_PENDING},
     * or {@see #STATUS_FAILED}.
     */
    public function getTransactionStatus()
    {
        if ($this->data['brq_statuscode'] == 190) {
            return NotificationInterface::STATUS_COMPLETED;
        } elseif ($this->data['brq_statuscode'] == 791) {
            return NotificationInterface::STATUS_PENDING;
        }
        return NotificationInterface::STATUS_FAILED;
    }

    /**
     * Was a non-completed transaction given a status that can still be processed later (delayed).
     * Ie; pending manual review or: e-check that was not yet cleared.
     *
     * @return bool
     */
    public function isTransactionCompletionDelayed(): bool
    {
        if (
            isset($this->data['brq_statuscode_detail'])
            && ($this->data['brq_statuscode_detail'] == 'P917' || $this->data['brq_statuscode_detail'] == 'P918')
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isCanceled(): bool
    {
        return $this->data['brq_statuscode'] == 490;
    }

    /**
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->data['brq_statuscode'] == 690;
    }

    /**
     * Response Message
     *
     * @return string A response message from the payment gateway
     */
    public function getMessage()
    {
        return $this->data['brq_statusmessage'];
    }

    /**
     * @return string
     */
    public function getAmount(): ?string
    {
        return $this->data['brq_amount'] ?? null;
    }

    /**
     * @return string
     */
    public function getInvoiceNumber()
    {
        return $this->data['brq_invoicenumber'];
    }

    /**
     * return string
     */
    public function getTransactionMethod()
    {
        return $this->data['brq_transaction_method'];
    }

    /**
     * @return string|null
     */
    public function getTransactionType(): ?string
    {
        return $this->data['brq_transaction_type'] ?? null;
    }

    /**
     * @return string|null
     */
    public function getKlarnaReservationNumber(): ?string
    {
        return $this->data['brq_SERVICE_klarnakp_ReservationNumber'] ?? null;
    }

    /**
     * @return bool
     */
    public function isKlarnaResponse(): bool {
        $paymenttype = $this->data['brq_primary_service'] ?? $this->data['brq_transaction_method'] ?? null;
        return $paymenttype === 'KlarnaKp';
    }

    public function isKlarnaPayTransaction(): bool {
        $transactiontype = $this->data['brq_transaction_type'] ?? '';
        return $transactiontype === 'V610';
    }

    /**
     * @return string|null
     */
    public function getKlarnaTransactionReference(): ?string
    {
        return $this->data['brq_datarequest'] ?? null;
    }
}
