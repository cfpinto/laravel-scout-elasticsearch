<?php
/**
 * Created by PhpStorm.
 * User: claudiopinto
 * Date: 09/10/2018
 * Time: 10:04
 */

namespace ScoutEngines\Elasticsearch\Engines;


use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Laravel\Scout\Searchable;

/**
 * Class ElasticSearchEngine
 *
 * @package ScoutEngines\Engines
 */
class ElasticSearchEngine extends Engine
{
    /**
     * @var string
     */
    protected $index;

    /**
     * @var Client
     */
    protected $elastic;

    /**
     * ElasticSearchEngine constructor.
     *
     * @param Client $elastic
     * @param        $index
     */
    public function __construct(Client $elastic, $index)
    {
        $this->elastic = $elastic;
        $this->index = $index;
    }

    /**
     * Update the given model in the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection $models
     *
     * @return void
     * @throws \Exception
     */
    public function update($models)
    {
        $params['body'] = [];
        $models->each(
            function ($model) use (&$params) {
                /** @var Searchable|\ScoutEngines\Elasticsearch\Traits\Searchable $model */
                $params['body'][] = [
                    'update' => [
                        '_id'    => $this->getElasticKey($model),
                        '_index' => $this->getIndex($model),
                        '_type'  => $model->searchableAs(),
                    ]
                ];
                $params['body'][] = [
                    'doc'           => $model->toSearchableArray(),
                    'doc_as_upsert' => true
                ];
            }
        );
        $result = $this->elastic->bulk($params);

        if ($result['errors']) {
            throw new \Exception("Error performing bulk insert." . json_encode($result));
        }
    }

    /**
     * Remove the given model from the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection $models
     *
     * @return void
     * @throws \Exception
     */
    public function delete($models)
    {
        $params['body'] = [];
        $models->each(
            function ($model) use (&$params) {
                /** @var Searchable $model */
                $params['body'][] = [
                    'delete' => [
                        '_id'    => $this->getElasticKey($model),
                        '_index' => $this->getIndex($model),
                        '_type'  => $model->searchableAs(),
                    ]
                ];
            }
        );
        
        $result = $this->elastic->bulk($params);

        if ($result['errors']) {
            throw new \Exception("Error performing bulk insert." . json_encode($result));
        }
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder $builder
     *
     * @return mixed
     */
    public function search(Builder $builder)
    {
        return $this->performSearch(
            $builder, array_filter(
                        [
                            'numericFilters' => $this->filters($builder),
                            'size'           => $builder->limit,
                        ]
                    )
        );
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder $builder
     * @param  int                    $perPage
     * @param  int                    $page
     *
     * @return mixed
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $result = $this->performSearch(
            $builder, [
                        'numericFilters' => $this->filters($builder),
                        'from'           => (($page * $perPage) - $perPage),
                        'size'           => $perPage,
                    ]
        );
        $result['nbPages'] = $result['hits']['total'] / $perPage;

        return $result;
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param  mixed $results
     *
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        return collect($results['hits']['hits'])->pluck('_id')->values();
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param  \Laravel\Scout\Builder              $builder
     * @param  mixed                               $results
     * @param  \Illuminate\Database\Eloquent\Model $model
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        if ($results['hits']['total'] === 0) {
            return Collection::make();
        }

        return collect($results['hits']['hits'])->map(
            function ($hit) use ($model) {
                $source = $hit['_source'];

                return isset($source['_serialized']) ? unserialize($source['_serialized']) : $model->newFromBuilder($source, $model->getConnectionName());
            }
        );
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param  mixed $results
     *
     * @return int
     */
    public function getTotalCount($results)
    {
        return $results['hits']['total'];
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  Builder $builder
     * @param  array   $options
     *
     * @return mixed
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        $params = [
            'index' => $this->getIndex($builder->model),
            'type'  => $builder->index ?: $builder->model->searchableAs(),
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'query_string' => [
                                    'query' => !empty(trim($builder->query, '*\s')) ? "*{$builder->query}*" : "*"
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if (method_exists($builder->model, 'getFields') && is_array($builder->model->getFields())) {
            $params['body']['query']['bool']['must'][0]['query_string']['fields'] = $builder->model->getFields();
        }
        if ($sort = $this->sort($builder)) {
            $params['body']['sort'] = $sort;
        }
        if (isset($options['from'])) {
            $params['body']['from'] = $options['from'];
        }
        if (isset($options['size'])) {
            $params['body']['size'] = $options['size'];
        }
        if (isset($options['numericFilters']) && count($options['numericFilters'])) {
            $params['body']['query']['bool']['must'] = array_merge(
                $params['body']['query']['bool']['must'],
                $options['numericFilters']
            );
        }

        if ($builder->callback) {
            return call_user_func(
                $builder->callback,
                $this->elastic,
                $builder->query,
                $params
            );
        }

        return $this->elastic->search($params);
    }

    /**
     * Get the filter array for the query.
     *
     * @param  Builder $builder
     *
     * @return array
     */
    protected function filters(Builder $builder)
    {
        if ($builder instanceof \ScoutEngines\Elasticsearch\Builder) {
            return ($builder->toESDL());
        }

        return collect($builder->wheres)->map(
            function ($value, $key) {
                if (is_array($value)) {
                    return ['terms' => [$key => $value]];
                }

                return ['match_phrase' => [$key => $value]];
            }
        )->values()->all();
    }

    /**
     * Generates the sort if there's any.
     *
     * @param  Builder $builder
     *
     * @return array|null
     */
    protected function sort($builder)
    {
        if (count($builder->orders) == 0) {
            return null;
        }

        return collect($builder->orders)->map(
            function ($order) {
                return [$order['column'] => $order['direction']];
            }
        )->toArray();
    }

    /**
     * @param Searchable|Model $model
     *
     * @return mixed
     */
    protected function getElasticKey($model)
    {
        if (method_exists($model, 'getScoutKey')) {
            return $model->getScoutKey();
        }

        return $model->getKey();
    }

    /**
     * @param Searchable $model
     *
     * @return string
     */
    protected function getIndex($model)
    {
        return $this->index . '_' . $model->searchableAs();
    }
}