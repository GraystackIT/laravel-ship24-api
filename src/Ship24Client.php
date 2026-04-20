<?php

declare(strict_types=1);

namespace GraystackIT\Ship24;

use GraystackIT\Ship24\Connectors\Ship24Connector;
use GraystackIT\Ship24\Data\BulkCreateResult;
use GraystackIT\Ship24\Data\Tracker;
use GraystackIT\Ship24\Data\TrackingResult;
use GraystackIT\Ship24\Exceptions\Ship24ApiException;
use GraystackIT\Ship24\Requests\BulkCreateTrackersRequest;
use GraystackIT\Ship24\Requests\CreateAndTrackRequest;
use GraystackIT\Ship24\Requests\CreateTrackerRequest;
use GraystackIT\Ship24\Requests\GetTrackingByTrackingNumberRequest;
use GraystackIT\Ship24\Requests\GetTrackingResultsRequest;
use GraystackIT\Ship24\Requests\ListTrackersRequest;
use GraystackIT\Ship24\Requests\SearchTrackingRequest;
use GraystackIT\Ship24\Requests\UpdateTrackerRequest;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\RequestException;

class Ship24Client
{
    public function __construct(private readonly Ship24Connector $connector) {}

    /**
     * Create a tracker for a given tracking number.
     *
     * @throws Ship24ApiException
     */
    public function createTracker(string $trackingNumber, ?string $shipmentReference = null): Tracker
    {
        Log::info('Ship24: creating tracker', ['trackingNumber' => $trackingNumber]);

        try {
            $response = $this->connector->send(new CreateTrackerRequest($trackingNumber, $shipmentReference));
        } catch (RequestException $e) {
            Log::error('Ship24: createTracker failed', [
                'trackingNumber' => $trackingNumber,
                'status'         => $e->getResponse()->status(),
                'body'           => substr($e->getResponse()->body(), 0, 500),
            ]);

            throw new Ship24ApiException(
                "Ship24 API returned HTTP {$e->getResponse()->status()} for createTracker: {$trackingNumber}",
                $e->getResponse()->status(),
                $e
            );
        } catch (\Throwable $e) {
            Log::error('Ship24: unexpected error in createTracker', ['message' => $e->getMessage()]);

            throw new Ship24ApiException("Ship24 createTracker failed: {$e->getMessage()}", 0, $e);
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new Ship24ApiException('Ship24 API returned a non-JSON response for createTracker.');
        }

        $tracker = Tracker::fromArray($data['data']['tracker'] ?? []);

        Log::info('Ship24: tracker created', ['trackerId' => $tracker->trackerId]);

        return $tracker;
    }

    /**
     * List all trackers with pagination.
     * Returns ['trackers' => Tracker[], 'total' => int|null, 'page' => int, 'limit' => int].
     *
     * @param int      $page  Page number (minimum 1)
     * @param int      $limit Results per page (1–500)
     * @param int|null $sort  Sort by createdAt: 1 = ascending, -1 = descending
     *
     * @return array{trackers: Tracker[], total: int|null, page: int, limit: int}
     *
     * @throws Ship24ApiException
     */
    public function listTrackers(int $page = 1, int $limit = 20, ?int $sort = null): array
    {
        Log::info('Ship24: listing trackers', ['page' => $page, 'limit' => $limit]);

        try {
            $response = $this->connector->send(new ListTrackersRequest($page, $limit, $sort));
        } catch (RequestException $e) {
            Log::error('Ship24: listTrackers failed', [
                'status' => $e->getResponse()->status(),
                'body'   => substr($e->getResponse()->body(), 0, 500),
            ]);

            throw new Ship24ApiException(
                "Ship24 API returned HTTP {$e->getResponse()->status()} for listTrackers",
                $e->getResponse()->status(),
                $e
            );
        } catch (\Throwable $e) {
            Log::error('Ship24: unexpected error in listTrackers', ['message' => $e->getMessage()]);

            throw new Ship24ApiException("Ship24 listTrackers failed: {$e->getMessage()}", 0, $e);
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new Ship24ApiException('Ship24 API returned a non-JSON response for listTrackers.');
        }

        $trackers = array_map(
            static fn (array $item) => Tracker::fromArray($item),
            $data['data']['trackers'] ?? []
        );

        $pagination = $data['data']['pagination'] ?? [];

        Log::info('Ship24: trackers listed', ['count' => count($trackers)]);

        return [
            'trackers' => $trackers,
            'total'    => isset($pagination['total']) ? (int) $pagination['total'] : null,
            'page'     => $page,
            'limit'    => $limit,
        ];
    }

