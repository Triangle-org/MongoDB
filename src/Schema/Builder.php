<?php

namespace Triangle\MongoDB\Schema;

use Closure;
use MongoDB\Model\CollectionInfo;

/**
 *
 */
class Builder extends \Illuminate\Database\Schema\Builder
{
    /**
     * @inheritdoc
     */
    public function hasColumn($table, $column)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function hasColumns($table, array $columns)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function hasTable($collection)
    {
        return $this->hasCollection($collection);
    }

    /**
     * Determine if the given collection exists.
     * @param string $name
     * @return bool
     */
    public function hasCollection($name)
    {
        $db = $this->connection->getMongoDB();

        $collections = iterator_to_array($db->listCollections([
            'filter' => [
                'name' => $name,
            ],
        ]), false);

        return count($collections) ? true : false;
    }

    /**
     * @inheritdoc
     */
    public function table($collection, Closure $callback)
    {
        return $this->collection($collection, $callback);
    }

    /**
     * Modify a collection on the schema.
     * @param string $collection
     * @param Closure $callback
     * @return void
     */
    public function collection($collection, Closure $callback): void
    {
        $blueprint = $this->createBlueprint($collection);

        $callback($blueprint);
    }

    /**
     * @inheritdoc
     */
    protected function createBlueprint($collection, Closure $callback = null): \Illuminate\Database\Schema\Blueprint|Blueprint
    {
        return new Blueprint($this->connection, $collection);
    }

    /**
     * @inheritdoc
     */
    public function create($collection, Closure $callback = null, array $options = []): void
    {
        $blueprint = $this->createBlueprint($collection);

        $blueprint->create($options);

        if ($callback) {
            $callback($blueprint);
        }
    }

    /**
     * @inheritdoc
     */
    public function dropIfExists($collection)
    {
        if ($this->hasCollection($collection)) {
            return $this->drop($collection);
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function drop($collection)
    {
        $blueprint = $this->createBlueprint($collection);

        return $blueprint->drop();
    }

    /**
     * @inheritdoc
     */
    public function dropAllTables(): void
    {
        foreach ($this->getAllCollections() as $collection) {
            $this->drop($collection);
        }
    }

    /**
     * Get all the collections names for the database.
     * @return array
     */
    protected function getAllCollections(): array
    {
        $collections = [];
        foreach ($this->connection->getMongoDB()->listCollections() as $collection) {
            $collections[] = $collection->getName();
        }

        return $collections;
    }

    /**
     * Get collection.
     * @param string $name
     * @return bool|CollectionInfo
     */
    public function getCollection($name): CollectionInfo|bool
    {
        $db = $this->connection->getMongoDB();

        $collections = iterator_to_array($db->listCollections([
            'filter' => [
                'name' => $name,
            ],
        ]), false);

        return count($collections) ? current($collections) : false;
    }
}
