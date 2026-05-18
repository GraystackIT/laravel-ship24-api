<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Http\Controllers;

use GraystackIT\Ship24\Services\Ship24TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class Ship24WebhookController extends Controller
{
    public function __invoke(Request $request, Ship24TrackingService $service): JsonResponse
    {
        if (! $this->signatureValid($request)) {
            Log::warning('Ship24: webhook rejected — invalid signature');

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = $request->all();

        if (empty($payload)) {
            return response()->json(['error' => 'Empty payload'], 422);
        }

        Log::info('Ship24: webhook received', [
            'count' => count($payload['trackings'] ?? []),
        ]);

        $updated = $service->syncFromWebhookPayload($payload);

        return response()->json(['status' => 'ok', 'updated' => $updated]);
    }

    private function signatureValid(Request $request): bool
    {
        $secret = config('ship24.webhook.secret');

        if (! $secret) {
            return true;
        }

        $authorization = $request->header('Authorization');

        if (! $authorization) {
            return false;
        }

        return hash_equals('Bearer '.(string) $secret, $authorization);
    }
}
