<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoFlujo extends Model
{
    use HasFactory;

    protected $table = 'tipo_flujo';

    protected $fillable = [
        'nombre',
        'descripcion',
        'id_emp',
        'id_user_create',
        'estado'
    ];

    protected $casts = [
        'estado' => 'boolean'
    ];

    // Relaciones
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_emp');
    }

    public function userCreate()
    {
        return $this->belongsTo(User::class, 'id_user_create');
    }

    public function flujos()
    {
        return $this->hasMany(Flujo::class, 'id_tipo_flujo');
    }
}
