<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use HasFactory;

    protected $table = 'documentos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'id_user_create',
        'id_tarea',
        'estado',
        'rol_cambios'
    ];

    protected $casts = [
        'estado' => 'integer'
    ];

    // Relacioness
    public function userCreate()
    {
        return $this->belongsTo(User::class, 'id_user_create');
    }

    public function tarea()
    {
        return $this->belongsTo(Tarea::class, 'id_tarea');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleDocumento::class, 'id_documento');
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_cambios');
    }
}
