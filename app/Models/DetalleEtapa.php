<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Factories\HasFactory;

class DetalleEtapa extends Model
{
    use HasFactory;

    protected $table = 'detalle_etapa';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'id_etapa',
        'id_detalle_flujo',
        'estado',
    ];

    protected $casts = [
        'id' => 'integer',
        'id_etapa' => 'integer',
        'id_detalle_flujo' => 'integer',
        'estado' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function etapa()
    {
        return $this->belongsTo(Etapa::class, 'id_etapa');
    }

    public function detalleFlujo()
    {
        return $this->belongsTo(DetalleFlujo::class, 'id_detalle_flujo');
    }

    public function detalleTareas()
    {
        return $this->hasMany(DetalleTarea::class, 'id_detalle_etapa');
    }

    public function detalleDocumentos()
    {
        // Nueva lógica: los documentos están relacionados a través de DetalleTarea
        return $this->hasManyThrough(
            DetalleDocumento::class,
            DetalleTarea::class,
            'id_detalle_etapa', // Foreign key en DetalleTarea
            'id_detalle_tarea', // Foreign key en DetalleDocumento
            'id', // Local key en DetalleEtapa
            'id'  // Local key en DetalleTarea
        );
    }
}
