<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormSequence extends Model {
    protected $table = 'form_sequences';
    public $timestamps = false;
    protected $fillable = ['id_form','id_emp','last_number'];
    public function form(){ return $this->belongsTo(Form::class,'id_form'); }
    public function empresa(){ return $this->belongsTo(Empresa::class,'id_emp'); }
}