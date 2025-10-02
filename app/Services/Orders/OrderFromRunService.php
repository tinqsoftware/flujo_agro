<?php

namespace App\Services\Orders;

use App\Models\{
    FormRun, PurchaseOrder, PurchaseOrderItem, SalesOrder, SalesOrderItem, LedgerEntry
};
use Illuminate\Support\Arr;

class OrderFromRunService
{
    /**
     * Crea una Orden de Compra (PO) desde un FormRun.
     *
     * @param  FormRun $run
     * @param  array   $map   Mapeo de campos. Ejemplo:
     *   [
     *     'supplier_field' => 'proveedor_id',           // código de campo cabecera
     *     'group_name'     => 'Productos',              // nombre del grupo en el form
     *     'product_field'  => 'producto',               // código de campo en fila
     *     'qty_field'      => 'cantidad',               // código de campo en fila
     *     'price_field'    => 'unit_price',             // código de campo en fila
     *     'desc_field'     => 'descripcion',            // (opcional)
     *     'currency'       => 'PEN',                    // (opcional)
     *   ]
     */
    public function createPOFromRun(FormRun $run, array $map): PurchaseOrder
    {
        // === 1) Cabecera: obtener proveedor desde answers de cabecera
        $answers = $run->answers()->with('field')->get();
        $byCode  = [];
        foreach ($answers as $ans) {
            $byCode[$ans->field->codigo] = $ans->value(); // usa tu accessor para value normalizado
        }

        $supplierId = Arr::get($byCode, $map['supplier_field']);
        $currency   = $map['currency'] ?? 'PEN';

        $po = PurchaseOrder::create([
            'id_emp'      => $run->id_emp,
            'supplier_id' => (int)$supplierId,
            'run_id'      => $run->id,
            'number'      => null,           // si quieres generar correlativo, hazlo aquí
            'status'      => 'draft',
            'currency'    => $currency,
            'total'       => 0,
        ]);

        // === 2) Detalle: recorrer filas del grupo indicado
        $total = 0;
        $rows = $run->rows()->with(['group','values.field'])
                  ->whereHas('group', fn($q)=>$q->where('nombre', $map['group_name']))
                  ->get();

        foreach ($rows as $row) {
            $vals = [];
            foreach ($row->values as $v) {
                $vals[$v->field->codigo] = $v->value();
            }

            $qty   = (float) Arr::get($vals, $map['qty_field'], 0);
            $price = (float) Arr::get($vals, $map['price_field'], 0);
            $prod  = Arr::get($vals, $map['product_field']);
            $desc  = Arr::get($vals, $map['desc_field'], null);

            $line = PurchaseOrderItem::create([
                'order_id'    => $po->id,
                'product_id'  => $prod ? (int)$prod : null,
                'description' => $desc,
                'qty'         => $qty,
                'unit_price'  => $price,
                'delivered_qty'=> 0,
            ]);

            $total += $qty * $price;
        }

        $po->update(['total' => $total]);

        // === 3) Asiento en libro
        LedgerEntry::create([
            'id_emp'   => $run->id_emp,
            'ref_type' => 'PO',
            'ref_id'   => $po->id,
            'direction'=> 'out',
            'concept'  => 'OC desde ejecución #'.$run->id,
            'amount'   => $total,
            'currency' => $currency,
        ]);

        return $po;
    }

    /**
     * Crea una Orden de Venta (SO) desde un FormRun.
     *
     * @param  FormRun $run
     * @param  array   $map   Ejemplo:
     *   [
     *     'customer_field' => 'cliente_id',
     *     'group_name'     => 'Productos',
     *     'product_field'  => 'producto',
     *     'qty_field'      => 'cantidad',
     *     'price_field'    => 'unit_price',
     *     'desc_field'     => 'descripcion',
     *     'currency'       => 'PEN',
     *   ]
     */
    public function createSOFromRun(FormRun $run, array $map): SalesOrder
    {
        $answers = $run->answers()->with('field')->get();
        $byCode  = [];
        foreach ($answers as $ans) {
            $byCode[$ans->field->codigo] = $ans->value();
        }

        $customerId = Arr::get($byCode, $map['customer_field']);
        $currency   = $map['currency'] ?? 'PEN';

        $so = SalesOrder::create([
            'id_emp'      => $run->id_emp,
            'customer_id' => (int)$customerId,
            'run_id'      => $run->id,
            'number'      => null,
            'status'      => 'draft',
            'currency'    => $currency,
            'total'       => 0,
        ]);

        $total = 0;
        $rows = $run->rows()->with(['group','values.field'])
                  ->whereHas('group', fn($q)=>$q->where('nombre', $map['group_name']))
                  ->get();

        foreach ($rows as $row) {
            $vals = [];
            foreach ($row->values as $v) {
                $vals[$v->field->codigo] = $v->value();
            }

            $qty   = (float) Arr::get($vals, $map['qty_field'], 0);
            $price = (float) Arr::get($vals, $map['price_field'], 0);
            $prod  = Arr::get($vals, $map['product_field']);
            $desc  = Arr::get($vals, $map['desc_field'], null);

            SalesOrderItem::create([
                'order_id'    => $so->id,
                'product_id'  => $prod ? (int)$prod : null,
                'description' => $desc,
                'qty'         => $qty,
                'unit_price'  => $price,
                'delivered_qty'=> 0,
            ]);

            $total += $qty * $price;
        }

        $so->update(['total' => $total]);

        LedgerEntry::create([
            'id_emp'   => $run->id_emp,
            'ref_type' => 'SO',
            'ref_id'   => $so->id,
            'direction'=> 'in',
            'concept'  => 'OV desde ejecución #'.$run->id,
            'amount'   => $total,
            'currency' => $currency,
        ]);

        return $so;
    }
}
