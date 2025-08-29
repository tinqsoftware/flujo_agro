<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleTarea extends Model
{
    use HasFactory;

    protected $table = 'detalle_tarea';

    protected $fillable = [
        'id_tarea',
        'id_detalle_etapa',
        'estado',
        'id_user_create'
    ];

    protected $casts = [
        'estado' => 'boolean'
    ];

    // Relaciones
    public function tarea()
    {
        return $this->belongsTo(Tarea::class, 'id_tarea');
    }

    public function detalleEtapa()
    {
        return $this->belongsTo(DetalleEtapa::class, 'id_detalle_etapa');
    }

    public function userCreate()
    {
        return $this->belongsTo(User::class, 'id_user_create');
    }
}
