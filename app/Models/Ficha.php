<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ficha extends Model
{
    use HasFactory;

    protected $table = 'ficha';

    protected $fillable = [
        'nombre',
        'id_emp',
        'estado',
        'id_user_create'
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

    public function atributos()
    {
        return $this->hasMany(AtributoFicha::class, 'id_ficha');
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'id_ficha');
    }

    public function proveedores()
    {
        return $this->hasMany(Proveedor::class, 'id_ficha');
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'id_ficha');
    }

    public function flujos()
    {
        return $this->hasMany(Flujo::class, 'id_ficha');
    }

    public function etapas()
    {
        return $this->hasMany(Etapa::class, 'id_ficha');
    }
}
