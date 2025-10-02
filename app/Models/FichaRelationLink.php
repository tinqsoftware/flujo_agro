<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FichaRelationLink extends Model
{
    protected $table = 'ficha_relation_links';
    protected $fillable = [
        'entity_type','entity_id','id_ficha','group_code', 
        'related_entity_type','related_entity_id'
    ];

}
