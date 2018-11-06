<?php
/**
 * Created by PhpStorm.
 * User: claudiopinto
 * Date: 05/11/2018
 * Time: 13:32
 */

namespace ScoutEngines\Elasticsearch;

use Closure;

class Builder extends \Laravel\Scout\Builder
{
    const TYPE_AND = 'AND';
    const TYPE_OR = 'OR';
    const VALID_TYPES = [self::TYPE_AND, self::TYPE_OR];

    /**
     * @param string|Closure $field
     * @param null   $value
     *
     * @return $this|\Laravel\Scout\Builder
     * @throws \Exception
     */
    public function where($field, $value = null)
    {
        if ($field instanceof Closure) {
            $field($query = new self($this->model, $this->query));
            $this->nestedWhere($query);

            return $this;
        }
        $this->addWhere($field, $value);

        return $this;
    }

    /**
     * @param string|Closure $field
     * @param null           $value
     *
     * @return $this
     * @throws \Exception
     */
    public function orWhere($field, $value = null)
    {
        if (is_callable($field)) {
            $field($query = new self($this->model, $this->query));
            $this->nestedWhere($query, self::TYPE_OR);

            return $this;
        }
        $this->addWhere($field, $value, self::TYPE_OR);

        return $this;
    }

    /**
     * @return string
     */
    public function toSql()
    {
        return $this->parseGroup($this->wheres);
    }

    /**
     * @return array
     */
    public function toESDL()
    {
        return $this->parseEsGroup($this->wheres);
    }

    /**
     * @param array $list
     *
     * @return array
     */
    private function parseEsGroup($list = [])
    {
        $query = [];
        if (count($list)) {
            $ands = [];
            $ors = [];
            foreach ($list as $item) {
                if ($item['type'] == self::TYPE_OR) {
                    $ors[] = $item;
                } else {
                    $ands[] = $item;
                }
            }

            $tmp = [
                'bool' => []
            ];

            if (count($ors)) {
                $tmp['bool'] = ['should' => []];
                $using = &$tmp['bool']['should'];

                foreach ($ors as $where) {
                    $using[] = $this->parseEsWhere($where);
                }

                if (count($ands)) {
                    $using[] = [
                        'bool' => [
                            'must' => $this->parseEsGroup($ands)
                        ]
                    ];
                }
            } else {
                $tmp['bool'] = ['must' => []];
                $using = &$tmp['bool']['must'];

                foreach ($ands as $where) {
                    $using[] = $this->parseEsWhere($where);
                }
            }

            $query[] = $tmp;
        }

        return $query;
    }

    /**
     * @param $where
     *
     * @return array|null
     */
    private function parseEsWhere($where)
    {
        if (isset($where['field'])) {
            return $this->parseEsEntry($where);
        }

        $group = $this->parseEsGroup($where['group']);

        return $group[0] ?? null;
    }

    /**
     * @param $where
     *
     * @return array
     */
    private function parseEsEntry($where)
    {
        if (is_array($where['value'])) {
            return [
                'terms' => [
                    $where['field'] => $where['value']
                ]
            ];
        }

        return [
            'match_phrase' => [
                $where['field'] => $where['value']
            ]
        ];
    }

    /**
     * @param array $list
     *
     * @return string
     */
    private function parseGroup($list = [])
    {
        $sql = '';
        foreach ($list as $i => $item) {
            if ($i > 0) {
                $sql .= ' ' . $item['type'] . ' ';
            }

            if (isset($item['field'])) {
                $sql .= sprintf('%s = %s', $item['field'], $item['value']);
            } elseif (!empty($item['group'])) {
                $sql .= sprintf('(%s)', $this->parseGroup($item['group']));
            }
        }

        return $sql;
    }

    /**
     * @param        $field
     * @param        $value
     * @param string $type
     *
     * @throws \Exception
     */
    private function addWhere($field, $value, $type = self::TYPE_AND)
    {
        if (!in_array($type, self::VALID_TYPES)) {
            throw new \Exception(sprintf('Invalid type %s', $type));
        }

        $this->wheres[] = [
            'field' => $field,
            'value' => $value,
            'type'  => $type,
        ];
    }

    /**
     * @param Builder $query
     * @param string  $type
     *
     * @throws \Exception
     */
    private function nestedWhere(Builder $query, $type = self::TYPE_AND)
    {
        if (!in_array($type, self::VALID_TYPES)) {
            throw new \Exception(sprintf('Invalid type %s', $type));
        }

        if (count($query->wheres)) {
            $this->wheres[] = [
                'type'  => $type,
                'group' => $query->wheres,
            ];
        }
    }
}