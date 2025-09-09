<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'empresa';

    protected $fillable = [
        'nombre',
        'id_user_admin',
        'fecha_inicio',
        'id_user_create',
        'descripcion',
        'ruta_logo',
        'estado',
        'editable'
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'estado' => 'boolean',
        'editable' => 'boolean'
    ];

    // Relaciones
    public function userAdmin()
    {
        return $this->belongsTo(User::class, 'id_user_admin');
    }

    public function userCreate()
    {
        return $this->belongsTo(User::class, 'id_user_create');
    }

    public function fichas()
    {
        return $this->hasMany(Ficha::class, 'id_emp');
    }

    public function flujos()
    {
        return $this->hasMany(Flujo::class, 'id_emp');
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'id_emp');
    }

    public function proveedores()
    {
        return $this->hasMany(Proveedor::class, 'id_emp');
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'id_emp');
    }

    public function tipoFlujos()
    {
        return $this->hasMany(TipoFlujo::class, 'id_emp');
    }

    public function formTypes(){ return $this->hasMany(FormType::class,'id_emp'); }
    public function forms(){ return $this->hasMany(Form::class,'id_emp'); }
    public function formRuns(){ return $this->hasMany(FormRun::class,'id_emp'); }
    public function formSequences(){ return $this->hasMany(FormSequence::class,'id_emp'); }

}
