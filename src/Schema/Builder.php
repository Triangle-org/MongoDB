<?php

declare(strict_types=1);

/**
 * @package     Triangle MongoDB Plugin
 * @link        https://github.com/Triangle-org/MongoDB
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2024 Localzet Group
 * @license     GNU Affero General Public License, version 3
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as
 *              published by the Free Software Foundation, either version 3 of the
 *              License, or (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

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
