<?php

declare(strict_types=1);

namespace JimmyAhalpara\LaravelDatatable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \JimmyAhalpara\LaravelDatatable\DataTableService make(\Illuminate\Database\Eloquent\Builder $builder)
 * @method static \JimmyAhalpara\LaravelDatatable\DataTableService setPage(int $page)
 * @method static \JimmyAhalpara\LaravelDatatable\DataTableService setItemsPerPage(int $itemsPerPage)
 * @method static \JimmyAhalpara\LaravelDatatable\DataTableService setSortBy(array $sortBy)
 * @method static \JimmyAhalpara\LaravelDatatable\DataTableService setResourceClass(string $resourceClass)
 * @method static \JimmyAhalpara\LaravelDatatable\DataTableService setGlobalFilters(array $filters)
 * @method static \JimmyAhalpara\LaravelDatatable\DataTableService setColumnFilters(array $filters)
 * @method static \JimmyAhalpara\LaravelDatatable\DataTableService setCustomFilters(array $filters)
 * @method static \JimmyAhalpara\LaravelDatatable\DataTableService setDownloadColumns(array $columns)
 * @method static \JimmyAhalpara\LaravelDatatable\DataTableService setDownloadMapper(callable $mapper)
 * @method static \JimmyAhalpara\LaravelDatatable\DataTableService fillFromRequest(\Illuminate\Http\Request $request)
 * @method static \Illuminate\Contracts\Pagination\LengthAwarePaginator apply()
 * @method static bool expectsDownload()
 * @method static mixed render()
 *
 * @see \JimmyAhalpara\LaravelDatatable\DataTableService
 */
class DataTable extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'datatable';
    }
}