    /**
     * Bulk-create up to 100 trackers in a single request.
     *
     * @param array<int, array<string, mixed>> $trackers Array of tracker-create objects.
     *                                                    Each must have 'trackingNumber'.
     *
     * @throws \InvalidArgumentException If the array is empty or exceeds 100 items.
     * @throws Ship24ApiException
     */
    public function bulkCreateTrackers(array $trackers): BulkCreateResult
    {
        if (count($trackers) === 0) {
            throw new \InvalidArgumentException('Ship24 bulkCreateTrackers requires at least 1 tracker.');
        }

        if (count($trackers) > 100) {
            throw new \InvalidArgumentException('Ship24 bulkCreateTrackers accepts at most 100 trackers per request.');
        }

        Log::info('Ship24: bulk creating trackers', ['count' => count($trackers)]);

        try {
            $response = $this->connector->send(new BulkCreateTrackersRequest($trackers));
        } catch (RequestException $e) {
            Log::error('Ship24: bulkCreateTrackers failed', [
                'status' => $e->getResponse()->status(),
                'body'   => substr($e->getResponse()->body(), 0, 500),
            ]);

            throw new Ship24ApiException(
                "Ship24 API returned HTTP {$e->getResponse()->status()} for bulkCreateTrackers",
                $e->getResponse()->status(),
                $e
            );
        } catch (\Throwable $e) {
            Log::error('Ship24: unexpected error in bulkCreateTrackers', ['message' => $e->getMessage()]);

            throw new Ship24ApiException("Ship24 bulkCreateTrackers failed: {$e->getMessage()}", 0, $e);
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new Ship24ApiException('Ship24 API returned a non-JSON response for bulkCreateTrackers.');
        }

        $result = BulkCreateResult::fromArray($data['data']['trackers'] ?? []);

        Log::info('Ship24: bulk create completed', [
            'status'       => $result->status,
            'successCount' => $result->successCount,
            'errorCount'   => $result->errorCount,
        ]);

        return $result;
    }

    /**
     * Create a tracker and immediately retrieve its tracking results in one API call.
     *
     * @throws Ship24ApiException
     */
    public function createAndTrack(
        string $trackingNumber,
        ?string $shipmentReference = null,
        ?string $originCountryCode = null,
        ?string $destinationCountryCode = null,
        ?string $destinationPostCode = null,
        ?string $shippingDate = null,
        ?array $courierCode = null,
    ): TrackingResult {
        Log::info('Ship24: create and track', ['trackingNumber' => $trackingNumber]);

        try {
            $response = $this->connector->send(new CreateAndTrackRequest(
                trackingNumber: $trackingNumber,
                shipmentReference: $shipmentReference,
                originCountryCode: $originCountryCode,
                destinationCountryCode: $destinationCountryCode,
                destinationPostCode: $destinationPostCode,
                shippingDate: $shippingDate,
                courierCode: $courierCode,
            ));
        } catch (RequestException $e) {
            Log::error('Ship24: createAndTrack failed', [
                'trackingNumber' => $trackingNumber,
                'status'         => $e->getResponse()->status(),
                'body'           => substr($e->getResponse()->body(), 0, 500),
            ]);

            throw new Ship24ApiException(
                "Ship24 API returned HTTP {$e->getResponse()->status()} for createAndTrack: {$trackingNumber}",
                $e->getResponse()->status(),
                $e
            );
        } catch (\Throwable $e) {
            Log::error('Ship24: unexpected error in createAndTrack', ['message' => $e->getMessage()]);

            throw new Ship24ApiException("Ship24 createAndTrack failed: {$e->getMessage()}", 0, $e);
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new Ship24ApiException('Ship24 API returned a non-JSON response for createAndTrack.');
        }

        $tracking = $data['data']['tracking'] ?? [];
        $result   = TrackingResult::fromArray($tracking);

        Log::info('Ship24: create and track completed', ['trackerId' => $result->tracker->trackerId]);

        return $result;
    }

    /**
     * Update an existing tracker's attributes.
     *
     * @param array<string, mixed> $updates Updatable fields: isSubscribed, courierCode,
     *                                       originCountryCode, destinationCountryCode,
     *                                       destinationPostCode, shippingDate
     *
     * @throws \InvalidArgumentException If updates array is empty.
     * @throws Ship24ApiException
     */
    public function updateTracker(string $trackerId, array $updates): Tracker
    {
        if (count($updates) === 0) {
            throw new \InvalidArgumentException('Ship24 updateTracker requires at least one field to update.');
        }

        Log::info('Ship24: updating tracker', ['trackerId' => $trackerId, 'fields' => array_keys($updates)]);

        try {
            $response = $this->connector->send(new UpdateTrackerRequest($trackerId, $updates));
        } catch (RequestException $e) {
            Log::error('Ship24: updateTracker failed', [
                'trackerId' => $trackerId,
                'status'    => $e->getResponse()->status(),
                'body'      => substr($e->getResponse()->body(), 0, 500),
            ]);

            throw new Ship24ApiException(
                "Ship24 API returned HTTP {$e->getResponse()->status()} for updateTracker: {$trackerId}",
                $e->getResponse()->status(),
                $e
            );
        } catch (\Throwable $e) {
            Log::error('Ship24: unexpected error in updateTracker', ['message' => $e->getMessage()]);

            throw new Ship24ApiException("Ship24 updateTracker failed: {$e->getMessage()}", 0, $e);
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new Ship24ApiException('Ship24 API returned a non-JSON response for updateTracker.');
        }

        $tracker = Tracker::fromArray($data['data']['tracker'] ?? []);

        Log::info('Ship24: tracker updated', ['trackerId' => $tracker->trackerId]);

        return $tracker;
    }

