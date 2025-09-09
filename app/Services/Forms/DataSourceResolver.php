<?php
namespace App\Services\Forms;

use App\Models\FormField;
use Illuminate\Support\Facades\DB;

class DataSourceResolver {
    public function options(FormField $field, array $context = []): array {
        $src = $field->source;
        if(!$src) return [];

        switch ($src->source_kind) {
            case 'table_column':
                // label estándar: nombre || razon_social || descripcion || codigo
                $labelCols = ['nombre','razon_social','descripcion','codigo','name'];
                $label = $src->column_name;
                foreach ($labelCols as $c) {
                    if ($this->colExists($src->table_name, $c)) { $label = $c; break; }
                }
                return DB::table($src->table_name)
                    ->select('id as value', DB::raw("$label as label"))
                    ->where('id_emp', $context['id_emp'] ?? null)
                    ->orderBy($label)->limit(500)->get()->toArray();

            case 'ficha_attr':
                // valores desde datos_atributos_fichas según tu esquema
                return DB::table('datos_atributos_fichas as d')
                    ->join('atributo_ficha as a','a.id','=','d.id_atributo')
                    ->where('a.id', $src->atributo_id)
                    ->select('d.id_relacion as value','d.dato as label')
                    ->limit(500)->get()->toArray();

            case 'query':
                // Acepta solo SELECT; sanitiza que no tenga ; ni palabras peligrosas
                $sql = trim($src->query_sql);
                if (stripos($sql,'select') !== 0) return [];
                return DB::select($sql);

            case 'static_options':
                return $src->options_json ?? [];
        }
        return [];
    }

    private function colExists(string $table, string $column): bool {
        return \Schema::hasColumn($table, $column);
    }

    public function getLabelById(string $table, int $id, string $column = 'nombre'): ?string
    {
        return \DB::table($table)->where('id', $id)->value($column);
    }

    public function getValue(string $table, int $id, string $column): mixed
    {
        return \DB::table($table)->where('id', $id)->value($column);
    }

    public function empresaValue(int $empId, string $column): mixed
    {
        return \DB::table('empresa')->where('id', $empId)->value($column);
    }

    public function fichaAttrValue(int $idFicha, int $idRelacion, int $atributoId): mixed
    {
        // si lo necesitas: lee valor desde datos_atributos_fichas
        return \DB::table('datos_atributos_fichas')
            ->where('id_relacion', $idRelacion)
            ->where('id_atributo', $atributoId)
            ->value('dato');
    }
}
