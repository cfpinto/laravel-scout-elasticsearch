<?php
/**
 * Created by PhpStorm.
 * User: claudiopinto
 * Date: 09/10/2018
 * Time: 10:06
 */

namespace ScoutEngines\Elasticsearch;


use Elasticsearch\ClientBuilder;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;
use ScoutEngines\Elasticsearch\Engines\ElasticSearchEngine;

class ScoutServiceProvider extends ServiceProvider
{
    public function boot()
    {
        resolve(EngineManager::class)->extend('elasticsearch', function () {
            return new ElasticSearchEngine(
                ClientBuilder::create()
                ->setHosts(config('scout.elasticsearch.hosts', 'localhost'))
                ->build(),
                config('scout.elasticsearch.index')
            );
        });

        Builder::macro('count', function () {
            return $this->engine()->getTotalCount(
                $this->engine()->search($this)
            );
        });
    }
}