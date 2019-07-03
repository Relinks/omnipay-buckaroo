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

        $statusCode = $this->data['Status']['Code']['Code'] ?? null;

        return $statusCode === 190;
    }

    /**
     * {@inheritdoc}
     */
    public function isRedirect(): bool
    {
        $subCode = $this->data['Status']['SubCode']['Code'] ?? null;

        return $subCode === 'S002';
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
}
