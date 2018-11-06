# Laravel Scout Elasticsearch Driver

**Disclaimer**: This project was highly inspired in [ErickTamayo](https://github.com/ErickTamayo/laravel-scout-elastic) implementation. However it is not a fork and most likely is incompatible.

This package is intented to be used with laravel 5.6 and up. Others versions might work but might  

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

The driver will work just like described in laravel scout [documentation](https://laravel.com/docs/5.5/scout).

If you want the ability to use closures in where and orWhere methods your model must use the Searchable trait included in the package

```php
<?php
namespace App;

use ScoutEngines\Elasticsearch\Traits\Searchable;

class Product extends Model
{
	use Searchable; 
}
```

## Credits

- [Claudio Pinto](https://github.com/cfpinto)
- [Erick Tamayo](https://github.com/ericktamayo)
- [All Contributors](https://github.com/cfpinto/laravel-scout-elastic/contributors)
## Support on Beerpay
Hey dude! Help me out for a couple of :beers:!

[![Beerpay](https://beerpay.io/cfpinto/laravel-scout-elasticsearch/badge.svg?style=beer-square)](https://beerpay.io/cfpinto/laravel-scout-elasticsearch)  [![Beerpay](https://beerpay.io/cfpinto/laravel-scout-elasticsearch/make-wish.svg?style=flat-square)](https://beerpay.io/cfpinto/laravel-scout-elasticsearch?focus=wish)