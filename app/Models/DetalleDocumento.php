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
        'id_detalle_tarea',
        'estado',
        'id_user_create',
        'ruta_doc',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'estado' => 'boolean'
    ];

    // Relaciones
    public function documento()
    {
        return $this->belongsTo(Documento::class, 'id_documento');
    }

    public function detalleTarea()
    {
        return $this->belongsTo(DetalleTarea::class, 'id_detalle_tarea');
    }

    public function userCreate()
    {
        return $this->belongsTo(User::class, 'id_user_create');
    }
}
