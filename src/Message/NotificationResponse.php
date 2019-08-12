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
     * @return string A reference provided by the gateway to represent this transaction
     */
    public function getTransactionReference()
    {
        return $this->data['brq_payment'];
    }

    /**
     * Was the transaction successful?
     *
     * @return string Transaction status, one of {@see STATUS_COMPLETED}, {@see #STATUS_PENDING},
     * or {@see #STATUS_FAILED}.
     */
    public function getTransactionStatus()
    {
        if($this->data['brq_statuscode'] == 190) {
            return NotificationInterface::STATUS_COMPLETED;
        }
        return NotificationInterface::STATUS_FAILED;
    }

    /**
     * Response Message
     *
     * @return string A response message from the payment gateway
     */
    public function getMessage()
    {

    }

    /**
     * @return
     */
    public function getAmount()
    {
        return $this->data['brq_amount'];
    }
}
