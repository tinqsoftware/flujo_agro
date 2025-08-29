<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleDocumento extends Model
{
    use HasFactory;

    protected $table = 'detalle_documento';

    protected $fillable = [
        'id_documento',
        'id_detalle_etapa',
        'estado',
        'id_user_create',
        'ruta_doc'
    ];

    protected $casts = [
        'estado' => 'boolean'
    ];

    // Relaciones
    public function documento()
    {
        return $this->belongsTo(Documento::class, 'id_documento');
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
