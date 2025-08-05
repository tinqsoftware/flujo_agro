<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nombres',
        'apellidos',
        'sexo',
        'id_emp',
        'celular',
        'estado',
        'id_user_create',
        'id_rol',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'estado' => 'boolean',
        ];
    }

    // Relaciones
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_emp');
    }

    public function userCreate()
    {
        return $this->belongsTo(User::class, 'id_user_create');
    }

    public function usersCreated()
    {
        return $this->hasMany(User::class, 'id_user_create');
    }

    public function empresasAdmin()
    {
        return $this->hasMany(Empresa::class, 'id_user_admin');
    }

    public function empresasCreated()
    {
        return $this->hasMany(Empresa::class, 'id_user_create');
    }

    public function fichasCreated()
    {
        return $this->hasMany(Ficha::class, 'id_user_create');
    }

    public function atributosCreated()
    {
        return $this->hasMany(AtributoFicha::class, 'id_user_create');
    }

    public function datosAtributosCreated()
    {
        return $this->hasMany(DatosAtributosFicha::class, 'id_user_create');
    }

    public function tipoFlujosCreated()
    {
        return $this->hasMany(TipoFlujo::class, 'id_user_create');
    }

    public function flujosCreated()
    {
        return $this->hasMany(Flujo::class, 'id_user_create');
    }

    public function etapasCreated()
    {
        return $this->hasMany(Etapa::class, 'id_user_create');
    }

    public function documentosCreated()
    {
        return $this->hasMany(Documento::class, 'id_user_create');
    }

    public function detalleDocumentosCreated()
    {
        return $this->hasMany(DetalleDocumento::class, 'id_user_create');
    }

    public function tareasCreated()
    {
        return $this->hasMany(Tarea::class, 'id_user_create');
    }

    public function detalleTareasCreated()
    {
        return $this->hasMany(DetalleTarea::class, 'id_user_create');
    }

    public function clientesCreated()
    {
        return $this->hasMany(Cliente::class, 'id_user_create');
    }

    public function proveedoresCreated()
    {
        return $this->hasMany(Proveedor::class, 'id_user_create');
    }

    public function productosCreated()
    {
        return $this->hasMany(Producto::class, 'id_user_create');
    }
}
