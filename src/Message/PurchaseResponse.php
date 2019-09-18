<?php
declare(strict_types=1);

namespace Omnipay\Buckaroo\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Buckaroo Purchase Response
 */
class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{
    /**
     * {@inheritdoc}
     */
    public function isSuccessful(): bool
    {
        if ($this->isRedirect()) {
            return false;
        }
        // 190 = Success
        // 792 = Wating for consumer
        return in_array($this->getCode(),['190', '792']);
    }

    /**
     * {@inheritdoc}
     */
    public function isRedirect(): bool
    {
        return $this->data['RequiredAction']['RedirectURL'] ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function isCancelled(): bool
    {
        return $this->getCode() === '890';
    }

    public function isPending()
    {
        // 790 = Pending Input
        // 791 = Pending Processing

        return in_array($this->getCode(),['790', '791']);
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectUrl(): ?string
    {
        $redirectUrl = $this->data['RequiredAction']['RedirectURL'] ?? null;

        return $redirectUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectMethod(): string
    {
        return 'GET';
    }

    /**
     * {@inheritdoc}
     *  result is being casted to string because buckaroo returns an integer value.
     *  The abstract classes enforces a string value.
     */
    public function getCode(): ?string
    {
        return (string) $this->data['Status']['Code']['Code'] ?? null;
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
    public function getTransactionReference()
    {
        return $this->data['Key'];
    }
}
