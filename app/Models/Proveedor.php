<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'id_user_create',
        'id_emp',
        'ruta_logo',
        'estado',
        'id_ficha'
    ];

    protected $casts = [
        'estado' => 'boolean'
    ];

    // Relaciones
    public function userCreate()
    {
        return $this->belongsTo(User::class, 'id_user_create');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_emp');
    }

    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'id_ficha');
    }
}
