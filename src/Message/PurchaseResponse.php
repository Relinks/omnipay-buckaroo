<?php
declare(strict_types=1);

namespace Omnipay\Buckaroo\Message;

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
        return parent::isSuccessful();
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
