<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class BulkCreateTrackersRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param array<int, array<string, mixed>> $trackers Up to 100 tracker-create objects
     */
    public function __construct(private readonly array $trackers) {}

    public function resolveEndpoint(): string
    {
        return '/trackers/bulk';
    }

    protected function defaultBody(): array
    {
        return $this->trackers;
    }
}
