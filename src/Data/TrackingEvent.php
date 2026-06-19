<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Data;

/**
 * Represents a single tracking event in a shipment's history.
 */
class TrackingEvent
{
    /**
     * @param string      $eventId              Ship24 unique event ID
     * @param string      $trackingNumber       Original tracking number used to create the tracker
     * @param string|null $eventTrackingNumber  Tracking number on which this event was found (may differ)
     * @param string|null $occurrenceDatetime   When the event occurred (primary field)
     * @param int|null    $order                Tie-breaking order for events with the same datetime
     * @param string|null $location             Raw location text
     * @param string|null $sourceCode           Internal source code that provided this event
     * @param string|null $courierCode          Courier code for this event
     * @param string|null $statusCode           Normalised status code
     * @param string|null $statusCategory       Status category
     * @param string|null $statusMilestone      Milestone slug
     * @param string|null $status               Raw status text from the courier
     * @param string|null $datetime             Deprecated — use occurrenceDatetime
     * @param bool|null   $hasNoTime            Deprecated
     * @param string|null $utcOffset            Deprecated
     */
    public function __construct(
        public readonly string $eventId,
        public readonly string $trackingNumber,
        public readonly ?string $eventTrackingNumber,
        public readonly ?string $occurrenceDatetime,
        public readonly ?int $order,
        public readonly ?string $location,
        public readonly ?string $sourceCode,
        public readonly ?string $courierCode,
        public readonly ?string $statusCode,
        public readonly ?string $statusCategory,
        public readonly ?string $statusMilestone,
        public readonly ?string $status,
        public readonly ?string $datetime,
        public readonly ?bool $hasNoTime,
        public readonly ?string $utcOffset,
    ) {}

    /**
     * @param  array<string, mixed> $item
     * @return self
     */
    public static function fromArray(array $item): self
    {
        return new self(
            eventId: (string) ($item['eventId'] ?? ''),
            trackingNumber: (string) ($item['trackingNumber'] ?? ''),
            eventTrackingNumber: isset($item['eventTrackingNumber']) ? (string) $item['eventTrackingNumber'] : null,
            occurrenceDatetime: isset($item['occurrenceDatetime']) ? (string) $item['occurrenceDatetime'] : null,
            order: isset($item['order']) ? (int) $item['order'] : null,
            location: isset($item['location']) ? (string) $item['location'] : null,
            sourceCode: isset($item['sourceCode']) ? (string) $item['sourceCode'] : null,
            courierCode: isset($item['courierCode']) ? (string) $item['courierCode'] : null,
            statusCode: isset($item['statusCode']) ? (string) $item['statusCode'] : null,
            statusCategory: isset($item['statusCategory']) ? (string) $item['statusCategory'] : null,
            statusMilestone: isset($item['statusMilestone']) ? (string) $item['statusMilestone'] : null,
            status: isset($item['status']) ? (string) $item['status'] : null,
            datetime: isset($item['datetime']) ? (string) $item['datetime'] : null,
            hasNoTime: isset($item['hasNoTime']) ? (bool) $item['hasNoTime'] : null,
            utcOffset: isset($item['utcOffset']) ? (string) $item['utcOffset'] : null,
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
            'eventTrackingNumber' => $this->eventTrackingNumber,
            'occurrenceDatetime'  => $this->occurrenceDatetime,
            'order'               => $this->order,
            'location'            => $this->location,
            'sourceCode'          => $this->sourceCode,
            'courierCode'         => $this->courierCode,
            'statusCode'          => $this->statusCode,
            'statusCategory'      => $this->statusCategory,
            'statusMilestone'     => $this->statusMilestone,
            'status'              => $this->status,
            'datetime'            => $this->datetime,
            'hasNoTime'           => $this->hasNoTime,
            'utcOffset'           => $this->utcOffset,
        ];
    }
}
