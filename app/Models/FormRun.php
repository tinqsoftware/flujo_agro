<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormRun extends Model {
    protected $table = 'form_runs';
    protected $fillable = [
        'id_form','id_emp','id_etapas_forms','correlativo','estado','created_by','updated_by'
    ];
    
    // Campos que pueden ser null
    protected $nullable = ['id_etapas_forms'];
    public function form(){ return $this->belongsTo(Form::class,'id_form'); }
    public function empresa(){ return $this->belongsTo(Empresa::class,'id_emp'); }
    public function etapaForm(){ return $this->belongsTo(EtapaForm::class,'id_etapas_forms'); }
    public function answers(){ return $this->hasMany(FormAnswer::class,'id_run'); }
    public function rows(){ return $this->hasMany(FormAnswerRow::class,'id_run'); }
    public function purchaseOrders(){ return $this->hasMany(\App\Models\PurchaseOrder::class, 'run_id'); }
    public function salesOrders(){ return $this->hasMany(\App\Models\SalesOrder::class, 'run_id'); }
}