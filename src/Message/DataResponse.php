<?php
declare(strict_types=1);

namespace Omnipay\Buckaroo\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Buckaroo Purchase Response
 */
class DataResponse extends AbstractResponse implements RedirectResponseInterface
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

        return $this->getCode() === '190';
    }

    /**
     * {@inheritdoc}
     */
    public function isCancelled(): bool
    {
        return $this->getCode() === '890';
    }

    /**
     * {@inheritdoc}
     */
    public function isPending()
    {
        // 790 = Pending Input
        // 791 = Pending Processing
        // 792 = Awaiting Consumer

        return in_array($this->getCode(),['790', '791', '792']);
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

    /** return string */
    public function getQrImageUrl(): ?string
    {
//        $this->getData
        foreach($this->data['Services'] as $services){
            if($services['Name'] == 'IdealQr'){
                foreach($services['Parameters'] as $paramaters){
                    if($paramaters['Name'] == 'QrImageUrl')
                    {
                        return $paramaters['Value'] ?? null;
                    }
                }
            }
        }
    }

    public function getRedirectUrl()
    {
        if (is_callable($this->getCallableFunctionRedirect())) {
            $redirectUrl = call_user_func($this->getCallableFunctionRedirect(), $this);
        }

        return $redirectUrl;
    }

    public function isRedirect()
    {
        return $this->data['ServiceCode'] === 'IdealQr';
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
