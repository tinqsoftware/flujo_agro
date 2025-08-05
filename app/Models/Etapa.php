<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etapa extends Model
{
    use HasFactory;

    protected $table = 'etapas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'id_user_create',
        'id_ficha',
        'id_flujo',
        'estado',
        'paralelo'
    ];

    protected $casts = [
        'estado' => 'boolean',
        'paralelo' => 'boolean'
    ];

    // Relaciones
    public function userCreate()
    {
        return $this->belongsTo(User::class, 'id_user_create');
    }

    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'id_ficha');
    }

    public function flujo()
    {
        return $this->belongsTo(Flujo::class, 'id_flujo');
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'id_etapa');
    }

    public function tareas()
    {
        return $this->hasMany(Tarea::class, 'id_etapa');
    }
}
