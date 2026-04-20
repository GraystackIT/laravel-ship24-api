<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Ship24Tracking extends Model
{
    protected $table = 'ship24_trackings';

    protected $fillable = [
        'trackable_id',
        'trackable_type',
        'tracking_number',
        'tracker_id',
        'carrier_id',
        'carrier_name',
        'status_code',
        'status_category',
        'status_milestone',
        'latest_event_at',
        'latest_event_status',
        'latest_event_location',
        'events',
        'raw_shipment',
    ];

    protected function casts(): array
    {
        return [
            'events'          => 'array',
            'raw_shipment'    => 'array',
            'latest_event_at' => 'datetime',
        ];
    }

    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }
}
