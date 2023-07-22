<?php

namespace Triangle\MongoDB;

use Illuminate\Database\Eloquent\SoftDeletes as EloquentSoftDeletes;

/**
 *
 */
trait SoftDeletes
{
    use EloquentSoftDeletes;

    /**
     * @inheritdoc
     */
    public function getQualifiedDeletedAtColumn()
    {
        return $this->getDeletedAtColumn();
    }
}
