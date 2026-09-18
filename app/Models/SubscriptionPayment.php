<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'user_id', 'invoice_number', 'method', 'amount', 'proof_path',
        'qris_reference', 'status', 'admin_note', 'reviewed_at',
    ];

    protected $casts = ['amount' => 'float', 'reviewed_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
