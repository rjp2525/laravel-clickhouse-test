<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'name',
        'address_1',
        'address_2',
        'city',
        'state',
        'country',
        'postal_code',
    ];
}
