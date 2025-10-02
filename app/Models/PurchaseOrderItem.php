<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = ['order_id','product_id','description','qty','unit_price','delivered_qty'];

    public function order(){ return $this->belongsTo(PurchaseOrder::class, 'order_id'); }
}