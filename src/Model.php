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

namespace Triangle\MongoDB;

use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Queue\QueueableCollection;
use Illuminate\Contracts\Queue\QueueableEntity;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\BSON\UTCDateTime;
use Triangle\MongoDB\Query\Builder as QueryBuilder;

/**
 * @method static Model make($attributes = [])
 * @method static Builder withGlobalScope($identifier, $scope)
 * @method static Builder withoutGlobalScope($scope)
 * @method static Builder withoutGlobalScopes($scopes = null)
 * @method static array removedScopes()
 * @method static Builder whereKey($id)
 * @method static Builder whereKeyNot($id)
 * @method static Builder where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Model|null firstWhere($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Builder orWhere($column, $operator = null, $value = null)
 * @method static Builder latest($column = null)
 * @method static Builder oldest($column = null)
 * @method static EloquentCollection hydrate($items)
 * @method static EloquentCollection fromQuery($query, $bindings = [])
 * @method static Model|EloquentCollection|static[]|static|null find($id, $columns = [])
 * @method static EloquentCollection findMany($ids, $columns = [])
 * @method static Model|EloquentCollection|static|static[] findOrFail($id, $columns = [])
 * @method static Model|static findOrNew($id, $columns = [])
 * @method static Model|static firstOrNew($attributes = [], $values = [])
 * @method static Model|static firstOrCreate($attributes = [], $values = [])
 * @method static Model|static updateOrCreate($attributes, $values = [])
 * @method static Model|static firstOrFail($columns = [])
 * @method static Model|static|mixed firstOr($columns = [], $callback = null)
 * @method static Model sole($columns = [])
 * @method static mixed value($column)
 * @method static EloquentCollection[]|static[] get($columns = [])
 * @method static Model[]|static[] getModels($columns = [])
 * @method static array eagerLoadRelations($models)
 * @method static LazyCollection cursor()
 * @method static Collection pluck($column, $key = null)
 * @method static LengthAwarePaginator paginate($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static Paginator simplePaginate($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static CursorPaginator cursorPaginate($perPage = null, $columns = [], $cursorName = 'cursor', $cursor = null)
 * @method static Model|$this create($attributes = [])
 * @method static Model|$this forceCreate($attributes)
 * @method static int upsert($values, $uniqueBy, $update = null)
 * @method static void onDelete($callback)
 * @method static static|mixed scopes($scopes)
 * @method static static applyScopes()
 * @method static Builder without($relations)
 * @method static Builder withOnly($relations)
 * @method static Model newModelInstance($attributes = [])
 * @method static Builder withCasts($casts)
 * @method static QueryBuilder getQuery()
 * @method static Builder setQuery($query)
 * @method static QueryBuilder toBase()
 * @method static array getEagerLoads()
 * @method static Builder setEagerLoads($eagerLoad)
 * @method static Model getModel()
 * @method static Builder setModel($model)
 * @method static Closure getMacro($name)
 * @method static bool hasMacro($name)
 * @method static Closure getGlobalMacro($name)
 * @method static bool hasGlobalMacro($name)
 * @method static static clone ()
 * @method static Builder has($relation, $operator = '>=', $count = 1, $boolean = 'and', $callback = null)
 * @method static Builder orHas($relation, $operator = '>=', $count = 1)
 * @method static Builder doesntHave($relation, $boolean = 'and', $callback = null)
 * @method static Builder orDoesntHave($relation)
 * @method static Builder whereHas($relation, $callback = null, $operator = '>=', $count = 1)
 * @method static Builder orWhereHas($relation, $callback = null, $operator = '>=', $count = 1)
 * @method static Builder whereDoesntHave($relation, $callback = null)
 * @method static Builder orWhereDoesntHave($relation, $callback = null)
 * @method static Builder hasMorph($relation, $types, $operator = '>=', $count = 1, $boolean = 'and', $callback = null)
 * @method static Builder orHasMorph($relation, $types, $operator = '>=', $count = 1)
 * @method static Builder doesntHaveMorph($relation, $types, $boolean = 'and', $callback = null)
 * @method static Builder orDoesntHaveMorph($relation, $types)
 * @method static Builder whereHasMorph($relation, $types, $callback = null, $operator = '>=', $count = 1)
 * @method static Builder orWhereHasMorph($relation, $types, $callback = null, $operator = '>=', $count = 1)
 * @method static Builder whereDoesntHaveMorph($relation, $types, $callback = null)
 * @method static Builder orWhereDoesntHaveMorph($relation, $types, $callback = null)
 * @method static Builder withAggregate($relations, $column, $function = null)
 * @method static Builder withCount($relations)
 * @method static Builder withMax($relation, $column)
 * @method static Builder withMin($relation, $column)
 * @method static Builder withSum($relation, $column)
 * @method static Builder withAvg($relation, $column)
 * @method static Builder withExists($relation)
 * @method static Builder mergeConstraintsFrom($from)
 * @method static Collection explain()
 * @method static bool chunk($count, $callback)
 * @method static Collection chunkMap($callback, $count = 1000)
 * @method static bool each($callback, $count = 1000)
 * @method static bool chunkById($count, $callback, $column = null, $alias = null)
 * @method static bool eachById($callback, $count = 1000, $column = null, $alias = null)
 * @method static LazyCollection lazy($chunkSize = 1000)
 * @method static LazyCollection lazyById($chunkSize = 1000, $column = null, $alias = null)
 * @method static Model|object|static|null first($columns = [])
 * @method static Model|object|null baseSole($columns = [])
 * @method static Builder tap($callback)
 * @method static mixed when($value, $callback, $default = null)
 * @method static mixed unless($value, $callback, $default = null)
 * @method static QueryBuilder select($columns = [])
 * @method static QueryBuilder selectSub($query, $as)
 * @method static QueryBuilder selectRaw($expression, $bindings = [])
 * @method static QueryBuilder fromSub($query, $as)
 * @method static QueryBuilder fromRaw($expression, $bindings = [])
 * @method static QueryBuilder addSelect($column)
 * @method static QueryBuilder distinct()
 * @method static QueryBuilder from($table, $as = null)
 * @method static QueryBuilder join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false)
 * @method static QueryBuilder joinWhere($table, $first, $operator, $second, $type = 'inner')
 * @method static QueryBuilder joinSub($query, $as, $first, $operator = null, $second = null, $type = 'inner', $where = false)
 * @method static QueryBuilder leftJoin($table, $first, $operator = null, $second = null)
 * @method static QueryBuilder leftJoinWhere($table, $first, $operator, $second)
 * @method static QueryBuilder leftJoinSub($query, $as, $first, $operator = null, $second = null)
 * @method static QueryBuilder rightJoin($table, $first, $operator = null, $second = null)
 * @method static QueryBuilder rightJoinWhere($table, $first, $operator, $second)
 * @method static QueryBuilder rightJoinSub($query, $as, $first, $operator = null, $second = null)
 * @method static QueryBuilder crossJoin($table, $first = null, $operator = null, $second = null)
 * @method static QueryBuilder crossJoinSub($query, $as)
 * @method static void mergeWheres($wheres, $bindings)
 * @method static array prepareValueAndOperator($value, $operator, $useDefault = false)
 * @method static QueryBuilder whereColumn($first, $operator = null, $second = null, $boolean = 'and')
 * @method static QueryBuilder orWhereColumn($first, $operator = null, $second = null)
 * @method static QueryBuilder whereRaw($sql, $bindings = [], $boolean = 'and')
 * @method static QueryBuilder orWhereRaw($sql, $bindings = [])
 * @method static QueryBuilder whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static QueryBuilder orWhereIn($column, $values)
 * @method static QueryBuilder whereNotIn($column, $values, $boolean = 'and')
 * @method static QueryBuilder orWhereNotIn($column, $values)
 * @method static QueryBuilder whereIntegerInRaw($column, $values, $boolean = 'and', $not = false)
 * @method static QueryBuilder orWhereIntegerInRaw($column, $values)
 * @method static QueryBuilder whereIntegerNotInRaw($column, $values, $boolean = 'and')
 * @method static QueryBuilder orWhereIntegerNotInRaw($column, $values)
 * @method static QueryBuilder whereNull($columns, $boolean = 'and', $not = false)
 * @method static QueryBuilder orWhereNull($column)
 * @method static QueryBuilder whereNotNull($columns, $boolean = 'and')
 * @method static QueryBuilder whereBetween($column, $values, $boolean = 'and', $not = false)
 * @method static QueryBuilder whereBetweenColumns($column, $values, $boolean = 'and', $not = false)
 * @method static QueryBuilder orWhereBetween($column, $values)
 * @method static QueryBuilder orWhereBetweenColumns($column, $values)
 * @method static QueryBuilder whereNotBetween($column, $values, $boolean = 'and')
 * @method static QueryBuilder whereNotBetweenColumns($column, $values, $boolean = 'and')
 * @method static QueryBuilder orWhereNotBetween($column, $values)
 * @method static QueryBuilder orWhereNotBetweenColumns($column, $values)
 * @method static QueryBuilder orWhereNotNull($column)
 * @method static QueryBuilder whereDate($column, $operator, $value = null, $boolean = 'and')
 * @method static QueryBuilder orWhereDate($column, $operator, $value = null)
 * @method static QueryBuilder whereTime($column, $operator, $value = null, $boolean = 'and')
 * @method static QueryBuilder orWhereTime($column, $operator, $value = null)
 * @method static QueryBuilder whereDay($column, $operator, $value = null, $boolean = 'and')
 * @method static QueryBuilder orWhereDay($column, $operator, $value = null)
 * @method static QueryBuilder whereMonth($column, $operator, $value = null, $boolean = 'and')
 * @method static QueryBuilder orWhereMonth($column, $operator, $value = null)
 * @method static QueryBuilder whereYear($column, $operator, $value = null, $boolean = 'and')
 * @method static QueryBuilder orWhereYear($column, $operator, $value = null)
 * @method static QueryBuilder whereNested($callback, $boolean = 'and')
 * @method static QueryBuilder forNestedWhere()
 * @method static QueryBuilder addNestedWhereQuery($query, $boolean = 'and')
 * @method static QueryBuilder whereExists($callback, $boolean = 'and', $not = false)
 * @method static QueryBuilder orWhereExists($callback, $not = false)
 * @method static QueryBuilder whereNotExists($callback, $boolean = 'and')
 * @method static QueryBuilder orWhereNotExists($callback)
 * @method static QueryBuilder addWhereExistsQuery($query, $boolean = 'and', $not = false)
 * @method static QueryBuilder whereRowValues($columns, $operator, $values, $boolean = 'and')
 * @method static QueryBuilder orWhereRowValues($columns, $operator, $values)
 * @method static QueryBuilder whereJsonContains($column, $value, $boolean = 'and', $not = false)
 * @method static QueryBuilder orWhereJsonContains($column, $value)
 * @method static QueryBuilder whereJsonDoesntContain($column, $value, $boolean = 'and')
 * @method static QueryBuilder orWhereJsonDoesntContain($column, $value)
 * @method static QueryBuilder whereJsonLength($column, $operator, $value = null, $boolean = 'and')
 * @method static QueryBuilder orWhereJsonLength($column, $operator, $value = null)
 * @method static QueryBuilder dynamicWhere($method, $parameters)
 * @method static QueryBuilder groupBy(...$groups)
 * @method static QueryBuilder groupByRaw($sql, $bindings = [])
 * @method static QueryBuilder having($column, $operator = null, $value = null, $boolean = 'and')
 * @method static QueryBuilder orHaving($column, $operator = null, $value = null)
 * @method static QueryBuilder havingBetween($column, $values, $boolean = 'and', $not = false)
 * @method static QueryBuilder havingRaw($sql, $bindings = [], $boolean = 'and')
 * @method static QueryBuilder orHavingRaw($sql, $bindings = [])
 * @method static QueryBuilder orderBy($column, $direction = 'asc')
 * @method static QueryBuilder orderByDesc($column)
 * @method static QueryBuilder inRandomOrder($seed = '')
 * @method static QueryBuilder orderByRaw($sql, $bindings = [])
 * @method static QueryBuilder skip($value)
 * @method static QueryBuilder offset($value)
 * @method static QueryBuilder take($value)
 * @method static QueryBuilder limit($value)
 * @method static QueryBuilder forPage($page, $perPage = 15)
 * @method static QueryBuilder forPageBeforeId($perPage = 15, $lastId = 0, $column = 'id')
 * @method static QueryBuilder forPageAfterId($perPage = 15, $lastId = 0, $column = 'id')
 * @method static QueryBuilder reorder($column = null, $direction = 'asc')
 * @method static QueryBuilder union($query, $all = false)
 * @method static QueryBuilder unionAll($query)
 * @method static QueryBuilder lock($value = true)
 * @method static QueryBuilder lockForUpdate()
 * @method static QueryBuilder sharedLock()
 * @method static QueryBuilder beforeQuery($callback)
 * @method static void applyBeforeQueryCallbacks()
 * @method static string toSql()
 * @method static int getCountForPagination($columns = [])
 * @method static string implode($column, $glue = '')
 * @method static bool exists()
 * @method static bool doesntExist()
 * @method static mixed existsOr($callback)
 * @method static mixed doesntExistOr($callback)
 * @method static int count($columns = '*')
 * @method static mixed min($column)
 * @method static mixed max($column)
 * @method static mixed sum($column)
 * @method static mixed avg($column)
 * @method static mixed average($column)
 * @method static mixed aggregate($function, $columns = [])
 * @method static float|int numericAggregate($function, $columns = [])
 * @method static bool insert($values)
 * @method static int insertOrIgnore($values)
 * @method static int insertGetId($values, $sequence = null)
 * @method static int insertUsing($columns, $query)
 * @method static bool updateOrInsert($attributes, $values = [])
 * @method static void truncate()
 * @method static Expression raw($value)
 * @method static array getBindings()
 * @method static array getRawBindings()
 * @method static QueryBuilder setBindings($bindings, $type = 'where')
 * @method static QueryBuilder addBinding($value, $type = 'where')
 * @method static QueryBuilder mergeBindings($query)
 * @method static array cleanBindings($bindings)
 * @method static Processor getProcessor()
 * @method static Grammar getGrammar()
 * @method static QueryBuilder useWritePdo()
 * @method static static cloneWithout($properties)
 * @method static static cloneWithoutBindings($except)
 * @method static QueryBuilder dump()
 * @method static void dd()
 * @method static void macro($name, $macro)
 * @method static void mixin($mixin, $replace = true)
 * @method static mixed macroCall($method, $parameters)
 */
