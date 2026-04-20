<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class ListTrackersRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $page = 1,
        private readonly int $limit = 20,
        private readonly ?int $sort = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/trackers';
    }

    protected function defaultQuery(): array
    {
        $query = [
            'page'  => $this->page,
            'limit' => $this->limit,
        ];

        if ($this->sort !== null) {
            $query['sort'] = $this->sort;
        }

        return $query;
    }
}
