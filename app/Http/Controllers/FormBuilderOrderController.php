<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormGroup;
use Illuminate\Http\Request;

class FormBuilderOrderController extends Controller
{
    public function reorder(Request $r, Form $form)
    {
        // payload:
        // groups: [{id:groupId, orden:n}]
        // fields: [{id:fieldId, orden:n, id_group:null|groupId}]
        $groups = $r->input('groups', []);
        $fields = $r->input('fields',  []);

        \DB::transaction(function() use ($groups, $fields, $form){
            foreach ($groups as $g) {
                FormGroup::where('id', $g['id'])->where('id_form', $form->id)
                    ->update(['orden' => (int)$g['orden']]);
            }
            foreach ($fields as $f) {
                $data = ['orden' => (int)$f['orden']];
                // permitir mover de cabecera a grupo y viceversa
                $data['id_group'] = array_key_exists('id_group', $f) ? $f['id_group'] : null;

                FormField::where('id', $f['id'])->where('id_form', $form->id)->update($data);
            }
        });

        return response()->json(['ok'=>true]);
    }
}
