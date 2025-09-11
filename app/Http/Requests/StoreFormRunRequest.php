<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormRunRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        // reglas base; los requeridos específicos se aplican en el controlador
        return [
            'fields'               => 'array',
            'groups'               => 'array',
            'id_etapas_forms'      => 'nullable|integer', // Campo correcto
            'id_detalle_flujo'     => 'nullable|integer', // Mantener compatibilidad si se necesita
        ];
    }
}
