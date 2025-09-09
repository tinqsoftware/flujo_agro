<?php
namespace App\Services\Forms;

use App\Models\FormField;
use App\Models\FormRun;

/**
 * Motor de fórmulas:
 * - Placeholders: {{codigo}}  (acepta dot-notation: {{cliente.ruc}} si lo pasas en el contexto)
 * - Operadores: + - * / ( ) .   (concatenación con concat() preferible)
 * - Helpers expuestos:
 *     col(table, column, id)              -> devuelve columna de una tabla por id
 *     fattr(entityKey, attrCode, id)      -> devuelve atributo (ficha) por id
 *     concat(...args)                     -> concatena texto
 *     round2(x, dec=2)                    -> redondeo decimal
 *     n(x, nullAsZero=true)               -> castea a número
 *     coalesce(a, b, c, ...)              -> primer valor no null/''/[]/false
 *     min(...), max(...), abs(x)
 *     sum_rows(groupName, fieldCode)      -> suma numérica de un campo en filas guardadas del run actual
 * - Casteo final según output_type: int|decimal|boolean|date|text
 */
class FormulaEngine
{
    public function __construct(private DataSourceResolver $resolver) {}

    /**
     * Evalúa la expresión asociada a $field usando $context
     * $context puede incluir:
     *  - valores de cabecera (fields)
     *  - valores de la fila (cuando aplique)
     *  - '_run' => FormRun   (para sum_rows)
     */
    public function evaluate(FormField $field, array $context): mixed
    {
        $expr = $field->formula?->expression;
        if (!$expr) {
            return null;
        }

        // 1) Reemplaza placeholders {{key}} por literales PHP seguros
        //    Acepta letras, números, _, y dot-notation (cliente.ruc)
        $exprPHP = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function($m) use ($context) {
            $key = $m[1];
            $v = $this->arrGet($context, $key); // soporta dot-notation
            return $this->toPhpLiteral($v);
        }, $expr);

        // 2) Helpers disponibles en la expresión
        $lookupCol = function(string $table, string $column, int|string $id) {
            // Nota: DataSourceResolver debe implementar getValue($table, $id, $column)
            return $this->resolver->getValue($table, (int)$id, $column);
        };
        $lookupFicha = function(string $entityKey, string $attrCode, int|string $id) {
            // Debes implementar en DataSourceResolver: getFichaAttr($entityKey, $attrCode, $id)
            return $this->resolver->getFichaAttr($entityKey, $attrCode, (int)$id);
        };
        $empresaVal = function(string $col) {
            $idEmp = auth()->user()?->id_emp;
            return $this->resolver->empresaValue($idEmp, $col);
        };
        $sumRows = function(string $groupName, string $fieldCode) use ($context) {
            /** @var FormRun|null $run */
            $run = $context['_run'] ?? null;
            if (!$run) return 0;
            $run->loadMissing('rows.values.field','rows.group');
            $sum = 0.0;
            foreach ($run->rows as $row) {
                if (!$row->group || $row->group->nombre !== $groupName) continue;
                foreach ($row->values as $v) {
                    if ($v->field && $v->field->codigo === $fieldCode) {
                        $num = $v->value_number ?? $v->value_int ?? 0;
                        $sum += (float)$num;
                    }
                }
            }
            return $sum;
        };

        // Helpers utilitarios
        $helpers = [
            'col'       => $lookupCol,
            'fattr'     => $lookupFicha,
            'empresa'   => $empresaVal,
            'sum_rows'  => $sumRows,
            'concat'    => function(...$args) { return implode('', array_map(fn($x)=> (string)$x, $args)); },
            'round2'    => function($x, $d=2){ return round((float)$x, (int)$d); },
            'n'         => function($x, $nullAsZero=true){
                if ($x === '' || $x === false || $x === null || (is_array($x) && empty($x))) {
                    return $nullAsZero ? 0 : null;
                }
                return is_numeric($x) ? 0+$x : 0;
            },
            'coalesce'  => function(...$args){
                foreach ($args as $a) {
                    if ($a !== null && $a !== '' && $a !== [] && $a !== false) return $a;
                }
                return null;
            },
            'min'       => fn(...$xs) => min(...array_map(fn($x)=>0+$x, $xs)),
            'max'       => fn(...$xs) => max(...array_map(fn($x)=>0+$x, $xs)),
            'abs'       => fn($x) => abs(0+$x),
        ];

        // 3) Seguridad básica: bloquear caracteres peligrosos tras reemplazos
        //    (no deberían existir si la UI es correcta)
        if ($this->looksUnsafe($exprPHP)) {
            return null;
        }

        // 4) Expone helpers a la expresión y evalúa
        $col       = $helpers['col'];
        $fattr     = $helpers['fattr'];
        $empresa   = $helpers['empresa'];
        $sum_rows  = $helpers['sum_rows'];
        $concat    = $helpers['concat'];
        $round2    = $helpers['round2'];
        $n         = $helpers['n'];
        $coalesce  = $helpers['coalesce'];
        $min       = $helpers['min'];
        $max       = $helpers['max'];
        $abs       = $helpers['abs'];

        try {
            // Importante: solo se evaluará lo ya transformado, con helpers controlados.
            // Ejemplos válidos:
            //   2 * col('productos','precio', 10)
            //   concat("Lote ", "A1")
            //   round2( ({{cantidad}}) * ({{precio}}), 2 ) -> ya vino en $exprPHP
            $result = eval('return ' . $exprPHP . ';');
        } catch (\Throwable $e) {
            $result = null;
        }

        // 5) Casteo final según output_type
        return match($field->formula?->output_type) {
            'int'     => (int)($result ?? 0),
            'decimal' => (float)($result ?? 0),
            'boolean' => (bool)$result,
            'date', 'text' => $result,
            default => $result,
        };
    }

    /* ===================== Helpers internos ===================== */

    /**
     * Obtiene valor por dot-notation (similar a data_get)
     */
    private function arrGet(array $arr, string $path, mixed $default=null): mixed
    {
        if ($path === '' || $path === null) return $default;
        if (!str_contains($path, '.')) return $arr[$path] ?? $default;
        $segments = explode('.', $path);
        $cur = $arr;
        foreach ($segments as $seg) {
            if (is_array($cur) && array_key_exists($seg, $cur)) {
                $cur = $cur[$seg];
            } else {
                return $default;
            }
        }
        return $cur;
    }

    /**
     * Convierte un valor PHP a literal embebible en la expresión evaluada
     */
    private function toPhpLiteral(mixed $v): string
    {
        if (is_null($v))     return 'null';
        if (is_bool($v))     return $v ? 'true' : 'false';
        if (is_int($v))      return (string)$v;
        if (is_float($v))    return (string)$v;
        if (is_array($v))    return '"'.addslashes(json_encode($v, JSON_UNESCAPED_UNICODE)).'"';
        // string u otros
        return '"'.addslashes((string)$v).'"';
    }

    /**
     * Bloquea tokens potencialmente peligrosos tras la sustitución.
     * Evita ; $ <? > backticks, y control chars.
     */
    private function looksUnsafe(string $s): bool
    {
        // no permitir inicio/fin de tag PHP, variables, ;, backticks, llaves
        if (preg_match('/(<\?php|\?>|\$|;|`|{\s*|}\s*|\x00|\x1F)/i', $s)) return true;
        return false;
    }
}
