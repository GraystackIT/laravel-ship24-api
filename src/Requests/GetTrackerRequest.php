<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * GET /public/v1/trackers/{trackerId} — fetch a single tracker by ID.
 */
class GetTrackerRequest extends Request
{
    protected Method $method = Method::GET;

    /**
     * @param string      $trackerId  Ship24 tracker ID (or clientTrackerId when searchBy is set)
     * @param string|null $searchBy   'trackerId' (default) or 'clientTrackerId'
     */
    public function __construct(
        private readonly string $trackerId,
        private readonly ?string $searchBy = null,
    ) {}

    /**
     * @return string
     */
    public function resolveEndpoint(): string
    {
        return '/trackers/'.rawurlencode($this->trackerId);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultQuery(): array
    {
        if ($this->searchBy !== null) {
            return ['searchBy' => $this->searchBy];
        }

        return [];
    }
}
