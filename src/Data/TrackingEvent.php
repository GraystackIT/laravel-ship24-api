<?php

declare(strict_types=1);

namespace Graystack\Ship24\Data;

class TrackingEvent
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $trackingNumber,
        public readonly string $datetime,
        public readonly ?string $hasNoTime,
        public readonly ?string $utcOffset,
        public readonly ?string $location,
        public readonly ?string $statusCode,
        public readonly ?string $statusCategory,
        public readonly ?string $statusMilestone,
        public readonly string $status,
        public readonly ?string $occurrenceDatetime,
        public readonly ?string $order,
        public readonly ?string $courierId,
    ) {}

    /**
     * @param array<string, mixed> $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            eventId: (string) ($item['eventId'] ?? ''),
            trackingNumber: (string) ($item['trackingNumber'] ?? ''),
            datetime: (string) ($item['datetime'] ?? ''),
            hasNoTime: isset($item['hasNoTime']) ? (string) $item['hasNoTime'] : null,
            utcOffset: isset($item['utcOffset']) ? (string) $item['utcOffset'] : null,
            location: isset($item['location']) ? (string) $item['location'] : null,
            statusCode: isset($item['statusCode']) ? (string) $item['statusCode'] : null,
            statusCategory: isset($item['statusCategory']) ? (string) $item['statusCategory'] : null,
            statusMilestone: isset($item['statusMilestone']) ? (string) $item['statusMilestone'] : null,
            status: (string) ($item['status'] ?? ''),
            occurrenceDatetime: isset($item['occurrenceDatetime']) ? (string) $item['occurrenceDatetime'] : null,
            order: isset($item['order']) ? (string) $item['order'] : null,
            courierId: isset($item['courierId']) ? (string) $item['courierId'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'eventId'             => $this->eventId,
            'trackingNumber'      => $this->trackingNumber,
            'datetime'            => $this->datetime,
            'hasNoTime'           => $this->hasNoTime,
            'utcOffset'           => $this->utcOffset,
            'location'            => $this->location,
            'statusCode'          => $this->statusCode,
            'statusCategory'      => $this->statusCategory,
            'statusMilestone'     => $this->statusMilestone,
            'status'              => $this->status,
            'occurrenceDatetime'  => $this->occurrenceDatetime,
            'order'               => $this->order,
            'courierId'           => $this->courierId,
        ];
    }
}
