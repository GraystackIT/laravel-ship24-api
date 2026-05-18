<?php

declare(strict_types=1);

use GraystackIT\Ship24\Services\Ship24TrackingService;

// ── controller auth ───────────────────────────────────────────────────────────

it('accepts webhook when no secret is configured', function () {
    config(['ship24.webhook.secret' => null]);

    $this->postJson('/ship24/webhook', [])
        ->assertStatus(422); // auth passed; empty body → 422
});

it('accepts webhook with correct Bearer token', function () {
    config(['ship24.webhook.secret' => 'test-secret']);

    $this->postJson('/ship24/webhook', [], ['Authorization' => 'Bearer test-secret'])
        ->assertStatus(422); // auth passed; empty body → 422
});

it('rejects webhook with wrong Bearer token', function () {
    config(['ship24.webhook.secret' => 'test-secret']);

    $this->postJson('/ship24/webhook', [], ['Authorization' => 'Bearer wrong'])
        ->assertStatus(401);
});

it('rejects webhook with missing Authorization header', function () {
    config(['ship24.webhook.secret' => 'test-secret']);

    $this->postJson('/ship24/webhook', [])
        ->assertStatus(401);
});

it('rejects old-style X-Ship24-Signature HMAC header', function () {
    config(['ship24.webhook.secret' => 'test-secret']);

    $body   = json_encode(['trackings' => []]);
    $hmac   = 'sha256='.hash_hmac('sha256', $body, 'test-secret');

    $this->postJson('/ship24/webhook', [], ['X-Ship24-Signature' => $hmac])
        ->assertStatus(401);
});

// ── service: syncFromWebhookPayload payload structure ─────────────────────────

it('returns 0 when trackings key is missing', function () {
    $service = app(Ship24TrackingService::class);

    expect($service->syncFromWebhookPayload([]))->toBe(0);
});

it('returns 0 when trackings array is empty', function () {
    $service = app(Ship24TrackingService::class);

    expect($service->syncFromWebhookPayload(['trackings' => []]))->toBe(0);
});

it('returns 0 when tracking item has no trackingNumber or trackerId', function () {
    $service = app(Ship24TrackingService::class);

    $payload = [
        'trackings' => [
            ['tracker' => [], 'shipment' => [], 'events' => []],
        ],
    ];

    expect($service->syncFromWebhookPayload($payload))->toBe(0);
});

it('ignores old flat-payload structure that lacks trackings key', function () {
    $service = app(Ship24TrackingService::class);

    // Old (incorrect) payload shape — should not cause errors, just return 0
    $payload = [
        'trackingNumber' => '1Z999AA10123456784',
        'trackerId'      => 'trk_abc123',
        'shipment'       => [],
        'events'         => [],
    ];

    expect($service->syncFromWebhookPayload($payload))->toBe(0);
});
