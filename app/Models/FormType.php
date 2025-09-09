<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormType extends Model {
    protected $table = 'form_types';
    protected $fillable = ['id_emp','nombre','descripcion','estado'];
    public function empresa(){ return $this->belongsTo(Empresa::class,'id_emp'); }
    public function forms(){ return $this->hasMany(Form::class,'id_type'); }
}
