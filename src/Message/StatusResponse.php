<?php
declare(strict_types=1);

namespace Omnipay\Buckaroo\Message;

use Omnipay\Common\Message\RequestInterface;

/**
 * Buckaroo Purchase Response
 */
class StatusResponse extends AbstractResponse
{
    private ?int $httpStatusCode;
    private ?string $rawBody;

    public function __construct(
        RequestInterface $request,
        $data,
        ?int $httpStatusCode = null,
        ?string $rawBody = null
    ) {
        parent::__construct($request, $data);
        $this->httpStatusCode = $httpStatusCode;
        $this->rawBody = $rawBody;
    }

    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    // Buckaroo rejects a request (e.g. a bad Authorization header) with a text/plain body, which getData() can't hold
    public function getRawBody(): ?string
    {
        return $this->rawBody;
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
