<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormField;
use Illuminate\Support\Facades\Log;

class FormulaEvalController extends Controller
{
    public function eval(Request $request)
    {
        $fieldId = $request->input('field_id');
        $local   = $request->input('local', []);
        $global  = $request->input('global', []);

        $field = FormField::with('formula')->find($fieldId);
        if (!$field || !$field->formula) {
            return response()->json(['ok' => false, 'msg' => 'formula not found'], 404);
        }

        $expr = $field->formula->expression;

        // ===== Reemplazar tokens tipo {{codigo}} =====
        $evaluated = preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)(?:\.([a-zA-Z0-9_]+))?\s*\}\}/',
            function($m) use ($local, $global){
                $code = $m[1];
                $attr = $m[2] ?? null;

                // primero en local
                if (array_key_exists($code, $local)) {
                    $val = $local[$code];
                    if ($attr && is_array($val)) {
                        return $val[$attr] ?? 0;
                    }
                    return $val ?? 0;
                }

                // luego en global
                if (array_key_exists($code, $global)) {
                    $val = $global[$code];
                    if ($attr && is_array($val)) {
                        return $val[$attr] ?? 0;
                    }
                    return $val ?? 0;
                }

                return 0;
            },
            $expr
        );

        // ===== Evaluar aritmética =====
        try {
            $val = eval("return $evaluated;");
        } catch (\Throwable $e) {
            Log::error("Formula eval error: ".$e->getMessage(), ['expr'=>$expr, 'evaluated'=>$evaluated]);
            return response()->json(['ok'=>false, 'msg'=>'eval error']);
        }

        return response()->json([
            'ok'    => true,
            'value' => $val,
        ]);
    }
}