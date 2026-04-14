<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Data;

class TrackingResult
{
    /**
     * @param TrackingEvent[] $events
     */
    public function __construct(
        public readonly Tracker $tracker,
        public readonly Shipment $shipment,
        public readonly array $events,
    ) {}

    /**
     * @param array<string, mixed> $item
     */
    public static function fromArray(array $item): self
    {
        $events = array_map(
            static fn (array $e) => TrackingEvent::fromArray($e),
            $item['events'] ?? []
        );

        return new self(
            tracker: Tracker::fromArray($item['tracker'] ?? []),
            shipment: Shipment::fromArray($item['shipment'] ?? []),
            events: $events,
        );
    }

    /**
     * Return the most recent event, or null if there are no events.
     */
    public function latestEvent(): ?TrackingEvent
    {
        return $this->events[0] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tracker'  => $this->tracker->toArray(),
            'shipment' => $this->shipment->toArray(),
            'events'   => array_map(static fn (TrackingEvent $e) => $e->toArray(), $this->events),
        ];
    }
}