abstract class Model extends BaseModel
{
    use HybridRelations, EmbedsRelations;

    /**
     * The collection associated with the model.
     * @var string
     */
    protected $collection;

    /**
     * The primary key for the model.
     * @var string
     */
    protected $primaryKey = '_id';

    /**
     * The primary key type.
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The parent relation instance.
     * @var Relation
     */
    protected Relation $parentRelation;

    /**
     * Custom accessor for the model's id.
     * @param mixed $value
     * @return mixed
     */
    public function getIdAttribute($value = null): mixed
    {
        // If we don't have a value for 'id', we will use the Mongo '_id' value.
        // This allows us to work with models in a more sql-like way.
        if (!$value && array_key_exists('_id', $this->attributes)) {
            $value = $this->attributes['_id'];
        }

        // Convert ObjectID to string.
        if ($value instanceof ObjectIdInterface) {
            return (string)$value;
        } elseif ($value instanceof Binary) {
            return (string)$value->getData();
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getQualifiedKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * @inheritdoc
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat ?: 'Y-m-d H:i:s';
    }

    /**
     * @inheritdoc
     */
    public function freshTimestamp(): UTCDateTime|Carbon
    {
        return new UTCDateTime(Date::now()->format('Uv'));
    }

    /**
     * @inheritdoc
     */
    public function getTable(): string
    {
        return $this->collection ?: parent::getTable();
    }

    /**
     * @inheritdoc
     */
    public function getAttribute($key)
    {
        if (!$key) {
            return;
        }

        // Dot notation support.
        if (Str::contains($key, '.') && Arr::has($this->attributes, $key)) {
            return $this->getAttributeValue($key);
        }

        // This checks for embedded relation support.
        if (method_exists($this, $key) && !method_exists(self::class, $key)) {
            return $this->getRelationValue($key);
        }

        return parent::getAttribute($key);
    }

    /**
     * @inheritdoc
     */
    public function setAttribute($key, $value)
    {
        // Convert _id to ObjectID.
        if ($key == '_id' && is_string($value)) {
            $builder = $this->newBaseQueryBuilder();

            $value = $builder->convertKey($value);
        } // Support keys in dot notation.
        elseif (Str::contains($key, '.')) {
            if (in_array($key, $this->getDates()) && $value) {
                $value = $this->fromDateTime($value);
            }

            Arr::set($this->attributes, $key, $value);

            return;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * @inheritdoc
     */
    protected function newBaseQueryBuilder(): QueryBuilder|\Illuminate\Database\Query\Builder
    {
        $connection = $this->getConnection();

        return new QueryBuilder($connection, $connection->getPostProcessor());
    }

    /**
     * @inheritdoc
     */
    public function fromDateTime($value): UTCDateTime|string|null
    {
        // If the value is already a UTCDateTime instance, we don't need to parse it.
        if ($value instanceof UTCDateTime) {
            return $value;
        }

        // Let Eloquent convert the value to a DateTime instance.
        if (!$value instanceof DateTimeInterface) {
            $value = parent::asDateTime($value);
        }

        return new UTCDateTime($value->format('Uv'));
    }

    /**
     * @inheritdoc
     */
    protected function asDateTime($value): false|Carbon
    {
        // Convert UTCDateTime instances.
        if ($value instanceof UTCDateTime) {
            $date = $value->toDateTime();

            $seconds = $date->format('U');
            $milliseconds = abs($date->format('v'));
            $timestampMs = sprintf('%d%03d', $seconds, $milliseconds);

            return Date::createFromTimestampMs($timestampMs);
        }

        return parent::asDateTime($value);
    }

    /**
     * @inheritdoc
     */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        // Because the original Eloquent never returns objects, we convert
        // MongoDB related objects to a string representation. This kind
        // of mimics the SQL behaviour so that dates are formatted
        // nicely when your models are converted to JSON.
        foreach ($attributes as $key => &$value) {
            if ($value instanceof ObjectIdInterface) {
                $value = (string)$value;
            } elseif ($value instanceof Binary) {
                $value = (string)$value->getData();
            }
        }

        // Convert dot-notation dates.
        foreach ($this->getDates() as $key) {
            if (Str::contains($key, '.') && Arr::has($attributes, $key)) {
                Arr::set($attributes, $key, (string)$this->asDateTime(Arr::get($attributes, $key)));
            }
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function getCasts(): array
    {
        return $this->casts;
    }

    /**
     * @inheritdoc
     */
    public function originalIsEquivalent($key): bool
    {
        if (!array_key_exists($key, $this->original)) {
            return false;
        }

        $attribute = Arr::get($this->attributes, $key);
        $original = Arr::get($this->original, $key);

        if ($attribute === $original) {
            return true;
        }

        if (null === $attribute) {
            return false;
        }

        if ($this->isDateAttribute($key)) {
            $attribute = $attribute instanceof UTCDateTime ? $this->asDateTime($attribute) : $attribute;
            $original = $original instanceof UTCDateTime ? $this->asDateTime($original) : $original;

            return $attribute == $original;
        }

        if ($this->hasCast($key, static::$primitiveCastTypes)) {
            return $this->castAttribute($key, $attribute) ===
                $this->castAttribute($key, $original);
        }

        return is_numeric($attribute) && is_numeric($original)
            && strcmp((string)$attribute, (string)$original) === 0;
    }

    /**
     * Remove one or more fields.
     * @param mixed $columns
     * @return int
     */
    public function drop(mixed $columns): int
    {
        $columns = Arr::wrap($columns);

        // Unset attributes
        foreach ($columns as $column) {
            $this->__unset($column);
        }

        // Perform unset only on current document
        return $this->newQuery()->where($this->getKeyName(), $this->getKey())->unset($columns);
    }

    /**
     * @inheritdoc
     */
    public function push()
    {
        if ($parameters = func_get_args()) {
            $unique = false;

            if (count($parameters) === 3) {
                [$column, $values, $unique] = $parameters;
            } else {
                [$column, $values] = $parameters;
            }

            // Do batch push by default.
            $values = Arr::wrap($values);

            $query = $this->setKeysForSaveQuery($this->newQuery());

            $this->pushAttributeValues($column, $values, $unique);

            return $query->push($column, $values, $unique);
        }

        return parent::push();
    }

    /**
     * Append one or more values to the underlying attribute value and sync with original.
     * @param string $column
     * @param array $values
     * @param bool $unique
     */
    protected function pushAttributeValues($column, array $values, $unique = false): void
    {
        $current = $this->getAttributeFromArray($column) ?: [];

        foreach ($values as $value) {
            // Don't add duplicate values when we only want unique values.
            if ($unique && (!is_array($current) || in_array($value, $current))) {
                continue;
            }

            $current[] = $value;
        }

        $this->attributes[$column] = $current;

        $this->syncOriginalAttribute($column);
    }

    /**
     * @inheritdoc
     */
    protected function getAttributeFromArray($key)
    {
        // Support keys in dot notation.
        if (Str::contains($key, '.')) {
            return Arr::get($this->attributes, $key);
        }

        return parent::getAttributeFromArray($key);
    }

    /**
     * Remove one or more values from an array.
     * @param string $column
     * @param mixed $values
     * @return mixed
     */
    public function pull(string $column, mixed $values): mixed
    {
        // Do batch pull by default.
        $values = Arr::wrap($values);

        $query = $this->setKeysForSaveQuery($this->newQuery());

        $this->pullAttributeValues($column, $values);

        return $query->pull($column, $values);
    }

    /**
     * Remove one or more values to the underlying attribute value and sync with original.
     * @param string $column
     * @param array $values
     */
    protected function pullAttributeValues($column, array $values): void
    {
        $current = $this->getAttributeFromArray($column) ?: [];

        if (is_array($current)) {
            foreach ($values as $value) {
                $keys = array_keys($current, $value);

                foreach ($keys as $key) {
                    unset($current[$key]);
                }
            }
        }

        $this->attributes[$column] = array_values($current);

        $this->syncOriginalAttribute($column);
    }

    /**
     * @inheritdoc
     */
    public function getForeignKey(): string
    {
        return Str::snake(class_basename($this)) . '_' . ltrim($this->primaryKey, '_');
    }

    /**
     * @inheritdoc
     */
    public function newEloquentBuilder($query): BaseModel|\Illuminate\Database\Eloquent\Builder|Builder
    {
        return new Builder($query);
    }

    /**
     * Get the queueable relationships for the entity.
     * @return array
     */
    public function getQueueableRelations(): array
    {
        $relations = [];

        foreach ($this->getRelationsWithoutParent() as $key => $relation) {
            if (method_exists($this, $key)) {
                $relations[] = $key;
            }

            if ($relation instanceof QueueableCollection) {
                foreach ($relation->getQueueableRelations() as $collectionValue) {
                    $relations[] = $key . '.' . $collectionValue;
                }
            }

            if ($relation instanceof QueueableEntity) {
                foreach ($relation->getQueueableRelations() as $entityKey => $entityValue) {
                    $relations[] = $key . '.' . $entityValue;
                }
            }
        }

        return array_unique($relations);
    }

    /**
     * Get loaded relations for the instance without parent.
     * @return array
     */
    protected function getRelationsWithoutParent(): array
    {
        $relations = $this->getRelations();

        if ($parentRelation = $this->getParentRelation()) {
            unset($relations[$parentRelation->getQualifiedForeignKeyName()]);
        }

        return $relations;
    }

    /**
     * Get the parent relation.
     * @return Relation
     */
    public function getParentRelation(): Relation
    {
        return $this->parentRelation;
    }

    /**
     * Set the parent relation.
     * @param Relation $relation
     */
    public function setParentRelation(Relation $relation): void
    {
        $this->parentRelation = $relation;
    }

    /**
     * @inheritdoc
     */
    public function __call($method, $parameters)
    {
        // Unset method
        if ($method == 'unset') {
            return call_user_func_array([$this, 'drop'], $parameters);
        }

        return parent::__call($method, $parameters);
    }

    /**
     * @param $key
     * @return mixed
     */
    protected function removeTableFromKey($key): mixed
    {
        return $key;
    }

    /**
     * Checks if column exists on a table.  As this is a document model, just return true.  This also
     * prevents calls to non-existent function Grammar::compileColumnListing().
     * @param string $key
     * @return bool
     */
    protected function isGuardableColumn($key): bool
    {
        return true;
    }
}
