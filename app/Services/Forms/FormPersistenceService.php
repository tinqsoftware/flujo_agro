<?php
namespace App\Services\Forms;

use App\Models\{Form, FormRun, FormAnswer, FormAnswerRow, FormAnswerRowValue};
use Illuminate\Support\Facades\DB;

class FormPersistenceService {
    public function saveRun(Form $form, array $payload, int $empId, ?int $detalleFlujoId, int $userId): FormRun {
        return DB::transaction(function() use ($form,$payload,$empId,$detalleFlujoId,$userId){
            $run = new FormRun([
                'id_form' => $form->id,
                'id_emp'  => $empId,
                'id_detalle_flujo' => $detalleFlujoId,
                'estado'  => 'draft',
                'created_by' => $userId
            ]);

            // correlativo
            if ($form->usa_correlativo) {
                $seq = app(SequenceService::class)->next(
                    $form->id, $empId, $form->correlativo_prefijo ?? '', $form->correlativo_sufijo ?? '', $form->correlativo_padding ?? 6
                );
                $run->correlativo = $seq;
            }
            $run->save();

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
