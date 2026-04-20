<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ship24_trackings', function (Blueprint $table) {
            $table->id();
            $table->morphs('trackable');
            $table->string('tracking_number');
            $table->string('tracker_id')->nullable();
            $table->string('carrier_id')->nullable();
            $table->string('carrier_name')->nullable();
            $table->string('status_code')->nullable();
            $table->string('status_category')->nullable();
            $table->string('status_milestone')->nullable();
            $table->timestamp('latest_event_at')->nullable();
            $table->string('latest_event_status')->nullable();
            $table->text('latest_event_location')->nullable();
            $table->json('events')->nullable();
            $table->json('raw_shipment')->nullable();
            $table->timestamps();

            $table->unique(['trackable_type', 'trackable_id', 'tracking_number'], 'ship24_trackings_unique');
            $table->index('tracking_number');
            $table->index('tracker_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ship24_trackings');
    }
};
