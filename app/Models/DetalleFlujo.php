<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleFlujo extends Model
{
    protected $table = 'detalle_flujo';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_flujo',
        'id_emp',
        'id_user_create',
        'estado',
    ];

    protected $casts = [
        'id' => 'integer',
        'id_flujo' => 'integer',
        'id_emp' => 'integer',
        'id_user_create' => 'integer',
        'estado' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function flujo()
    {
        return $this->belongsTo(Flujo::class, 'id_flujo');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_emp');
    }

    public function userCreate()
    {
        return $this->belongsTo(User::class, 'id_user_create');
    }

    public function detalleEtapas()
    {
        return $this->hasMany(DetalleEtapa::class, 'id_detalle_flujo');
    }
}
