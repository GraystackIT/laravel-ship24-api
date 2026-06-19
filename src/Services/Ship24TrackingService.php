<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Services;

use GraystackIT\Ship24\Data\TrackingResult;
use GraystackIT\Ship24\Models\Ship24Tracking;
use GraystackIT\Ship24\Ship24Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Synchronises Ship24 API tracking results with local Ship24Tracking records.
 */
class Ship24TrackingService
{
    /**
     * @param Ship24Client $client
     */
    public function __construct(private readonly Ship24Client $client) {}

    /**
     * Create or update a Ship24Tracking record for a given trackable model, then persist API results.
     *
     * @param  Model          $trackable
     * @param  string         $trackingNumber
     * @param  TrackingResult $result
     * @return Ship24Tracking
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
     *
     * @param  Ship24Tracking $tracking
     * @param  TrackingResult $result
     * @return void
     */
    public function applyResult(Ship24Tracking $tracking, TrackingResult $result): void
    {
        $shipment = $result->shipment;
        $latest   = $result->latestEvent();

        $tracking->tracker_id = $result->tracker->trackerId;

        // Derive carrier from the latest event's courierCode, falling back to the tracker's own courierCode.
        $tracking->carrier_id   = $latest?->courierCode ?? ($result->tracker->courierCode[0] ?? null);
        $tracking->carrier_name = null;

        $tracking->status_code      = $shipment->statusCode;
        $tracking->status_category  = $shipment->statusCategory;
        $tracking->status_milestone = $shipment->statusMilestone;
        $tracking->raw_shipment     = $shipment->toArray();

        if ($latest !== null) {
            // occurrenceDatetime is the canonical field; datetime is deprecated but kept as fallback.
            $tracking->latest_event_at       = $latest->occurrenceDatetime ?? $latest->datetime;
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
     *
     * @param  Ship24Tracking $tracking
     * @return Ship24Tracking
     *
     * @throws \GraystackIT\Ship24\Exceptions\Ship24ApiException
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
        $trackings = $payload['trackings'] ?? [];

        if (empty($trackings)) {
            return 0;
        }

        $totalUpdated = 0;

        foreach ($trackings as $item) {
            $trackerData    = $item['tracker'] ?? [];
            $trackingNumber = $trackerData['trackingNumber'] ?? null;
            $trackerId      = $trackerData['trackerId'] ?? null;

            if (! $trackingNumber && ! $trackerId) {
                continue;
            }

            $records = Ship24Tracking::query()
                ->when($trackingNumber, fn ($q) => $q->where('tracking_number', $trackingNumber))
                ->when($trackerId && ! $trackingNumber, fn ($q) => $q->orWhere('tracker_id', $trackerId))
                ->get();

            if ($records->isEmpty()) {
                continue;
            }

            $result = TrackingResult::fromArray([
                'tracker'    => array_merge(
                    [
                        'trackerId'      => $trackerId ?? '',
                        'trackingNumber' => $trackingNumber ?? '',
                        'createdAt'      => now()->toIso8601String(),
                        'isSubscribed'   => true,
                    ],
                    $trackerData,
                ),
                'shipment'   => $item['shipment'] ?? [],
                'events'     => $item['events'] ?? [],
                'statistics' => $item['statistics'] ?? null,
            ]);

            foreach ($records as $record) {
                $this->applyResult($record, $result);
            }

            Log::info('Ship24: webhook synced tracking records', [
                'trackingNumber' => $trackingNumber,
                'trackerId'      => $trackerId,
                'updated'        => $records->count(),
            ]);

            $totalUpdated += $records->count();
        }

        return $totalUpdated;
    }
}
