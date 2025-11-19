<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'identification',
        'phone',
    ];

    /**
     * Get the wallet associated with this.
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'client_id')->latestOfMany();
    }

    /**
     * Get the wallets associated with this.
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'client_id');
    }
}
