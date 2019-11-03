<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StkPushPayment extends Model
{
    protected $table = 'stkPush_payments';

    protected $fillable = ['MpesaReceiptNumber','phone','amount','ResultCode','ResultDesc','status'];
}
