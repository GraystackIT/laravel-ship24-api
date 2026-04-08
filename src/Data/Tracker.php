<?php

declare(strict_types=1);

namespace Graystack\Ship24\Data;

class Tracker
{
    public function __construct(
        public readonly string $trackerId,
        public readonly string $trackingNumber,
        public readonly ?string $shipmentReference,
        public readonly string $createdAt,
        public readonly bool $isSubscribed,
        public readonly ?string $activeUntilDatetime,
    ) {}

    /**
     * @param array<string, mixed> $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            trackerId: (string) ($item['trackerId'] ?? ''),
            trackingNumber: (string) ($item['trackingNumber'] ?? ''),
            shipmentReference: isset($item['shipmentReference']) ? (string) $item['shipmentReference'] : null,
            createdAt: (string) ($item['createdAt'] ?? ''),
            isSubscribed: (bool) ($item['isSubscribed'] ?? false),
            activeUntilDatetime: isset($item['activeUntilDatetime']) ? (string) $item['activeUntilDatetime'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'trackerId'            => $this->trackerId,
            'trackingNumber'       => $this->trackingNumber,
            'shipmentReference'    => $this->shipmentReference,
            'createdAt'            => $this->createdAt,
            'isSubscribed'         => $this->isSubscribed,
            'activeUntilDatetime'  => $this->activeUntilDatetime,
        ];
    }
}
