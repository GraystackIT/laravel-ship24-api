<?php

declare(strict_types=1);

namespace Graystack\Ship24;

use Graystack\Ship24\Connectors\Ship24Connector;
use Graystack\Ship24\Data\Tracker;
use Graystack\Ship24\Data\TrackingResult;
use Graystack\Ship24\Exceptions\Ship24ApiException;
use Graystack\Ship24\Requests\CreateTrackerRequest;
use Graystack\Ship24\Requests\GetTrackingResultsRequest;
use Graystack\Ship24\Requests\SearchTrackingRequest;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\RequestException;

class Ship24Client
{
    public function __construct(private readonly Ship24Connector $connector) {}

    /**
     * Create a tracker for a given tracking number.
     * Returns the created Tracker object.
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
     * Combines createTracker + getTrackingResults in one convenience call.
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
