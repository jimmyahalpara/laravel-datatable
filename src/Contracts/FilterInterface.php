<?php

declare(strict_types=1);

namespace JimmyAhalpara\LaravelDatatable\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

interface FilterInterface
{
    /**
     * Apply the filter to the query builder.
     * 
     * @param Builder|QueryBuilder $builder
     * @param array|string $data
     * @return void
     */
    public function apply($builder, $data): void;
}