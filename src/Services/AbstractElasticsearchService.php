<?php

declare(strict_types=1);

namespace Molitor\Elasticsearch\Services;

use Elastic\Elasticsearch\Client;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractElasticsearchService
{
    public function __construct(protected Client $client) {}

    abstract protected function getIndexName(): string;

    abstract protected function getSettings(): array;

    abstract protected function getMappings(): array;

    abstract protected function buildSearchQuery(string $query, array $options): array;

    abstract protected function prepareDocument(Model $model): array;

    abstract protected function getModelClass(): string;

    public function indexModel(Model $model): void
    {
        $this->client->index([
            'index' => $this->getIndexName(),
            'id' => (string) $model->getKey(),
            'body' => $this->prepareDocument($model),
        ]);
    }

    public function deleteDocument(int $id): void
    {
        try {
            $this->client->delete([
                'index' => $this->getIndexName(),
                'id' => (string) $id,
            ]);
        } catch (\Throwable) {
            // Document may not exist; ignore
        }
    }

    public function search(string $query, array $options = []): array
    {
        $page = (int) ($options['page'] ?? 1);
        $perPage = (int) ($options['per_page'] ?? 10);

        $body = array_merge(
            $this->buildSearchQuery($query, $options),
            [
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
            ],
        );

        $response = $this->client->search([
            'index' => $this->getIndexName(),
            'body' => $body,
        ]);

        return [
            'hits' => array_map(
                fn ($hit) => array_merge(['_id' => $hit['_id']], $hit['_source']),
                $response['hits']['hits'],
            ),
            'total' => $response['hits']['total']['value'] ?? 0,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function searchModel(string $query, array $options = []): array
    {
        $result = $this->search($query, $options);

        $ids = array_column($result['hits'], '_id');

        $modelClass = $this->getModelClass();
        $models = $modelClass::whereIn('id', $ids)
            ->orderByRaw('FIELD(id, ' . implode(',', array_map('intval', $ids)) . ')')
            ->get();

        return array_merge($result, ['hits' => $models]);
    }

    public function reindexAll(callable $iterator): int
    {
        $this->recreateIndex();

        $count = 0;
        $iterator(function ($models) use (&$count) {
            foreach ($models as $model) {
                $this->indexModel($model);
                $count++;
            }
        });

        return $count;
    }

    private function recreateIndex(): void
    {
        $index = $this->getIndexName();

        if ($this->client->indices()->exists(['index' => $index])->asBool()) {
            $this->client->indices()->delete(['index' => $index]);
        }

        $this->client->indices()->create([
            'index' => $index,
            'body' => [
                'settings' => $this->getSettings(),
                'mappings' => $this->getMappings(),
            ],
        ]);
    }
}
