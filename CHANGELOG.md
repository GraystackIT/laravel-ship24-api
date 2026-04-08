# Changelog

All notable changes to `graystack/ship24` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-04-07

### Added
- Initial release
- `Ship24Client` with `createTracker()`, `getTrackingResults()`, and `searchByTrackingNumber()` methods
- `Tracker`, `Shipment`, `TrackingEvent`, and `TrackingResult` data objects
- Bearer token authentication via Ship24 API key
- Laravel service provider with auto-discovery
- Config validation: clear `RuntimeException` when `SHIP24_API_KEY` is missing