    /**
     * Get tracking results for an existing tracker by its tracking number.
     * Useful when you have the tracking number but not the tracker ID.
     *
     * @return TrackingResult[]
     *
     * @throws Ship24ApiException
     */
    public function getTrackingResultsByTrackingNumber(string $trackingNumber): array
    {
        Log::info('Ship24: fetching tracking results by tracking number', ['trackingNumber' => $trackingNumber]);

        try {
            $response = $this->connector->send(new GetTrackingByTrackingNumberRequest($trackingNumber));
        } catch (RequestException $e) {
            Log::error('Ship24: getTrackingResultsByTrackingNumber failed', [
                'trackingNumber' => $trackingNumber,
                'status'         => $e->getResponse()->status(),
                'body'           => substr($e->getResponse()->body(), 0, 500),
            ]);

            throw new Ship24ApiException(
                "Ship24 API returned HTTP {$e->getResponse()->status()} for getTrackingResultsByTrackingNumber: {$trackingNumber}",
                $e->getResponse()->status(),
                $e
            );
        } catch (\Throwable $e) {
            Log::error('Ship24: unexpected error in getTrackingResultsByTrackingNumber', ['message' => $e->getMessage()]);

            throw new Ship24ApiException("Ship24 getTrackingResultsByTrackingNumber failed: {$e->getMessage()}", 0, $e);
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new Ship24ApiException('Ship24 API returned a non-JSON response for getTrackingResultsByTrackingNumber.');
        }

        $results = array_map(
            static fn (array $item) => TrackingResult::fromArray($item),
            $data['data']['trackings'] ?? []
        );

        Log::info('Ship24: tracking results by number fetched', [
            'trackingNumber' => $trackingNumber,
            'count'          => count($results),
        ]);

        return $results;
    }

    /**
     * Get the latest tracking results for an existing tracker by its ID.
     *
     * @return TrackingResult[]
     *
     * @throws Ship24ApiException
     */
    public function getTrackingResults(string $trackerId): array
    {
        Log::info('Ship24: fetching tracking results', ['trackerId' => $trackerId]);

        try {
            $response = $this->connector->send(new GetTrackingResultsRequest($trackerId));
        } catch (RequestException $e) {
            Log::error('Ship24: getTrackingResults failed', [
                'trackerId' => $trackerId,
                'status'    => $e->getResponse()->status(),
                'body'      => substr($e->getResponse()->body(), 0, 500),
            ]);

            throw new Ship24ApiException(
                "Ship24 API returned HTTP {$e->getResponse()->status()} for getTrackingResults: {$trackerId}",
                $e->getResponse()->status(),
                $e
            );
        } catch (\Throwable $e) {
            Log::error('Ship24: unexpected error in getTrackingResults', ['message' => $e->getMessage()]);

            throw new Ship24ApiException("Ship24 getTrackingResults failed: {$e->getMessage()}", 0, $e);
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new Ship24ApiException('Ship24 API returned a non-JSON response for getTrackingResults.');
        }

        $results = array_map(
            static fn (array $item) => TrackingResult::fromArray($item),
            $data['data']['trackings'] ?? []
        );

        Log::info('Ship24: tracking results fetched', ['trackerId' => $trackerId, 'count' => count($results)]);

        return $results;
    }

    /**
     * Instantly search for tracking info by tracking number (no tracker ID required).
     * Uses the per-call plan endpoint — does not create a persistent tracker.
     *
     * @return TrackingResult[]
     *
     * @throws Ship24ApiException
     */
    public function searchByTrackingNumber(string $trackingNumber): array
    {
        Log::info('Ship24: searching by tracking number', ['trackingNumber' => $trackingNumber]);

        try {
            $response = $this->connector->send(new SearchTrackingRequest($trackingNumber));
        } catch (RequestException $e) {
            Log::error('Ship24: searchByTrackingNumber failed', [
                'trackingNumber' => $trackingNumber,
                'status'         => $e->getResponse()->status(),
                'body'           => substr($e->getResponse()->body(), 0, 500),
            ]);

            throw new Ship24ApiException(
                "Ship24 API returned HTTP {$e->getResponse()->status()} for searchByTrackingNumber: {$trackingNumber}",
                $e->getResponse()->status(),
                $e
            );
        } catch (\Throwable $e) {
            Log::error('Ship24: unexpected error in searchByTrackingNumber', ['message' => $e->getMessage()]);

            throw new Ship24ApiException("Ship24 searchByTrackingNumber failed: {$e->getMessage()}", 0, $e);
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new Ship24ApiException('Ship24 API returned a non-JSON response for searchByTrackingNumber.');
        }

        $results = array_map(
            static fn (array $item) => TrackingResult::fromArray($item),
            $data['data']['trackings'] ?? []
        );

        Log::info('Ship24: search completed', ['trackingNumber' => $trackingNumber, 'count' => count($results)]);

        return $results;
    }
}
