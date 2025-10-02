<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FichaListItem extends Model
{
    protected $table = 'ficha_list_items';
    protected $fillable = [
        'entity_type','entity_id','id_ficha','group_code','value_json','sort_order'
    ];

    protected $casts = [
        'value_json' => 'array'
    ];

}
