<?php
declare(strict_types=1);

namespace Omnipay\Buckaroo\Message;

/**
 * Buckaroo Purchase Response
 */
class StatusResponse extends AbstractResponse
{
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

    /*
     * return string|null
     */
    public function getAmountDebit(): ?string
    {
        return isset($this->data['AmountDebit']) ? (string) $this->data['AmountDebit'] : null;
    }
}
