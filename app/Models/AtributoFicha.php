<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtributoFicha extends Model
{
    use HasFactory;

    protected $table = 'atributo_ficha';

    protected $fillable = [
        'id_ficha',
        'titulo',
        'tipo',
        'json',
        'estado',
        'id_user_create',
        'obligatorio'
    ];

    protected $casts = [
        'estado' => 'boolean',
        'obligatorio' => 'boolean',
        'json' => 'array'
    ];

    // Relaciones
    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'id_ficha');
    }

    public function userCreate()
    {
        return $this->belongsTo(User::class, 'id_user_create');
    }

    public function datosAtributos()
    {
        return $this->hasMany(DatosAtributosFicha::class, 'id_atributo');
    }
}
