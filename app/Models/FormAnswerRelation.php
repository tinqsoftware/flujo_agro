<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormAnswerRelation extends Model {
    protected $table = 'form_answer_relations';
    protected $casts = ['extra_json'=>'array'];
    protected $fillable = ['id_answer','related_table','related_id','extra_json'];
    public function answer(){ return $this->belongsTo(FormAnswer::class,'id_answer'); }
}