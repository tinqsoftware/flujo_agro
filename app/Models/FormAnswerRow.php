<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormAnswerRow extends Model {
    protected $table = 'form_answer_rows';
    protected $fillable = ['id_run','id_group','row_index'];
    public function run(){ return $this->belongsTo(FormRun::class,'id_run'); }
    public function group(){ return $this->belongsTo(FormGroup::class,'id_group'); }
    public function values(){ return $this->hasMany(FormAnswerRowValue::class,'id_row'); }
}