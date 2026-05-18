<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Data;

class BulkCreateItem
{
    public function __construct(
        public readonly bool $success,
        public readonly ?Tracker $tracker,
        public readonly ?string $errorCode,
        public readonly ?string $errorMessage,
    ) {}

    /**
     * @param array<string, mixed> $item
     */
    public static function fromArray(array $item): self
    {
        $tracker = null;

        if (isset($item['tracker']) && is_array($item['tracker'])) {
            $tracker = Tracker::fromArray($item['tracker']);
        }

        $errors = isset($item['errors']) && is_array($item['errors']) ? $item['errors'] : [];
        $firstError = $errors[0] ?? [];

        return new self(
            success: (($item['itemStatus'] ?? '') !== 'error'),
            tracker: $tracker,
            errorCode: isset($firstError['code']) ? (string) $firstError['code'] : null,
            errorMessage: isset($firstError['message']) ? (string) $firstError['message'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success'      => $this->success,
            'tracker'      => $this->tracker?->toArray(),
            'errorCode'    => $this->errorCode,
            'errorMessage' => $this->errorMessage,
        ];
    }
}
