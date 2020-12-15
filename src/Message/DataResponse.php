<?php
declare(strict_types=1);

namespace Omnipay\Buckaroo\Message;

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

        return parent::isSuccessful();
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

        return null;
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
        return $this->data['ServiceCode'] === 'IdealQr' || $this->data['ServiceCode'] === 'KlarnaKp';
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
