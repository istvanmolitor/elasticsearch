<?php

namespace Molitor\Elasticsearch\Providers;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\ServiceProvider;
use Molitor\Elasticsearch\Services\ElasticsearchService;
use Molitor\Elasticsearch\Services\ElasticsearchServiceInterface;

class ElasticsearchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/elasticsearch.php' => config_path('elasticsearch.php'),
        ], 'elasticsearch-config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/elasticsearch.php', 'elasticsearch');

        $this->app->singleton(Client::class, function () {
            $builder = ClientBuilder::create()
                ->setHosts([
                    sprintf(
                        '%s://%s:%d',
                        config('elasticsearch.scheme', 'http'),
                        config('elasticsearch.host', 'elasticsearch'),
                        config('elasticsearch.port', 9200),
                    ),
                ]);

            $username = config('elasticsearch.username');
            $password = config('elasticsearch.password');

            if ($username && $password) {
                $builder->setBasicAuthentication($username, $password);
            }

            return $builder->build();
        });

        $this->app->singleton(ElasticsearchServiceInterface::class, ElasticsearchService::class);
    }
}
