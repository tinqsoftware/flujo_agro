<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_emp'  => 'required|integer',
            'id_type' => 'required|integer',
            'nombre'  => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'usa_correlativo'     => 'boolean',
            'correlativo_prefijo' => 'nullable|string|max:30',
            'correlativo_sufijo'  => 'nullable|string|max:30',
            'correlativo_padding' => 'nullable|integer|min:1|max:12',
            'estado' => 'boolean',
        ];
    }
}
