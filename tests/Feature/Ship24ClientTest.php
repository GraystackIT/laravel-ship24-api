<?php

declare(strict_types=1);

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
use GraystackIT\Ship24\Ship24Client;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

// ── helpers ──────────────────────────────────────────────────────────────────

function makeTracker(string $id = 'trk_abc123', string $number = '1Z999AA10123456784'): array
{
    return [
        'trackerId'           => $id,
        'trackingNumber'      => $number,
        'shipmentReference'   => null,
        'createdAt'           => '2024-01-01T00:00:00Z',
        'isSubscribed'        => true,
        'isTracked'           => true,
        'activeUntilDatetime' => null,
    ];
}

function makeShipment(string $number = '1Z999AA10123456784'): array
{
    return [
        'shipmentId'             => 'shp_xyz',
        'trackingNumber'         => $number,
        'statusCode'             => 'delivery_delivered',
        'statusCategory'         => 'Delivered',
        'statusMilestone'        => 'delivered',
        'originCountryCode'      => 'US',
        'destinationCountryCode' => 'DE',
        'courierIds'             => ['ups'],
    ];
}

function makeTracking(string $trackerId = 'trk_abc123', string $number = '1Z999AA10123456784'): array
{
    return [
        'tracker'  => makeTracker($trackerId, $number),
        'shipment' => makeShipment($number),
        'events'   => [
            [
                'eventId'        => 'evt_1',
                'trackingNumber' => $number,
                'datetime'       => '2024-01-10T12:00:00Z',
                'status'         => 'Delivered',
                'statusCode'     => 'delivery_delivered',
                'statusCategory' => 'Delivered',
                'location'       => 'Berlin, DE',
            ],
        ],
    ];
}

// ── container resolution ─────────────────────────────────────────────────────

it('is resolved from the container', function () {
    expect(app(Ship24Client::class))->toBeInstanceOf(Ship24Client::class);
});

// ── createTracker ────────────────────────────────────────────────────────────

