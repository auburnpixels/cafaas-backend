<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * @class DomainScope.
 */
class DomainScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (request()->domain && request()->domain->app) {
            $builder->where('app_id', request()->domain->app->id);
        } else {
            $builder->whereNull('app_id');
        }
    }
}
