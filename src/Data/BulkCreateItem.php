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

        $error = isset($item['error']) && is_array($item['error']) ? $item['error'] : [];

        return new self(
            success: (bool) ($item['success'] ?? false),
            tracker: $tracker,
            errorCode: isset($error['code']) ? (string) $error['code'] : null,
            errorMessage: isset($error['message']) ? (string) $error['message'] : null,
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
