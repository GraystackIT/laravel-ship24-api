<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * PATCH /public/v1/trackers/{trackerId} — update an existing tracker.
 */
class UpdateTrackerRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PATCH;

    /**
     * @param string               $trackerId  Ship24 tracker ID (or clientTrackerId when searchBy is set)
     * @param array<string, mixed> $updates    Fields to update
     * @param string|null          $searchBy   'trackerId' (default) or 'clientTrackerId'
     */
    public function __construct(
        private readonly string $trackerId,
        private readonly array $updates,
        private readonly ?string $searchBy = null,
    ) {}

    /**
     * @return string
     */
    public function resolveEndpoint(): string
    {
        return '/trackers/'.$this->trackerId;
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

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->updates;
    }
}
