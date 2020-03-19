<?php
declare(strict_types=1);

namespace Omnipay\Buckaroo\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * Buckaroo Purchase Response
 */
class StatusResponse extends AbstractResponse
{
    /**
     * {@inheritdoc}
     */
    public function isSuccessful(): bool
    {
        return $this->getCode() === '190';
    }

    /**
     *
     * get the servicecode of the response this is usually the paymentmethod
     *
     * return string
     */
    public function getServiceCode(): string
    {
        $data = $this->getData();

        return $data['ServiceCode'] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function isPending(): bool
    {
        // 790 = Pending Input
        // 791 = Pending Processing
        // 792 = Awaiting Consumer
        return in_array($this->getCode(), ['790', '791', '792']);
    }

    /**
     * get the parameters for a specific payment service in the response
     * return array|null
     */
    public function getParametersForService($serviceName): ?array
    {
        $parameters = [];

        foreach ($this->data['Services'] as $service) {
            if ($serviceName == $service['Name']) {
                $parameters = $service['Parameters'];
            }
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getCode(): ?string
    {
        $status = $this->data['Status'] ?? [];
        $code = $status['Code'] ?? [];
        $code = $code['Code'] ?? null;
        return $code === null ? null : (string) $code;
    }

    /*
     * return string|null
     */
    public function getAmountDebit(): ?string
    {
        return isset($this->data['AmountDebit']) ? (string) $this->data['AmountDebit'] : null;
    }
}
