<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class UpdateTrackerRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PATCH;

    /**
     * @param array<string, mixed> $updates Fields to update: isSubscribed, courierCode,
     *                                       originCountryCode, destinationCountryCode,
     *                                       destinationPostCode, shippingDate
     */
    public function __construct(
        private readonly string $trackerId,
        private readonly array $updates,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/trackers/'.$this->trackerId;
    }

    protected function defaultBody(): array
    {
        return $this->updates;
    }
}
