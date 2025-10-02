<?php

namespace App\Models\Traits;

use App\Models\FichaGroupDef;
use App\Models\FichaListItem;
use App\Models\FichaRelationLink;

trait HasFichaExtras
{
    // Debes implementar en cada modelo un: public function fichaEntityType(): string
    abstract public function fichaEntityType(): string;

    public function fichaActiveGroups()
    {
        return FichaGroupDef::for($this->fichaEntityType());
    }

    /** LIST: obtener items de un group_code */
    public function getFichaList(string $groupCode)
    {
        return FichaListItem::query()
            ->where('entity_type', $this->fichaEntityType())
            ->where('entity_id', $this->getKey())
            ->where('group_code', $groupCode)
            ->orderBy('sort_order')
            ->get();
    }

    /** LIST: reemplazar todos los items (rápido para store/update) */
    public function saveFichaList(string $groupCode, array $items): void
    {
        FichaListItem::query()
            ->where('entity_type', $this->fichaEntityType())
            ->where('entity_id', $this->getKey())
            ->where('group_code', $groupCode)
            ->delete();

        $n = 1;
        foreach ($items as $item) {
            FichaListItem::create([
                'entity_type' => $this->fichaEntityType(),
                'entity_id'   => $this->getKey(),
                'group_code'  => $groupCode,
                'value_json'  => $item,
                'sort_order'  => $n++,
            ]);
        }
    }

    /** REL: obtener IDs relacionados por group_code */
    public function getFichaRelated(string $groupCode, string $relatedEntityType)
    {
        return FichaRelationLink::query()
            ->where('entity_type', $this->fichaEntityType())
            ->where('entity_id', $this->getKey())
            ->where('group_code', $groupCode)
            ->where('related_entity_type', $relatedEntityType)
            ->pluck('related_entity_id')
            ->all();
    }

    /** REL: sincronizar relacionados (reemplaza todo) */
    public function syncFichaRelated(string $groupCode, string $relatedEntityType, array $ids): void
    {
        FichaRelationLink::query()
            ->where('entity_type', $this->fichaEntityType())
            ->where('entity_id', $this->getKey())
            ->where('group_code', $groupCode)
            ->where('related_entity_type', $relatedEntityType)
            ->delete();

        $ids = array_values(array_unique(array_filter($ids)));
        foreach ($ids as $id) {
            FichaRelationLink::create([
                'entity_type'         => $this->fichaEntityType(),
                'entity_id'           => $this->getKey(),
                'group_code'          => $groupCode,
                'related_entity_type' => $relatedEntityType,
                'related_entity_id'   => $id,
            ]);
        }
    }
}
