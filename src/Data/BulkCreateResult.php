<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Data;

class BulkCreateResult
{
    /**
     * @param BulkCreateItem[] $items
     */
    public function __construct(
        public readonly string $status,
        public readonly int $requested,
        public readonly int $successCount,
        public readonly int $errorCount,
        public readonly array $items,
    ) {}

    /**
     * @param array<string, mixed> $data  The `data.trackers` envelope from the API response
     */
    public static function fromArray(array $data): self
    {
        $summary = isset($data['summary']) && is_array($data['summary']) ? $data['summary'] : [];

        $items = array_map(
            static fn (array $item) => BulkCreateItem::fromArray($item),
            isset($data['items']) && is_array($data['items']) ? $data['items'] : []
        );

        return new self(
            status: (string) ($data['status'] ?? ''),
            requested: (int) ($summary['requested'] ?? 0),
            successCount: (int) ($summary['success'] ?? 0),
            errorCount: (int) ($summary['error'] ?? 0),
            items: $items,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status'       => $this->status,
            'requested'    => $this->requested,
            'successCount' => $this->successCount,
            'errorCount'   => $this->errorCount,
            'items'        => array_map(static fn (BulkCreateItem $i) => $i->toArray(), $this->items),
        ];
    }
}
