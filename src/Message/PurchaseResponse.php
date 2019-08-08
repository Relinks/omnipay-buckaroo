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

        return $this->getCode() === 190;
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
        return $this->getCode() === 890;
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
     */
    public function getCode(): ?string
    {
        return $this->data['Status']['Code']['Code'] ?? null;
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
