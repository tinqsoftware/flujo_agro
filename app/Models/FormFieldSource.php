<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormFieldSource extends Model {
    protected $table = 'form_field_sources';
    protected $casts = ['options_json'=>'array'];
    protected $fillable = [
        'id_field','source_kind','table_name','column_name','ficha_id','atributo_id',
        'query_sql','options_json','multi_select'
    ];
    public function field(){ return $this->belongsTo(FormField::class,'id_field'); }
}
