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

    public function __construct(private readonly string $trackingNumber) {}

    public function resolveEndpoint(): string
    {
        return '/trackers/search';
    }

    protected function defaultBody(): array
    {
        return ['trackingNumber' => $this->trackingNumber];
    }
}
