<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'description',
        'reference_id',
        'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function formatAmount()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getAmountWithSign()
    {
        $sign = $this->type === 'topup' ? '+' : '-';
        return $sign . $this->formatAmount();
    }

    public function getTypeIcon()
    {
        $icons = [
            'topup' => 'plus',
            'purchase' => 'minus',
            'refund' => 'undo'
        ];

        return $icons[$this->type] ?? 'exchange-alt';
    }

    public function getTypeClass()
    {
        return $this->type === 'topup' ? 'positive' : 'negative';
    }

    public function isSuccess()
    {
        return $this->status === 'success';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    // Scope queries
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $limit = 10)
    {
        return $query->latest()->limit($limit);
    }

    // Static methods
    public static function createTopup($userId, $amount, $description = null, $referenceId = null)
    {
        return static::create([
            'user_id' => $userId,
            'type' => 'topup',
            'amount' => $amount,
            'description' => $description ?: 'Top up saldo',
            'reference_id' => $referenceId,
            'status' => 'success'
        ]);
    }

    public static function createPurchase($userId, $amount, $description, $referenceId = null)
    {
        return static::create([
            'user_id' => $userId,
            'type' => 'purchase',
            'amount' => $amount,
            'description' => $description,
            'reference_id' => $referenceId,
            'status' => 'success'
        ]);
    }
}
