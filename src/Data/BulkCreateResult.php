<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Data;

/**
 * Result of a bulk tracker creation request.
 */
class BulkCreateResult
{
    /**
     * @param string         $status        Overall status: success, partial, or error
     * @param int            $requested     Total number of trackers submitted
     * @param int            $successCount  Number successfully created
     * @param int            $existingCount Number already existing
     * @param int            $errorCount    Number that failed
     * @param BulkCreateItem[] $items       Per-item results
     */
    public function __construct(
        public readonly string $status,
        public readonly int $requested,
        public readonly int $successCount,
        public readonly int $existingCount,
        public readonly int $errorCount,
        public readonly array $items,
    ) {}

    /**
     * Construct from the root-level bulk create API response body.
     *
     * @param  array<string, mixed> $data  Root JSON response from POST /trackers/bulk
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $summary = isset($data['summary']) && is_array($data['summary']) ? $data['summary'] : [];

        $items = array_map(
            static fn (array $item) => BulkCreateItem::fromArray($item),
            isset($data['data']) && is_array($data['data']) ? $data['data'] : []
        );

        return new self(
            status: (string) ($data['status'] ?? ''),
            requested: (int) ($summary['totalInputs'] ?? 0),
            successCount: (int) ($summary['totalCreated'] ?? 0),
            existingCount: (int) ($summary['totalExisting'] ?? 0),
            errorCount: (int) ($summary['totalErrors'] ?? 0),
            items: $items,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status'        => $this->status,
            'requested'     => $this->requested,
            'successCount'  => $this->successCount,
            'existingCount' => $this->existingCount,
            'errorCount'    => $this->errorCount,
            'items'         => array_map(static fn (BulkCreateItem $i) => $i->toArray(), $this->items),
        ];
    }
}
