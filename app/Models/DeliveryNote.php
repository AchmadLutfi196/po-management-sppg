<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryNote extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'number',
        'date',
        'driver',
        'kepada',
        'kd_sppg',
        'nama_sppg',
        'pj_sppg',
        'whatsapp',
        'notes',
        'proof_photo',
        'item_photos',
        'has_photo',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'item_photos' => 'array',
            'has_photo' => 'boolean',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
