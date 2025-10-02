<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FichaGroupDef extends Model
{
    protected $table = 'ficha_group_defs';
    protected $fillable = [
        'id_ficha', 'entity_type','code','label',
        'group_type','related_entity_type','item_fields_json','allow_multiple','is_active'
    ];

    protected $casts = [
        'item_fields_json' => 'array',
        'allow_multiple'=>'bool',
        'is_active' => 'boolean',
    ];

    public static function for(string $entityType): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }
}
