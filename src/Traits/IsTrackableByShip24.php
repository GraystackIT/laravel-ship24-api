<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Traits;

use GraystackIT\Ship24\Models\Ship24Tracking;
use GraystackIT\Ship24\Services\Ship24TrackingService;
use GraystackIT\Ship24\Ship24Client;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Add Ship24 tracking capabilities to any Eloquent model.
 *
 * Usage:
 *   class Order extends Model {
 *       use IsTrackableByShip24;
 *   }
 */
trait IsTrackableByShip24
{
    /**
     * All Ship24 tracking records linked to this model.
     */
    public function trackings(): MorphMany
    {
        return $this->morphMany(Ship24Tracking::class, 'trackable');
    }

    /**
     * The single most-recently-updated tracking record for this model,
     * or null if no tracking has been started yet.
     */
    public function latestTrackingStatus(): ?Ship24Tracking
    {
        return $this->trackings()
            ->orderByDesc('latest_event_at')
            ->first();
    }

    /**
     * Create a Ship24 tracker for this model and immediately persist the tracking result.
     * Uses the createAndTrack() API call (single request).
     */
    public function startTracking(string $trackingNumber, ?string $shipmentReference = null): Ship24Tracking
    {
        $result = app(Ship24Client::class)->createAndTrack(
            trackingNumber: $trackingNumber,
            shipmentReference: $shipmentReference,
        );

        return app(Ship24TrackingService::class)
            ->createOrUpdateFromResult($this, $trackingNumber, $result);
    }

    /**
     * Fetch the latest tracking data from Ship24 for a given tracking number
     * and update (or create) the local Ship24Tracking record.
     */
    public function syncTracking(string $trackingNumber): Ship24Tracking
    {
        $results = app(Ship24Client::class)
            ->getTrackingResultsByTrackingNumber($trackingNumber);

        if (empty($results)) {
            /** @var Ship24Tracking $record */
            $record = $this->trackings()->firstOrCreate([
                'trackable_id'    => $this->getKey(),
                'trackable_type'  => $this->getMorphClass(),
                'tracking_number' => $trackingNumber,
            ]);

            return $record;
        }

        return app(Ship24TrackingService::class)
            ->createOrUpdateFromResult($this, $trackingNumber, $results[0]);
    }
}
