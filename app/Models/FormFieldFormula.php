<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormFieldFormula extends Model {
    protected $table = 'form_field_formulas';
    protected $fillable = ['id_field','expression','output_type'];
    public $timestamps = true;
    
    public function field(){ return $this->belongsTo(FormField::class,'id_field'); }
}