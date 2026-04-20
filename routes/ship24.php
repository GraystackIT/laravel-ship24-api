<?php

declare(strict_types=1);

use GraystackIT\Ship24\Http\Controllers\Ship24WebhookController;
use Illuminate\Support\Facades\Route;

Route::post(config('ship24.webhook.path', 'ship24/webhook'), Ship24WebhookController::class)
    ->name('ship24.webhook');
