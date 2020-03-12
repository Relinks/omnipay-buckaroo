<?php
declare(strict_types=1);

namespace Omnipay\Buckaroo;

use Omnipay\Buckaroo\Message\DataRequest;
use Omnipay\Buckaroo\Message\PurchaseRequest;
use Omnipay\Buckaroo\Message\StatusRequest;
use Omnipay\Buckaroo\Message\TransactionRequest;
use Omnipay\Common\AbstractGateway;

/**
 * Buckaroo Credit Card Gateway
 */
class Gateway extends AbstractGateway
{
    public function getName()
    {
        return 'Buckaroo Gateway';
    }

    public function getDefaultParameters()
    {
        return [
            'websiteKey' => '',
            'secretKey' => '',
            'testMode' => false,
        ];
    }

    /**
     * @return string
     */
    public function getWebsiteKey(): string
    {
        return $this->getParameter('websiteKey');
    }

    /**
     * @param string $value
     *
     * @return Gateway
     */
    public function setWebsiteKey(string $value): Gateway
    {
        return $this->setParameter('websiteKey', $value);
    }

    /**
     * @return string
     */
    public function getSecretKey(): string
    {
        return $this->getParameter('secretKey');
    }

    /**
     * @param string $value
     *
     * @return Gateway
     */
    public function setSecretKey(string $value): Gateway
    {
        return $this->setParameter('secretKey', $value);
    }

    public function purchase(array $parameters = [])
    {
        return $this->createRequest(PurchaseRequest::class, $parameters);
    }

    public function status(array $parameters = [])
    {
        return $this->createRequest(StatusRequest::class, $parameters);
    }

    public function data(array $parameters = [])
    {
        return $this->createRequest(DataRequest::class, $parameters);
    }

    public function transaction(array $parameters = [])
    {
        return $this->createRequest(TransactionRequest::class, $parameters);
    }
}
