<?php
declare(strict_types=1);
namespace Omnipay\Buckaroo\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Exception\RuntimeException;

/**
 * Currently only used for klarnakp (nieuwe klarna paymethod)
 *
 * Class CancelReservationRequest
 * @package Omnipay\Buckaroo\Message
 */
class CancelReservationRequest extends AbstractRequest
{
    /**
     * @return array
     * @throws InvalidRequestException
     */
    public function getData(): array
    {
        $data = parent::getData();

        $this->validate('AmountCredit', 'Invoice', 'paymentMethod');

        $data['Currency'] = $this->getCurrency();
        $data['Invoice'] = $this->getInvoice();
        $data['Services'] = $this->getServices();
        if (! empty($this->getPushURL())) {
            $data['PushURL'] = $this->getPushURL();
        }
        if ($this->getDescription()) {
            $data['Description'] = $this->getDescription();
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function sendData($data): CancelReservationResponse
    {
        ksort($data);
        $jsonData = json_encode($data);

        $endpoint = $this->getEndpoint('/DataRequest');

        try {
            $response = $this->httpClient->request(
                'POST',
                $endpoint,
                [
                    'Authorization' => 'hmac ' . $this->generateAuthorizationToken($jsonData, $endpoint),
                    'Content-Type' => 'application/json',
                ],
                $jsonData
            );

            $respData = json_decode((string) $response->getBody(), true);
        } catch (Throwable $t) {
            throw new RuntimeException('Could not send the request', 0, $t);
        }

        return new CancelReservationResponse($this, $respData);
    }

    public function setAmountCredit(string $amount)
    {
        return $this->setParameter('AmountCredit', $amount);
    }

    public function getAmountCredit(): string
    {
        return $this->getParameter('AmountCredit');
    }

    public function setInvoice(string $invoice)
    {
        return $this->setParameter('Invoice', $invoice);
    }

    public function getInvoice(): string
    {
        return $this->getParameter('Invoice') ?? '';
    }

    /**
     * @return string|null
     */
    public function getReservationNumber(): ?string
    {
        return $this->getParameter('reservationNumber');
    }

    /**
     * @param string|null $reservationNumber
     *
     * @return $this
     */
    public function setReservationNumber(?string $reservationNumber): CancelReservationRequest
    {
        $this->setParameter('reservationNumber', $reservationNumber);

        return $this;
    }

    private function getServices(): array
    {
        $paymentMethod = $this->getParameter('paymentMethod');

        if ($paymentMethod !== 'klarnakp') {
            return [];
        }

        return [
            'ServiceList' => [
                ['Name' => $this->getPaymentMethod(),
                    'Action' => 'CancelReservation',
                    'Parameters' => [
                        [
                            'Name' => 'ReservationNumber',
                            'Value' => $this->getReservationNumber(),
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getPushURL(): string
    {
        return $this->getParameter('PushURL') ?? '';
    }

    public function setPushURL(string $pushURL)
    {
        return $this->setParameter('PushURL', $pushURL);
    }
}

