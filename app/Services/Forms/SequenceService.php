<?php
namespace App\Services\Forms;

use App\Models\FormSequence;
use Illuminate\Support\Facades\DB;

class SequenceService {
    public function next(int $formId, int $empId, string $prefix='', string $suffix='', int $padding=6): string {
        return DB::transaction(function() use ($formId,$empId,$prefix,$suffix,$padding){
            $seq = FormSequence::lockForUpdate()->firstOrCreate(
                ['id_form'=>$formId,'id_emp'=>$empId],
                ['last_number'=>0]
            );
            $seq->last_number++;
            $seq->save();

            $num = str_pad((string)$seq->last_number, $padding, '0', STR_PAD_LEFT);
            return $prefix.$num.$suffix;
        }, 3);
    }
}
