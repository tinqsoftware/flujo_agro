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
        // Especificamos las tablas para evitar ambigüedad en las columnas
        return $this->hasManyThrough(
            Documento::class, 
            Tarea::class, 
            'id_etapa', // Foreign key en tabla tareas
            'id_tarea', // Foreign key en tabla documentos
            'id', // Local key en tabla etapas
            'id'  // Local key en tabla tareas
        );
    }

    public function tareas()
    {
        return $this->hasMany(Tarea::class, 'id_etapa');
    }

    public function detalleEtapas()
    {
        return $this->hasMany(DetalleEtapa::class, 'id_etapa');
    }

    // Relación con formularios a través de la tabla pivot etapas_forms
    public function forms()
    {
        return $this->belongsToMany(Form::class, 'etapas_forms', 'id_etapa', 'id_forms')
                    ->withTimestamps();
    }
    
    // Relación directa con EtapaForm
    public function etapaForms()
    {
        return $this->hasMany(EtapaForm::class, 'id_etapa');
    }
}
