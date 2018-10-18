# Laravel Scout Elasticsearch Driver

**Disclaimer**: This project was highly inspired in [ErickTamayo](https://github.com/ErickTamayo/laravel-scout-elastic) implementation. However it is not a fork and most likely is incompatible.

## Installation

```bash
composer require cfpinto/laravel-scout-elasticsearch
```  

If your project doesn't have auto discovery you will need to add the providers manually in your app.php config file   

```php
'providers' => [
	...
	Laravel\Scout\ScoutServiceProvider::class,
	...
	ScoutEngines\Elasticsearch\ElasticsearchProvider::class
],
```

### Setup Elasticsearch configuration

You must have an Elasticsearch instance running with the necessary index. 

Change the config/scout.php file to include elasticsearch settings

```php
// config/scout.php
// Set your driver to elasticsearch
    'driver' => env('SCOUT_DRIVER', 'elasticsearch'),

...
    'elasticsearch' => [
        'index' => env('ELASTICSEARCH_INDEX', 'laravel'),
        'hosts' => [
            env('ELASTICSEARCH_HOST', 'http://localhost'),
        ],
    ],
...
```

## Usage

The driver will work just like described in laravel scout [documentation](https://laravel.com/docs/5.5/scout)

## Credits

- [Claudio Pinto](https://github.com/cfpinto)
- [Erick Tamayo](https://github.com/ericktamayo)
- [All Contributors](https://github.com/cfpinto/laravel-scout-elastic/contributors)