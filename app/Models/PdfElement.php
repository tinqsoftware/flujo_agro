<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdfElement extends Model {
    protected $table = 'pdf_elements';
    protected $casts = ['options_json'=>'array'];
    protected $fillable = ['id_template','type','x','y','w','h','binding','options_json','orden'];
    public function template(){ return $this->belongsTo(PdfTemplate::class,'id_template'); }
}