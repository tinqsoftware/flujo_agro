<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormRun extends Model {
    protected $table = 'form_runs';
    protected $fillable = [
        'id_form','id_emp','id_detalle_flujo','correlativo','estado','created_by','updated_by'
    ];
    public function form(){ return $this->belongsTo(Form::class,'id_form'); }
    public function empresa(){ return $this->belongsTo(Empresa::class,'id_emp'); }
    public function detalleFlujo(){ return $this->belongsTo(DetalleFlujo::class,'id_detalle_flujo'); }
    public function answers(){ return $this->hasMany(FormAnswer::class,'id_run'); }
    public function rows(){ return $this->hasMany(FormAnswerRow::class,'id_run'); }
}