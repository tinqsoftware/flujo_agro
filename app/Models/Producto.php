<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha_inicio',
        'id_user_create',
        'id_ficha',
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

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_emp');
    }
}
