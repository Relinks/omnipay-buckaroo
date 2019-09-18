<?php
declare(strict_types=1);

namespace Omnipay\Buckaroo\Message;

use Omnipay\Common\Message\AbstractRequest as CommonAbstractRequest;

/**
 * Buckaroo Abstract Request
 */
abstract class AbstractRequest extends CommonAbstractRequest
{
    /** @var string */
    private $testEndpoint = 'https://testcheckout.buckaroo.nl/json';
    /** @var string */
    private $liveEndpoint = 'https://checkout.buckaroo.nl/json';

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
     * @return AbstractRequest
     */
    public function setWebsiteKey(string $value): AbstractRequest
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
     * @return AbstractRequest
     */
    public function setSecretKey(string $value): AbstractRequest
    {
        return $this->setParameter('secretKey', $value);
    }

    /**
     * @return string
     */
    public function getRejectUrl(): string
    {
        return $this->getParameter('rejectUrl');
    }

    /**
     * sets the Reject URL which is used by buckaroo when
     * the payment is rejected.
     *
     * @param string $value
     *
     * @return AbstractRequest
     */
    public function setRejectUrl(string $value): AbstractRequest
    {
        return $this->setParameter('rejectUrl', $value);
    }

    /**
     * returns the error URL
     *
     * @return string
     */
    public function getErrorUrl(): string
    {
        return $this->getParameter('errorUrl');
    }

    /**
     * sets the error URL which is used by buckaroo when
     * the payment results in an error
     *
     * @param string $value
     *
     * @return AbstractRequest
     */
    public function setErrorUrl(string $value): AbstractRequest
    {
        return $this->setParameter('errorUrl', $value);
    }


    public function getData(): array
    {
        $this->validate('websiteKey', 'secretKey');

        $data = [];

        return $data;
    }

    /**
     * @param string $jsonData
     * @param string $endpoint
     *
     * @return string
     */
    protected function generateAuthorizationToken(string $jsonData, string $endpoint): string
    {
        $method = 'GET';

        if ($jsonData) {
            $md5 = md5($jsonData, true);
            $post = base64_encode($md5);
            $method = 'POST';
        }

        $websiteKey = $this->getWebsiteKey();
        $uri = substr($endpoint, strlen('https://'));
        $uri = strtolower(urlencode($uri));
        $nonce = 'nonce_' . rand(0000000, 9999999);
        $time = time();

        $hmac = $websiteKey . $method . $uri . $time . $nonce . $post;
        $hmac = hash_hmac('sha256', $hmac, $this->getSecretKey(), true);
        $hmac = base64_encode($hmac);

        return $websiteKey . ':' . $hmac . ':' . $nonce . ':' . $time;
    }

    /**
     * @param string $endpoint
     *
     * @return string
     */
    public function getEndpoint(string $endpoint): string
    {
        return ($this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint) . $endpoint;
    }
}
