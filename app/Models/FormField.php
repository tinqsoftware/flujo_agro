<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormField extends Model {
    protected $table = 'form_fields';
    protected $casts = ['config_json'=>'array'];
    protected $fillable = [
        'id_form','id_group','codigo','etiqueta','descripcion','kind','datatype',
        'requerido','unico','orden','visible','config_json'
    ];
    public function form(){ return $this->belongsTo(Form::class,'id_form'); }
    public function group(){ return $this->belongsTo(FormGroup::class,'id_group'); }
    public function source(){ return $this->hasOne(FormFieldSource::class,'id_field'); }
    public function formula(){ return $this->hasOne(FormFieldFormula::class,'id_field'); }
}