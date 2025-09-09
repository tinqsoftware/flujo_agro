<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormGroup extends Model {
    protected $table = 'form_groups';
    protected $fillable = ['id_form','nombre','descripcion','repetible','orden'];
    public function form(){ return $this->belongsTo(Form::class,'id_form'); }
    public function fields(){ return $this->hasMany(FormField::class,'id_group')->orderBy('orden'); }
}