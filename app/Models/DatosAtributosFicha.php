<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatosAtributosFicha extends Model
{
    use HasFactory;

    protected $table = 'datos_atributos_fichas';

    protected $fillable = [
        'id_atributo',
        'id_relacion',
        'dato',
        'json',
        'id_user_create'
    ];

    protected $casts = [
        'json' => 'array'
    ];

    // Relaciones
    public function atributo()
    {
        return $this->belongsTo(AtributoFicha::class, 'id_atributo');
    }

    public function userCreate()
    {
        return $this->belongsTo(User::class, 'id_user_create');
    }
}
