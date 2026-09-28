<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class SearchTrackingRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  string[]|null  $courierCode  Up to 3 courier codes
     */
    public function __construct(
        private readonly string $trackingNumber,
        private readonly ?string $originCountryCode = null,
        private readonly ?string $destinationCountryCode = null,
        private readonly ?string $destinationPostCode = null,
        private readonly ?string $shippingDate = null,
        private readonly ?array $courierCode = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/tracking/search';
    }

    protected function defaultBody(): array
    {
        $body = ['trackingNumber' => $this->trackingNumber];

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

        return $body;
    }
}