it('creates a tracker and returns a Tracker object', function () {
    $mockClient = new MockClient([
        CreateTrackerRequest::class => MockResponse::make([
            'data' => ['tracker' => makeTracker('trk_abc123', '1Z999AA10123456784') + ['shipmentReference' => 'ORDER-001']],
        ], 200),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    $tracker = (new Ship24Client($connector))->createTracker('1Z999AA10123456784', 'ORDER-001');

    expect($tracker)->toBeInstanceOf(Tracker::class)
        ->and($tracker->trackerId)->toBe('trk_abc123')
        ->and($tracker->trackingNumber)->toBe('1Z999AA10123456784')
        ->and($tracker->isSubscribed)->toBeTrue()
        ->and($tracker->isTracked)->toBeTrue();
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

// ── listTrackers ─────────────────────────────────────────────────────────────

it('lists trackers with pagination', function () {
    $mockClient = new MockClient([
        ListTrackersRequest::class => MockResponse::make([
            'data' => [
                'trackers'   => [makeTracker('trk_1'), makeTracker('trk_2')],
                'pagination' => ['total' => 50],
            ],
        ], 200),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    $result = (new Ship24Client($connector))->listTrackers(page: 1, limit: 2);

    expect($result['trackers'])->toHaveCount(2)
        ->and($result['trackers'][0])->toBeInstanceOf(Tracker::class)
        ->and($result['trackers'][0]->trackerId)->toBe('trk_1')
        ->and($result['total'])->toBe(50)
        ->and($result['page'])->toBe(1)
        ->and($result['limit'])->toBe(2);
});

it('returns empty trackers list when data is absent', function () {
    $mockClient = new MockClient([
        ListTrackersRequest::class => MockResponse::make(['data' => []], 200),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    $result = (new Ship24Client($connector))->listTrackers();

    expect($result['trackers'])->toBe([])
        ->and($result['total'])->toBeNull();
});

it('throws Ship24ApiException on 401 for listTrackers', function () {
    $mockClient = new MockClient([
        ListTrackersRequest::class => MockResponse::make(['error' => 'Unauthorized'], 401),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    expect(fn () => (new Ship24Client($connector))->listTrackers())
        ->toThrow(Ship24ApiException::class);
});

// ── bulkCreateTrackers ───────────────────────────────────────────────────────

it('bulk creates trackers and returns BulkCreateResult', function () {
    $mockClient = new MockClient([
        BulkCreateTrackersRequest::class => MockResponse::make([
            'data' => [
                'trackers' => [
                    'status'  => 'success',
                    'summary' => ['requested' => 2, 'success' => 2, 'error' => 0],
                    'items'   => [
                        ['success' => true, 'tracker' => makeTracker('trk_1', 'NUM1')],
                        ['success' => true, 'tracker' => makeTracker('trk_2', 'NUM2')],
                    ],
                ],
            ],
        ], 200),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    $result = (new Ship24Client($connector))->bulkCreateTrackers([
        ['trackingNumber' => 'NUM1'],
        ['trackingNumber' => 'NUM2'],
    ]);

    expect($result)->toBeInstanceOf(BulkCreateResult::class)
        ->and($result->status)->toBe('success')
        ->and($result->requested)->toBe(2)
        ->and($result->successCount)->toBe(2)
        ->and($result->errorCount)->toBe(0)
        ->and($result->items)->toHaveCount(2)
        ->and($result->items[0]->success)->toBeTrue()
        ->and($result->items[0]->tracker)->toBeInstanceOf(Tracker::class)
        ->and($result->items[0]->tracker->trackerId)->toBe('trk_1');
});

it('handles partial bulk create with errors', function () {
    $mockClient = new MockClient([
        BulkCreateTrackersRequest::class => MockResponse::make([
            'data' => [
                'trackers' => [
                    'status'  => 'partial',
                    'summary' => ['requested' => 2, 'success' => 1, 'error' => 1],
                    'items'   => [
                        ['success' => true, 'tracker' => makeTracker('trk_1', 'NUM1')],
                        ['success' => false, 'error' => ['code' => '400', 'message' => 'Invalid tracking number']],
                    ],
                ],
            ],
        ], 200),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    $result = (new Ship24Client($connector))->bulkCreateTrackers([
        ['trackingNumber' => 'NUM1'],
        ['trackingNumber' => 'BAD'],
    ]);

    expect($result->status)->toBe('partial')
        ->and($result->successCount)->toBe(1)
        ->and($result->errorCount)->toBe(1)
        ->and($result->items[1]->success)->toBeFalse()
        ->and($result->items[1]->tracker)->toBeNull()
        ->and($result->items[1]->errorMessage)->toBe('Invalid tracking number');
});

it('throws InvalidArgumentException when bulk create receives empty array', function () {
    $connector = app(Ship24Connector::class);

    expect(fn () => (new Ship24Client($connector))->bulkCreateTrackers([]))
        ->toThrow(\InvalidArgumentException::class);
});

it('throws InvalidArgumentException when bulk create exceeds 100 trackers', function () {
    $connector = app(Ship24Connector::class);

    $trackers = array_fill(0, 101, ['trackingNumber' => 'NUM']);

    expect(fn () => (new Ship24Client($connector))->bulkCreateTrackers($trackers))
        ->toThrow(\InvalidArgumentException::class);
});

it('throws Ship24ApiException on 400 for bulkCreateTrackers', function () {
    $mockClient = new MockClient([
        BulkCreateTrackersRequest::class => MockResponse::make(['error' => 'Bad Request'], 400),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    expect(fn () => (new Ship24Client($connector))->bulkCreateTrackers([['trackingNumber' => 'NUM1']]))
        ->toThrow(Ship24ApiException::class);
});

// ── createAndTrack ────────────────────────────────────────────────────────────

it('creates tracker and returns tracking result', function () {
    $mockClient = new MockClient([
        CreateAndTrackRequest::class => MockResponse::make([
            'data' => ['tracking' => makeTracking('trk_ct1', '1Z999AA10123456784')],
        ], 200),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    $result = (new Ship24Client($connector))->createAndTrack('1Z999AA10123456784', 'ORDER-042');

    expect($result)->toBeInstanceOf(TrackingResult::class)
        ->and($result->tracker->trackerId)->toBe('trk_ct1')
        ->and($result->shipment->statusMilestone)->toBe('delivered')
        ->and($result->events)->toHaveCount(1)
        ->and($result->latestEvent()->status)->toBe('Delivered');
});

it('createAndTrack result carries statistics when present', function () {
    $mockClient = new MockClient([
        CreateAndTrackRequest::class => MockResponse::make([
            'data' => [
                'tracking' => makeTracking() + ['statistics' => ['transitDays' => 5]],
            ],
        ], 200),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    $result = (new Ship24Client($connector))->createAndTrack('1Z999AA10123456784');

    expect($result->statistics)->toBe(['transitDays' => 5]);
});

it('throws Ship24ApiException on 404 for createAndTrack', function () {
    $mockClient = new MockClient([
        CreateAndTrackRequest::class => MockResponse::make(['error' => 'Not Found'], 404),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    expect(fn () => (new Ship24Client($connector))->createAndTrack('INVALID'))
        ->toThrow(Ship24ApiException::class);
});

// ── updateTracker ─────────────────────────────────────────────────────────────

it('updates tracker and returns updated Tracker', function () {
    $updated = array_merge(makeTracker(), ['isSubscribed' => false]);

    $mockClient = new MockClient([
        UpdateTrackerRequest::class => MockResponse::make([
            'data' => ['tracker' => $updated],
        ], 200),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    $tracker = (new Ship24Client($connector))->updateTracker('trk_abc123', ['isSubscribed' => false]);

    expect($tracker)->toBeInstanceOf(Tracker::class)
        ->and($tracker->trackerId)->toBe('trk_abc123')
        ->and($tracker->isSubscribed)->toBeFalse();
});

it('throws InvalidArgumentException when updateTracker receives empty updates', function () {
    $connector = app(Ship24Connector::class);

    expect(fn () => (new Ship24Client($connector))->updateTracker('trk_abc123', []))
        ->toThrow(\InvalidArgumentException::class);
});

it('throws Ship24ApiException on 404 for updateTracker', function () {
    $mockClient = new MockClient([
        UpdateTrackerRequest::class => MockResponse::make(['error' => 'Not Found'], 404),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    expect(fn () => (new Ship24Client($connector))->updateTracker('trk_missing', ['isSubscribed' => true]))
        ->toThrow(Ship24ApiException::class);
});

// ── getTrackingResultsByTrackingNumber ────────────────────────────────────────

it('returns TrackingResult array for getTrackingResultsByTrackingNumber', function () {
    $mockClient = new MockClient([
        GetTrackingByTrackingNumberRequest::class => MockResponse::make([
            'data' => ['trackings' => [makeTracking('trk_abc123', '1Z999AA10123456784')]],
        ], 200),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    $results = (new Ship24Client($connector))->getTrackingResultsByTrackingNumber('1Z999AA10123456784');

    expect($results)->toHaveCount(1)
        ->and($results[0])->toBeInstanceOf(TrackingResult::class)
        ->and($results[0]->tracker->trackingNumber)->toBe('1Z999AA10123456784')
        ->and($results[0]->shipment->statusCategory)->toBe('Delivered');
});

it('returns empty array when trackings absent for getTrackingResultsByTrackingNumber', function () {
    $mockClient = new MockClient([
        GetTrackingByTrackingNumberRequest::class => MockResponse::make(['data' => []], 200),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    $results = (new Ship24Client($connector))->getTrackingResultsByTrackingNumber('1Z999AA10123456784');

    expect($results)->toBe([]);
});

it('throws Ship24ApiException on 404 for getTrackingResultsByTrackingNumber', function () {
    $mockClient = new MockClient([
        GetTrackingByTrackingNumberRequest::class => MockResponse::make(['error' => 'Not Found'], 404),
    ]);

    $connector = app(Ship24Connector::class);
    $connector->withMockClient($mockClient);

    expect(fn () => (new Ship24Client($connector))->getTrackingResultsByTrackingNumber('NOTFOUND'))
        ->toThrow(Ship24ApiException::class);
});

// ── getTrackingResults ───────────────────────────────────────────────────────

it('returns TrackingResult array for getTrackingResults', function () {
    $mockClient = new MockClient([
        GetTrackingResultsRequest::class => MockResponse::make([
            'data' => ['trackings' => [makeTracking()]],
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
                        'tracker'  => makeTracker('trk_search1', 'JD014600006228974097'),
                        'shipment' => [
                            'shipmentId'      => 'shp_s1',
                            'trackingNumber'  => 'JD014600006228974097',
                            'statusMilestone' => 'in_transit',
                            'statusCategory'  => 'InTransit',
                            'statusCode'      => 'in_transit',
                            'courierIds'      => ['dhl'],
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
