<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcpayBindCard extends Model
{
    public $incrementing = false;
    protected $table = 'ecpay_bind_card';
    protected $primaryKey = 'id';
}
