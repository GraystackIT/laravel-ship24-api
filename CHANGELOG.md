# Changelog

All notable changes to `graystackit/laravel-ship24-api` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Audited against Ship24's published OpenAPI 3.1 spec (`docs.ship24.com/assets/openapi/ship24-tracking-api.yaml`).

### Fixed
- `GetTrackerRequest`, `UpdateTrackerRequest`, `GetTrackingResultsRequest`, and
  `GetTrackingByTrackingNumberRequest` now `rawurlencode()` the `trackerId`/`trackingNumber`
  path segment before building the endpoint. Ship24's own tracking-number pattern
  (`^[a-zA-Z0-9-_/.]*$`) explicitly allows `/` and `.` — an unencoded value containing `/`
  would corrupt the request path instead of being treated as a single path segment.
- Corrected the `config/ship24.php` webhook-secret comment and added a docblock to
  `Ship24WebhookController::signatureValid()`: Ship24's webhook auth is a plain
  `Authorization: Bearer {secret}` string comparison, not an HMAC-SHA256 signature as the
  comment previously claimed — the actual `hash_equals()` check was already correct.

### Added
- `SearchTrackingRequest` / `Ship24Client::searchByTrackingNumber()`: new optional
  `originCountryCode`, `destinationCountryCode`, `destinationPostCode`, `shippingDate`,
  `courierCode` parameters — all documented as "recommended to improve tracking accuracy" for
  this endpoint and previously unavailable (only `trackingNumber` could be sent).
- `CreateAndTrackRequest` / `Ship24Client::createAndTrack()`: new optional `clientTrackerId`,
  `courierName`, `trackingUrl`, `orderNumber`, `title`, `recipientEmail`, `recipientName`,
  `restrictTrackingToCourierCode` parameters, bringing this endpoint to parity with
  `createTracker()` — Ship24's `/trackers/track` accepts the exact same request schema as
  `/trackers`. **Breaking for positional (non-named-argument) callers only:**
  `$clientTrackerId` was inserted right after `$shipmentReference`, matching `createTracker()`'s
  existing parameter order, shifting every parameter after it by one position.
- `Delivery::$aiPredictiveDeliveryFrom` / `$aiPredictiveDeliveryTo` — Ship24's AI Predictive
  Delivery Date add-on field, absent unless subscribed.

### Changed
- `Ship24Client::updateTracker()`'s PHPDoc now lists the full set of updatable fields
  (`courierName`, `trackingUrl`, `recipient.name` were missing) and notes that `courierCode`,
  `originCountryCode`, `destinationCountryCode`, and `shippingDate` become read-only once a
  tracker has gathered shipment data. The code itself was never restricted to the old list —
  this is a documentation correction only.

## [1.0.0] - 2025-04-07

### Added
- Initial release
- `Ship24Client` with `createTracker()`, `getTrackingResults()`, and `searchByTrackingNumber()` methods
- `Tracker`, `Shipment`, `TrackingEvent`, and `TrackingResult` data objects
- Bearer token authentication via Ship24 API key
- Laravel service provider with auto-discovery
- Config validation: clear `RuntimeException` when `SHIP24_API_KEY` is missing
