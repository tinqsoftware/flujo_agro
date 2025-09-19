<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormRunPdf extends Model {
    use HasFactory;
    protected $table = 'form_run_pdfs';
    protected $fillable = ['form_run_id','template_id','path','filename','mime','size','created_by'];

    public function formRun(){ return $this->belongsTo(FormRun::class,'form_run_id'); }
    public function template(){ return $this->belongsTo(PdfTemplate::class,'template_id'); }
}
