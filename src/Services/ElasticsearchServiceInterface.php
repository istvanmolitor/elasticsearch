<?php

namespace Molitor\Elasticsearch\Services;

interface ElasticsearchServiceInterface
{
    public function index(string $index, string $id, array $document): void;

    public function search(string $index, string $keyword): array;

    public function delete(string $index, string $id): void;
}
