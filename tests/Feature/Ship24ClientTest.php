<?php

declare(strict_types=1);

use Graystack\Ship24\Connectors\Ship24Connector;
use Graystack\Ship24\Data\Tracker;
use Graystack\Ship24\Data\TrackingResult;
use Graystack\Ship24\Exceptions\Ship24ApiException;
use Graystack\Ship24\Requests\CreateTrackerRequest;
use Graystack\Ship24\Requests\GetTrackingResultsRequest;
use Graystack\Ship24\Requests\SearchTrackingRequest;
use Graystack\Ship24\Ship24Client;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('is resolved from the container', function () {
    expect(app(Ship24Client::class))->toBeInstanceOf(Ship24Client::class);
});

// ── createTracker ────────────────────────────────────────────────────────────

it('creates a tracker and returns a Tracker object', function () {
    $mockClient = new MockClient([
        CreateTrackerRequest::class => MockResponse::make([
            'data' => [
                'tracker' => [
                    'trackerId'           => 'trk_abc123',
                    'trackingNumber'      => '1Z999AA10123456784',
                    'shipmentReference'   => 'ORDER-001',
                    'createdAt'           => '2024-01-01T00:00:00Z',
                    'isSubscribed'        => true,
                    'activeUntilDatetime' => '2024-02-01T00:00:00Z',
                ],
            ],
        ], 200),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    $tracker = (new Ship24Client($connector))->createTracker('1Z999AA10123456784', 'ORDER-001');

    expect($tracker)->toBeInstanceOf(Tracker::class)
        ->and($tracker->trackerId)->toBe('trk_abc123')
        ->and($tracker->trackingNumber)->toBe('1Z999AA10123456784')
        ->and($tracker->shipmentReference)->toBe('ORDER-001')
        ->and($tracker->isSubscribed)->toBeTrue();
});

it('throws Ship24ApiException on 401 for createTracker', function () {
    $mockClient = new MockClient([
        CreateTrackerRequest::class => MockResponse::make(['error' => 'Unauthorized'], 401),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    expect(fn () => (new Ship24Client($connector))->createTracker('INVALID'))
        ->toThrow(Ship24ApiException::class);
});

// ── getTrackingResults ───────────────────────────────────────────────────────

it('returns TrackingResult array for getTrackingResults', function () {
    $mockClient = new MockClient([
        GetTrackingResultsRequest::class => MockResponse::make([
            'data' => [
                'trackings' => [
                    [
                        'tracker'  => [
                            'trackerId'           => 'trk_abc123',
                            'trackingNumber'      => '1Z999AA10123456784',
                            'shipmentReference'   => null,
                            'createdAt'           => '2024-01-01T00:00:00Z',
                            'isSubscribed'        => true,
                            'activeUntilDatetime' => null,
                        ],
                        'shipment' => [
                            'shipmentId'             => 'shp_xyz',
                            'trackingNumber'         => '1Z999AA10123456784',
                            'statusCode'             => 'delivery_delivered',
                            'statusCategory'         => 'Delivered',
                            'statusMilestone'        => 'delivered',
                            'originCountryCode'      => 'US',
                            'destinationCountryCode' => 'DE',
                            'courierIds'             => ['ups'],
                        ],
                        'events'   => [
                            [
                                'eventId'        => 'evt_1',
                                'trackingNumber' => '1Z999AA10123456784',
                                'datetime'       => '2024-01-10T12:00:00Z',
                                'status'         => 'Delivered',
                                'statusCode'     => 'delivery_delivered',
                                'statusCategory' => 'Delivered',
                                'location'       => 'Berlin, DE',
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    $results = (new Ship24Client($connector))->getTrackingResults('trk_abc123');

    expect($results)->toHaveCount(1)
        ->and($results[0])->toBeInstanceOf(TrackingResult::class)
        ->and($results[0]->tracker->trackerId)->toBe('trk_abc123')
        ->and($results[0]->shipment->statusCategory)->toBe('Delivered')
        ->and($results[0]->events)->toHaveCount(1)
        ->and($results[0]->latestEvent()->status)->toBe('Delivered');
});

it('returns empty array when trackings key is absent for getTrackingResults', function () {
    $mockClient = new MockClient([
        GetTrackingResultsRequest::class => MockResponse::make(['data' => []], 200),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    $results = (new Ship24Client($connector))->getTrackingResults('trk_abc123');

    expect($results)->toBe([]);
});

it('throws Ship24ApiException on 404 for getTrackingResults', function () {
    $mockClient = new MockClient([
        GetTrackingResultsRequest::class => MockResponse::make(['error' => 'Not Found'], 404),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    expect(fn () => (new Ship24Client($connector))->getTrackingResults('trk_missing'))
        ->toThrow(Ship24ApiException::class);
});

// ── searchByTrackingNumber ───────────────────────────────────────────────────

it('returns TrackingResult array for searchByTrackingNumber', function () {
    $mockClient = new MockClient([
        SearchTrackingRequest::class => MockResponse::make([
            'data' => [
                'trackings' => [
                    [
                        'tracker'  => [
                            'trackerId'           => 'trk_search1',
                            'trackingNumber'      => 'JD014600006228974097',
                            'shipmentReference'   => null,
                            'createdAt'           => '2024-01-01T00:00:00Z',
                            'isSubscribed'        => false,
                            'activeUntilDatetime' => null,
                        ],
                        'shipment' => [
                            'shipmentId'             => 'shp_s1',
                            'trackingNumber'         => 'JD014600006228974097',
                            'statusMilestone'        => 'in_transit',
                            'statusCategory'         => 'InTransit',
                            'statusCode'             => 'in_transit',
                            'courierIds'             => ['dhl'],
                        ],
                        'events'   => [],
                    ],
                ],
            ],
        ], 200),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    $results = (new Ship24Client($connector))->searchByTrackingNumber('JD014600006228974097');

    expect($results)->toHaveCount(1)
        ->and($results[0])->toBeInstanceOf(TrackingResult::class)
        ->and($results[0]->shipment->statusMilestone)->toBe('in_transit')
        ->and($results[0]->latestEvent())->toBeNull();
});

it('throws Ship24ApiException on 429 for searchByTrackingNumber', function () {
    $mockClient = new MockClient([
        SearchTrackingRequest::class => MockResponse::make(['error' => 'Too Many Requests'], 429),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    expect(fn () => (new Ship24Client($connector))->searchByTrackingNumber('SOMENUM'))
        ->toThrow(Ship24ApiException::class);
});
