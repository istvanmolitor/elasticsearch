<?php

namespace Molitor\Elasticsearch\Services;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticsearchService implements ElasticsearchServiceInterface
{
    private Client $client;

    public function __construct()
    {
        $builder = ClientBuilder::create()
            ->setHosts([
                sprintf(
                    '%s://%s:%d',
                    config('elasticsearch.scheme'),
                    config('elasticsearch.host'),
                    config('elasticsearch.port'),
                ),
            ]);

        $username = config('elasticsearch.username');
        $password = config('elasticsearch.password');

        if ($username && $password) {
            $builder->setBasicAuthentication($username, $password);
        }

        $this->client = $builder->build();
    }

    public function index(string $index, string $id, array $document): void
    {
        $this->client->index([
            'index' => $index,
            'id'    => $id,
            'body'  => $document,
        ]);
    }

    public function search(string $index, string $keyword): array
    {
        $response = $this->client->search([
            'index' => $index,
            'body'  => [
                'query' => [
                    'multi_match' => [
                        'query'  => $keyword,
                        'fields' => ['*'],
                    ],
                ],
            ],
        ]);

        return array_map(
            fn ($hit) => array_merge(['_id' => $hit['_id']], $hit['_source']),
            $response['hits']['hits'],
        );
    }

    public function delete(string $index, string $id): void
    {
        $this->client->delete([
            'index' => $index,
            'id'    => $id,
        ]);
    }
}
