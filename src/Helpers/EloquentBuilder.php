<?php

namespace Triangle\MongoDB\Helpers;

use Illuminate\Database\Eloquent\Builder;

/**
 *
 */
class EloquentBuilder extends Builder
{
    use QueriesRelationships;
}
