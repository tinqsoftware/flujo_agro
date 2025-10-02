<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    protected $fillable = ['order_id','product_id','description','qty','unit_price','delivered_qty'];

    public function order(){ return $this->belongsTo(SalesOrder::class, 'order_id'); }
}