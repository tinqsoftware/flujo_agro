<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $fillable = [
        'id_emp','customer_id','run_id','number','status','currency','total',
    ];

    public function items(){ return $this->hasMany(SalesOrderItem::class, 'order_id'); }
    public function run(){ return $this->belongsTo(FormRun::class, 'run_id'); }
}
