<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    protected $fillable = [
        'id_emp','ref_type','ref_id','direction','concept','amount','currency',
    ];
}