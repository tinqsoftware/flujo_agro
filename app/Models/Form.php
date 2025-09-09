<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model {
    protected $table = 'forms';
    protected $fillable = [
        'id_emp','id_type','nombre','descripcion',
        'usa_correlativo','correlativo_prefijo','correlativo_sufijo','correlativo_padding',
        'estado','created_by','updated_by'
    ];
    public function empresa(){ return $this->belongsTo(Empresa::class,'id_emp'); }
    public function type(){ return $this->belongsTo(FormType::class,'id_type'); }
    public function groups(){ return $this->hasMany(FormGroup::class,'id_form')->orderBy('orden'); }
    public function fields(){ return $this->hasMany(FormField::class,'id_form')->orderBy('orden'); }
    public function sequences(){ return $this->hasMany(FormSequence::class,'id_form'); }
    public function runs(){ return $this->hasMany(FormRun::class,'id_form'); }
    public function pdfTemplates(){ return $this->hasMany(PdfTemplate::class,'id_form'); }
}