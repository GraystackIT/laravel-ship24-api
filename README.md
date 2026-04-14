# graystackit/laravel-ship24-api

Laravel package for the [Ship24](https://www.ship24.com/) shipment tracking API, built on [Saloon 4](https://docs.saloon.dev/).

## Installation

```bash
composer require graystackit/laravel-ship24-api
```

Publish the config file:

```bash
php artisan vendor:publish --tag=ship24-config
```

Add your API key to `.env`:

```env
SHIP24_API_KEY=your-api-key-here
```

## Usage

Inject or resolve `Ship24Client` from the container:

```php
use GraystackIT\Ship24\Ship24Client;

$client = app(Ship24Client::class);
```

### Create a tracker

```php
$tracker = $client->createTracker('1Z999AA10123456784', 'ORDER-001');

echo $tracker->trackerId;       // trk_abc123
echo $tracker->trackingNumber;  // 1Z999AA10123456784
```

### Get tracking results by tracker ID

```php
$results = $client->getTrackingResults($tracker->trackerId);

foreach ($results as $result) {
    echo $result->shipment->statusMilestone; // delivered, in_transit, etc.
    echo $result->latestEvent()?->status;    // most recent event status
}
```

### Search by tracking number (one-shot)

No tracker ID required — useful for ad-hoc lookups:

```php
$results = $client->searchByTrackingNumber('JD014600006228974097');

foreach ($results as $result) {
    echo $result->shipment->currentCourierName;
    echo $result->shipment->originCountryCode;

    foreach ($result->events as $event) {
        echo $event->datetime.' - '.$event->status.' - '.$event->location;
    }
}
```

## Data objects

| Class | Key properties |
|---|---|
| `Tracker` | `trackerId`, `trackingNumber`, `shipmentReference`, `isSubscribed`, `createdAt` |
| `Shipment` | `shipmentId`, `trackingNumber`, `statusCode`, `statusCategory`, `statusMilestone`, `originCountryCode`, `destinationCountryCode`, `courierIds` |
| `TrackingEvent` | `eventId`, `trackingNumber`, `datetime`, `status`, `statusCode`, `statusCategory`, `statusMilestone`, `location` |
| `TrackingResult` | `tracker`, `shipment`, `events[]`, `latestEvent()` |

## Error handling

All API errors throw `GraystackIT\Ship24\Exceptions\Ship24ApiException`:

```php
use GraystackIT\Ship24\Exceptions\Ship24ApiException;

try {
    $results = $client->searchByTrackingNumber('INVALID');
} catch (Ship24ApiException $e) {
    logger()->error('Ship24 error: '.$e->getMessage());
}
```

## Testing

```bash
composer test
```

## Configuration

| Key | Env | Default |
|---|---|---|
| `api_key` | `SHIP24_API_KEY` | — |
| `base_url` | `SHIP24_BASE_URL` | `https://api.ship24.com/public/v1` |

## License

MIT
