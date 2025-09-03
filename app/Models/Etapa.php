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
        'paralelo',
        'nro'
    ];

    protected $casts = [
        'estado' => 'integer',
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
        // Nueva lógica: los documentos de una etapa son todos los documentos de sus tareas
        // Especificamos la tabla para evitar ambigüedad en la columna 'estado'
        return $this->hasManyThrough(
            Documento::class, 
            Tarea::class, 
            'id_etapa', // Foreign key en tabla tareas
            'id_tarea', // Foreign key en tabla documentos
            'id', // Local key en tabla etapas
            'id'  // Local key en tabla tareas
        )->select('documentos.*'); // Especificar que queremos todas las columnas de documentos
    }

    public function tareas()
    {
        return $this->hasMany(Tarea::class, 'id_etapa');
    }

    public function detalleEtapas()
    {
        return $this->hasMany(DetalleEtapa::class, 'id_etapa');
    }
}
