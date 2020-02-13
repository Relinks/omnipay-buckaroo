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
    /** @var callable|null */
    private $callableFunctionRedirect;

    /**
     * {@inheritdoc}
     */
    public function isSuccessful(): bool
    {
        if ($this->isRedirect()) {
            return false;
        }
        // 190 = Success
        return in_array($this->getCode(), ['190']);
    }

    /**
     * {@inheritdoc}
     */
    public function isRedirect(): bool
    {
        $redirectURL = $this->data['RequiredAction']['RedirectURL'] ?? null;

        return (bool)$redirectURL;
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
        // 792 = Waiting for consumer

        return in_array($this->getCode(), ['790', '791', '792']);
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectUrl(): ?string
    {
        if ($this->getCallableFunctionRedirect() && is_callable($this->getCallableFunctionRedirect())) {
            $redirectUrl = call_user_func($this->getCallableFunctionRedirect(), $this);
        } else {
            $redirectUrl = $this->data['RequiredAction']['RedirectURL'] ?? null;
        }

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

    public function setCallableFunctionRedirect($callableFunctionRedirect)
    {
        $this->callableFunctionRedirect = $callableFunctionRedirect;
    }

    public function getCallableFunctionRedirect()
    {
        return $this->callableFunctionRedirect;
    }
}
