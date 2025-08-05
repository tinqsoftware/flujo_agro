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
        'estado'
    ];

    protected $casts = [
        'estado' => 'boolean'
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
}
