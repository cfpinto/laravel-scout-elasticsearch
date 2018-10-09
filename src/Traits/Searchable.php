<?php
/**
 * Created by PhpStorm.
 * User: claudiopinto
 * Date: 09/10/2018
 * Time: 11:50
 */

namespace ScoutEngines\Elasticsearch\Traits;


use Laravel\Scout\Searchable as SearchableTrait;

trait Searchable
{
    use SearchableTrait;

    /**
     * @return array
     */
    public function getPreload(): array
    {
        return $this->preload;
    }

    /**
     * @param array $preload
     */
    public function setPreload(array $preload): void
    {
        $this->preload = $preload;
    }

    public function getFields()
    {
        return $this->searchable;
    }
    
    public function setFields(array $fields)
    {
        $this->searchable = $fields;
    }

    public function toSearchableArray()
    {
        foreach ($this->getPreload() as $relation) {
            $this->getAttribute($relation);
        }
        $data = $this->toArray();
        $data['_serialized'] = serialize($this);

        return $data;
    }
}