<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormAnswerRowValue extends Model {
    protected $table = 'form_answer_row_values';
    protected $casts = ['value_json'=>'array'];
    protected $fillable = [
        'id_row','id_field','value_text','value_number','value_int','value_date',
        'value_datetime','value_bool','value_json'
    ];
    public function row(){ return $this->belongsTo(FormAnswerRow::class,'id_row'); }
    public function field(){ return $this->belongsTo(FormField::class,'id_field'); }
}