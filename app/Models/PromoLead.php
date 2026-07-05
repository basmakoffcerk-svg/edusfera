<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'subject',
        'status',
        'ip_address',
    ];
}
