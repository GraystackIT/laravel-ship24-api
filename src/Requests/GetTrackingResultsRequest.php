<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetTrackingResultsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $trackerId) {}

    public function resolveEndpoint(): string
    {
        return '/trackers/'.$this->trackerId.'/results';
    }
}
