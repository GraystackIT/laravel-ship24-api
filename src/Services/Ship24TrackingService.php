<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Services;

use GraystackIT\Ship24\Data\TrackingResult;
use GraystackIT\Ship24\Models\Ship24Tracking;
use GraystackIT\Ship24\Ship24Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Ship24TrackingService
{
    public function __construct(private readonly Ship24Client $client) {}

    /**
     * Create or update a Ship24Tracking record for a given trackable model, then persist API results.
     */
    public function createOrUpdateFromResult(
        Model $trackable,
        string $trackingNumber,
        TrackingResult $result,
    ): Ship24Tracking {
        /** @var Ship24Tracking $tracking */
        $tracking = Ship24Tracking::firstOrNew([
            'trackable_id'    => $trackable->getKey(),
            'trackable_type'  => $trackable->getMorphClass(),
            'tracking_number' => $trackingNumber,
        ]);

        $this->applyResult($tracking, $result);

        return $tracking;
    }

    /**
     * Apply a TrackingResult to a Ship24Tracking record and persist it.
     * Respects config('ship24.tracking_mode'): 'latest' clears events, 'history' stores them.
     */
    public function applyResult(Ship24Tracking $tracking, TrackingResult $result): void
    {
        $shipment = $result->shipment;
        $latest   = $result->latestEvent();

        $tracking->tracker_id       = $result->tracker->trackerId;
        $tracking->carrier_id       = $shipment->currentCourierId;
        $tracking->carrier_name     = $shipment->currentCourierName;
        $tracking->status_code      = $shipment->statusCode;
        $tracking->status_category  = $shipment->statusCategory;
        $tracking->status_milestone = $shipment->statusMilestone;
        $tracking->raw_shipment     = $shipment->toArray();

        if ($latest !== null) {
            $tracking->latest_event_at       = $latest->datetime;
            $tracking->latest_event_status   = $latest->status;
            $tracking->latest_event_location = $latest->location;
        }

        if (config('ship24.tracking_mode', 'latest') === 'history') {
            $tracking->events = array_map(
                static fn ($e) => $e->toArray(),
                $result->events,
            );
        } else {
            $tracking->events = null;
        }

        $tracking->save();
    }

    /**
     * Re-fetch tracking data from the Ship24 API and update the local record.
     */
    public function refresh(Ship24Tracking $tracking): Ship24Tracking
    {
        Log::info('Ship24: refreshing tracking record', [
            'id'             => $tracking->id,
            'trackingNumber' => $tracking->tracking_number,
        ]);

        $results = $this->client->getTrackingResultsByTrackingNumber($tracking->tracking_number);

        if (! empty($results)) {
            $this->applyResult($tracking, $results[0]);
        }

        return $tracking;
    }

    /**
     * Parse a raw Ship24 webhook payload and update all matching local tracking records.
     *
     * @param  array<string, mixed> $payload
     * @return int Number of records updated
     */
    public function syncFromWebhookPayload(array $payload): int
    {
        $trackingNumber = $payload['trackingNumber'] ?? null;
        $trackerId      = $payload['trackerId'] ?? null;

        if (! $trackingNumber && ! $trackerId) {
            return 0;
        }

        $records = Ship24Tracking::query()
            ->when($trackingNumber, fn ($q) => $q->where('tracking_number', $trackingNumber))
            ->when($trackerId && ! $trackingNumber, fn ($q) => $q->orWhere('tracker_id', $trackerId))
            ->get();

        if ($records->isEmpty()) {
            return 0;
        }

        // Build a TrackingResult from the webhook payload, merging top-level fields into the tracker stub.
        $result = TrackingResult::fromArray([
            'tracker'    => array_merge(
                [
                    'trackerId'      => $trackerId ?? '',
                    'trackingNumber' => $trackingNumber ?? '',
                    'createdAt'      => now()->toIso8601String(),
                    'isSubscribed'   => true,
                ],
                $payload['tracker'] ?? [],
            ),
            'shipment'   => $payload['shipment'] ?? [],
            'events'     => $payload['events'] ?? [],
            'statistics' => $payload['statistics'] ?? null,
        ]);

        foreach ($records as $record) {
            $this->applyResult($record, $result);
        }

        Log::info('Ship24: webhook synced tracking records', [
            'trackingNumber' => $trackingNumber,
            'trackerId'      => $trackerId,
            'updated'        => $records->count(),
        ]);

        return $records->count();
    }
}
