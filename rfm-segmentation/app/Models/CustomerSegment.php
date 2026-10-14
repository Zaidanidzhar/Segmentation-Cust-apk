<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'recency',
        'frequency',
        'monetary',
        'recency_score',
        'frequency_score',
        'monetary_score',
        'cluster_label',
        'segment_name',
        'calculated_at',
    ];

    protected $casts = [
        'monetary' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}