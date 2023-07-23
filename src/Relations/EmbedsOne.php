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

namespace Triangle\MongoDB\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use MongoDB\BSON\ObjectID;

/**
 *
 */
class EmbedsOne extends EmbedsOneOrMany
{
    /**
     * @param array $models
     * @param $relation
     * @return array
     */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, null);
        }

        return $models;
    }

    /**
     * @return \Triangle\MongoDB\Model
     */
    public function getResults()
    {
        return $this->toModel($this->getEmbedded());
    }

    /**
     * @return Collection
     */
    public function getEager()
    {
        $eager = $this->get();

        // EmbedsOne only brings one result, Eager needs a collection!
        return $this->toCollection([$eager]);
    }

    /**
     * Save a new model and attach it to the parent model.
     * @param Model $model
     * @return Model|bool
     */
    public function performInsert(Model $model)
    {
        // Generate a new key if needed.
        if ($model->getKeyName() == '_id' && !$model->getKey()) {
            $model->setAttribute('_id', new ObjectID);
        }

        // For deeply nested documents, let the parent handle the changes.
        if ($this->isNested()) {
            $this->associate($model);

            return $this->parent->save() ? $model : false;
        }

        $result = $this->getBaseQuery()->update([$this->localKey => $model->getAttributes()]);

        // Attach the model to its parent.
        if ($result) {
            $this->associate($model);
        }

        return $result ? $model : false;
    }

    /**
     * Attach the model to its parent.
     * @param Model $model
     * @return Model
     */
    public function associate(Model $model)
    {
        return $this->setEmbedded($model->getAttributes());
    }

    /**
     * Save an existing model and attach it to the parent model.
     * @param Model $model
     * @return Model|bool
     */
    public function performUpdate(Model $model)
    {
        if ($this->isNested()) {
            $this->associate($model);

            return $this->parent->save();
        }

        $values = $this->getUpdateValues($model->getDirty(), $this->localKey . '.');

        $result = $this->getBaseQuery()->update($values);

        // Attach the model to its parent.
        if ($result) {
            $this->associate($model);
        }

        return $result ? $model : false;
    }

    /**
     * Delete all embedded models.
     * @return int
     */
    public function delete()
    {
        return $this->performDelete();
    }

    /**
     * Delete an existing model and detach it from the parent model.
     * @return int
     */
    public function performDelete()
    {
        // For deeply nested documents, let the parent handle the changes.
        if ($this->isNested()) {
            $this->dissociate();

            return $this->parent->save();
        }

        // Overwrite the local key with an empty array.
        $result = $this->getBaseQuery()->update([$this->localKey => null]);

        // Detach the model from its parent.
        if ($result) {
            $this->dissociate();
        }

        return $result;
    }

    /**
     * Detach the model from its parent.
     * @return Model
     */
    public function dissociate()
    {
        return $this->setEmbedded(null);
    }

    /**
     * Get the name of the "where in" method for eager loading.
     * @param EloquentModel $model
     * @param string $key
     * @return string
     */
    protected function whereInMethod(EloquentModel $model, $key)
    {
        return 'whereIn';
    }
}
