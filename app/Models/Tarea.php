<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarea extends Model
{
    use HasFactory;

    protected $table = 'tareas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'id_user_create',
        'id_etapa',
        'estado',
        'rol_cambios'
    ];

    protected $casts = [
        'estado' => 'integer'
    ];

    // Relaciones
    public function userCreate()
    {
        return $this->belongsTo(User::class, 'id_user_create');
    }

    public function etapa()
    {
        return $this->belongsTo(Etapa::class, 'id_etapa');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleTarea::class, 'id_tarea');
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_cambios');
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'id_tarea');
    }
}
