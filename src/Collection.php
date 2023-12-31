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

namespace Triangle\MongoDB;

use Exception;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Collection as MongoCollection;

/**
 *
 */
class Collection
{
    /**
     * The connection instance.
     * @var Connection
     */
    protected $connection;

    /**
     * The MongoCollection instance..
     * @var MongoCollection
     */
    protected $collection;

    /**
     * @param Connection $connection
     * @param MongoCollection $collection
     */
    public function __construct(Connection $connection, MongoCollection $collection)
    {
        $this->connection = $connection;
        $this->collection = $collection;
    }

    /**
     * Handle dynamic method calls.
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $start = microtime(true);
        $result = call_user_func_array([$this->collection, $method], $parameters);

        // Once we have run the query we will calculate the time that it took to run and
        // then log the query, bindings, and execution time so we will report them on
        // the event that the developer needs them. We'll log time in milliseconds.
        $time = $this->connection->getElapsedTime($start);

        $query = [];

        // Convert the query parameters to a json string.
        array_walk_recursive($parameters, function (&$item, $key) {
            if ($item instanceof ObjectIdInterface) {
                $item = (string)$item;
            }
        });

        // Convert the query parameters to a json string.
        foreach ($parameters as $parameter) {
            try {
                $query[] = json_encode($parameter);
            } catch (Exception $e) {
                $query[] = '{...}';
            }
        }

        $queryString = $this->collection->getCollectionName() . '.' . $method . '(' . implode(',', $query) . ')';

        $this->connection->logQuery($queryString, [], $time);

        return $result;
    }
}
