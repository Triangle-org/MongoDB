<?php

declare(strict_types=1);

/**
 * @package     Triangle MongoDB Plugin
 * @link        https://github.com/Triangle-org/MongoDB
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2023 Localzet Group
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

namespace Triangle\MongoDB\Helpers;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Triangle\MongoDB\Builder;
use Triangle\MongoDB\Model;

/**
 *
 */
trait QueriesRelationships
{
    /**
     * Add a relationship count / exists condition to the query.
     * @param Relation|string $relation
     * @param string $operator
     * @param int $count
     * @param string $boolean
     * @param Closure|null $callback
     * @return Builder|static
     * @throws Exception
     */
    public function has($relation, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null): Builder|static
    {
        if (is_string($relation)) {
            if (str_contains($relation, '.')) {
                return $this->hasNested($relation, $operator, $count, $boolean, $callback);
            }

            $relation = $this->getRelationWithoutConstraints($relation);
        }

        // If this is a hybrid relation then we can not use a normal whereExists() query that relies on a sub-query
        // We need to use a `whereIn` query
        if ($this->getModel() instanceof Model || $this->isAcrossConnections($relation)) {
            return $this->addHybridHas($relation, $operator, $count, $boolean, $callback);
        }

        // If we only need to check for the existence of the relation, then we can optimize
        // the subquery to only run a "where exists" clause instead of this full "count"
        // clause. This will make these queries run much faster compared with a count.
        $method = $this->canUseExistsForExistenceCheck($operator, $count)
            ? 'getRelationExistenceQuery'
            : 'getRelationExistenceCountQuery';

        $hasQuery = $relation->{$method}(
            $relation->getRelated()->newQuery(), $this
        );

        // Next we will call any given callback as an "anonymous" scope so they can get the
        // proper logical grouping of the where clauses if needed by this Eloquent query
        // builder. Then, we will be ready to finalize and return this query instance.
        if ($callback) {
            $hasQuery->callScope($callback);
        }

        return $this->addHasWhere(
            $hasQuery, $relation, $operator, $count, $boolean
        );
    }

    /**
     * @param Relation $relation
     * @return bool
     */
    protected function isAcrossConnections(Relation $relation): bool
    {
        return $relation->getParent()->getConnectionName() !== $relation->getRelated()->getConnectionName();
    }

    /**
     * Compare across databases.
     * @param Relation $relation
     * @param string $operator
     * @param int $count
     * @param string $boolean
     * @param Closure|null $callback
     * @return EloquentBuilder|Builder
     * @throws Exception
     */
    public function addHybridHas(Relation $relation, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null): EloquentBuilder|Builder
    {
        $hasQuery = $relation->getQuery();
        if ($callback) {
            $hasQuery->callScope($callback);
        }

        // If the operator is <, <= or !=, we will use whereNotIn.
        $not = in_array($operator, ['<', '<=', '!=']);
        // If we are comparing to 0, we need an additional $not flip.
        if ($count == 0) {
            $not = !$not;
        }

        $relations = $hasQuery->pluck($this->getHasCompareKey($relation));

        $relatedIds = $this->getConstrainedRelatedIds($relations, $operator, $count);

        return $this->whereIn($this->getRelatedConstraintKey($relation), $relatedIds, $boolean, $not);
    }

    /**
     * @param Relation $relation
     * @return string
     */
    protected function getHasCompareKey(Relation $relation): string
    {
        if (method_exists($relation, 'getHasCompareKey')) {
            return $relation->getHasCompareKey();
        }

        return $relation instanceof HasOneOrMany ? $relation->getForeignKeyName() : $relation->getOwnerKeyName();
    }

    /**
     * @param $relations
     * @param $operator
     * @param $count
     * @return array
     */
    protected function getConstrainedRelatedIds($relations, $operator, $count): array
    {
        $relationCount = array_count_values(array_map(function ($id) {
            return (string)$id; // Convert Back ObjectIds to Strings
        }, is_array($relations) ? $relations : $relations->flatten()->toArray()));
        // Remove unwanted related objects based on the operator and count.
        $relationCount = array_filter($relationCount, function ($counted) use ($count, $operator) {
            // If we are comparing to 0, we always need all results.
            if ($count == 0) {
                return true;
            }
            switch ($operator) {
                case '>=':
                case '<':
                    return $counted >= $count;
                case '>':
                case '<=':
                    return $counted > $count;
                case '=':
                case '!=':
                    return $counted == $count;
            }
        });

        // All related ids.
        return array_keys($relationCount);
    }

    /**
     * Returns key we are constraining this parent model's query with.
     * @param Relation $relation
     * @return string
     * @throws Exception
     */
    protected function getRelatedConstraintKey(Relation $relation): string
    {
        if ($relation instanceof HasOneOrMany) {
            return $relation->getLocalKeyName();
        }

        if ($relation instanceof BelongsTo) {
            return $relation->getForeignKeyName();
        }

        if ($relation instanceof BelongsToMany && !$this->isAcrossConnections($relation)) {
            return $this->model->getKeyName();
        }

        throw new Exception(class_basename($relation) . ' is not supported for hybrid query constraints.');
    }
}
