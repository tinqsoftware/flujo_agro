<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasFichaExtras;

class Cliente extends Model
{
    use HasFactory;
    use HasFichaExtras;


    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'ruta_logo',
        'id_user_create',
        'id_emp',
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
    
    public function fichaEntityType(): string { return 'cliente'; }

}
