<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class CreateAndTrackRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  string[]|null  $courierCode  Up to 3 courier codes
     */
    public function __construct(
        private readonly string $trackingNumber,
        private readonly ?string $shipmentReference = null,
        private readonly ?string $clientTrackerId = null,
        private readonly ?string $originCountryCode = null,
        private readonly ?string $destinationCountryCode = null,
        private readonly ?string $destinationPostCode = null,
        private readonly ?string $shippingDate = null,
        private readonly ?array $courierCode = null,
        private readonly ?string $courierName = null,
        private readonly ?string $trackingUrl = null,
        private readonly ?string $orderNumber = null,
        private readonly ?string $title = null,
        private readonly ?string $recipientEmail = null,
        private readonly ?string $recipientName = null,
        private readonly ?bool $restrictTrackingToCourierCode = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/trackers/track';
    }

    protected function defaultBody(): array
    {
        $body = ['trackingNumber' => $this->trackingNumber];

        if ($this->shipmentReference !== null) {
            $body['shipmentReference'] = $this->shipmentReference;
        }

        if ($this->clientTrackerId !== null) {
            $body['clientTrackerId'] = $this->clientTrackerId;
        }

        if ($this->originCountryCode !== null) {
            $body['originCountryCode'] = $this->originCountryCode;
        }

        if ($this->destinationCountryCode !== null) {
            $body['destinationCountryCode'] = $this->destinationCountryCode;
        }

        if ($this->destinationPostCode !== null) {
            $body['destinationPostCode'] = $this->destinationPostCode;
        }

        if ($this->shippingDate !== null) {
            $body['shippingDate'] = $this->shippingDate;
        }

        if ($this->courierCode !== null) {
            $body['courierCode'] = $this->courierCode;
        }

        if ($this->courierName !== null) {
            $body['courierName'] = $this->courierName;
        }

        if ($this->trackingUrl !== null) {
            $body['trackingUrl'] = $this->trackingUrl;
        }

        if ($this->orderNumber !== null) {
            $body['orderNumber'] = $this->orderNumber;
        }

        if ($this->title !== null) {
            $body['title'] = $this->title;
        }

        $recipient = [];

        if ($this->recipientEmail !== null) {
            $recipient['email'] = $this->recipientEmail;
        }

        if ($this->recipientName !== null) {
            $recipient['name'] = $this->recipientName;
        }

        if ($recipient !== []) {
            $body['recipient'] = $recipient;
        }

        if ($this->restrictTrackingToCourierCode !== null) {
            $body['settings'] = ['restrictTrackingToCourierCode' => $this->restrictTrackingToCourierCode];
        }

        return $body;
    }
}
