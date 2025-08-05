<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flujo extends Model
{
    use HasFactory;

    protected $table = 'flujos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha_inicio',
        'id_user_create',
        'id_ficha',
        'id_tipo_flujo',
        'id_emp',
        'estado'
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'estado' => 'boolean'
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

    public function tipoFlujo()
    {
        return $this->belongsTo(TipoFlujo::class, 'id_tipo_flujo');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_emp');
    }

    public function etapas()
    {
        return $this->hasMany(Etapa::class, 'id_flujo');
    }
}
