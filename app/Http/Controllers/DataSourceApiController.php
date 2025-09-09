<?php

namespace App\Http\Controllers;

use App\Models\FormField;
use App\Services\Forms\DataSourceResolver;
use Illuminate\Http\Request;

class DataSourceApiController extends Controller
{
    public function options(Request $r, DataSourceResolver $resolver) {
        $field = FormField::with('source')->findOrFail($r->query('field_id'));
        $ctx = ['id_emp'=>$field->id_emp];
        return response()->json($resolver->options($field, $ctx));
    }
}
