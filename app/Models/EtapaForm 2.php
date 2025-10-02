<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtapaForm extends Model
{
    use HasFactory;

    protected $table = 'etapas_forms';

    protected $fillable = [
        'id_forms',
        'id_etapa',
    ];

    protected $casts = [
        'id_forms' => 'integer',
        'id_etapa' => 'integer',
    ];

    // Relaciones
    public function form()
    {
        return $this->belongsTo(Form::class, 'id_forms');
    }

    public function etapa()
    {
        return $this->belongsTo(Etapa::class, 'id_etapa');
    }
}
