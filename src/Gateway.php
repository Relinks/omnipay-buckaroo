<?php
declare(strict_types=1);

namespace Omnipay\Buckaroo;

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
        return $this->createRequest('\Omnipay\Buckaroo\Message\PurchaseRequest', $parameters);
    }
}
