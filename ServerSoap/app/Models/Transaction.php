<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'status',
        'reference',
    ];

    /**
     * Get the wallet that owns the this.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    /**
     * Get the confirmations associated with this.
     */
    public function confirmations(): HasMany
    {
        return $this->hasMany(CodeConfirmation::class, 'transaction_id');
    }
}
