<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdfTemplate extends Model {
    protected $table = 'pdf_templates';
    protected $casts = ['config_json'=>'array'];
    protected $fillable = ['id_form','nombre','config_json'];
    public function form(){ return $this->belongsTo(Form::class,'id_form'); }
    public function elements(){ return $this->hasMany(PdfElement::class,'id_template')->orderBy('orden'); }
}