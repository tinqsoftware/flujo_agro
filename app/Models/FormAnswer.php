<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormAnswer extends Model {
    protected $table = 'form_answers';
    protected $casts = ['value_json'=>'array'];
    protected $fillable = [
        'id_run','id_field','value_text','value_number','value_int','value_date',
        'value_datetime','value_bool','value_json'
    ];
    public function run(){ return $this->belongsTo(FormRun::class,'id_run'); }
    public function field(){ return $this->belongsTo(FormField::class,'id_field'); }
    public function relations(){ return $this->hasMany(FormAnswerRelation::class,'id_answer'); }
}
