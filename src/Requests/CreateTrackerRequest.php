<?php

declare(strict_types=1);

namespace Graystack\Ship24\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class CreateTrackerRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $trackingNumber,
        private readonly ?string $shipmentReference = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/trackers';
    }

    protected function defaultBody(): array
    {
        $body = ['trackingNumber' => $this->trackingNumber];

        if ($this->shipmentReference !== null) {
            $body['shipmentReference'] = $this->shipmentReference;
        }

        return $body;
    }
}
