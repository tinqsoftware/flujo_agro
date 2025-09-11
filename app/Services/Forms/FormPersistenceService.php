<?php
namespace App\Services\Forms;

use App\Models\{Form, FormRun, FormAnswer, FormAnswerRow, FormAnswerRowValue};
use Illuminate\Support\Facades\DB;

class FormPersistenceService {
    public function saveRun(Form $form, array $payload, int $empId, ?int $etapaFormId, int $userId, ?FormRun $existingRun = null): FormRun {
        return DB::transaction(function() use ($form,$payload,$empId,$etapaFormId,$userId,$existingRun){
            if ($existingRun) {
                // Actualizar run existente
                $run = $existingRun;
                $run->update([
                    'id_etapas_forms' => $etapaFormId,
                    'updated_by' => $userId
                ]);
                
                // Limpiar respuestas existentes para reemplazarlas
                $run->answers()->delete();
                $run->rows()->delete(); // Esto debería eliminar en cascada los values
            } else {
                // Crear nuevo run
                $run = new FormRun([
                    'id_form' => $form->id,
                    'id_emp'  => $empId,
                    'id_etapas_forms' => $etapaFormId, // Campo correcto según el modelo
                    'estado'  => 'draft',
                    'created_by' => $userId
                ]);

                // correlativo solo para nuevos runs
                if ($form->usa_correlativo) {
                    $seq = app(SequenceService::class)->next(
                        $form->id, $empId, $form->correlativo_prefijo ?? '', $form->correlativo_sufijo ?? '', $form->correlativo_padding ?? 6
                    );
                    $run->correlativo = $seq;
                }
                $run->save();
            }

            // valores simples
            foreach ($payload['fields'] ?? [] as $codigo => $value) {
                $field = $form->fields->firstWhere('codigo',$codigo);
                if(!$field || $field->group) continue;
                FormAnswer::updateOrCreate(
                    ['id_run'=>$run->id,'id_field'=>$field->id],
                    $this->mapValue($field->datatype, $value)
                );
            }

            // grupos repetibles
            foreach ($payload['groups'] ?? [] as $groupName => $rows) {
                $group = $form->groups->firstWhere('nombre',$groupName);
                if(!$group) continue;
                foreach ($rows as $i => $row) {
                    $rowModel = FormAnswerRow::create(['id_run'=>$run->id,'id_group'=>$group->id,'row_index'=>$i]);
                    foreach ($row as $codigo => $value) {
                        $field = $form->fields->firstWhere('codigo',$codigo);
                        if(!$field) continue;
                        FormAnswerRowValue::updateOrCreate(
                            ['id_row'=>$rowModel->id,'id_field'=>$field->id],
                            $this->mapValue($field->datatype, $value)
                        );
                    }
                }
            }
            return $run;
        });
    }

    private function mapValue(string $datatype, $value): array {
        return match($datatype){
            'int'     => ['value_int'=>$value],
            'decimal' => ['value_number'=>$value],
            'date'    => ['value_date'=>$value],
            'datetime'=> ['value_datetime'=>$value],
            'boolean' => ['value_bool'=>$value ? 1:0],
            'json'    => ['value_json'=>$value],
            default   => ['value_text'=>(string)$value],
        };
    }
}
