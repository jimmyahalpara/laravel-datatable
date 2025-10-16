<?php

declare(strict_types=1);

namespace JimmyAhalpara\LaravelDatatable\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

interface DataTableServiceInterface
{
    /**
     * Create a new instance of DataTableService.
     */
    public static function make(Builder $builder): self;

    /**
     * Set the page number.
     */
    public function setPage(int $page): self;

    /**
     * Set the number of items per page.
     */
    public function setItemsPerPage(int $itemsPerPage): self;

    /**
     * Set the sorting fields.
     */
    public function setSortBy(array $sortBy): self;

    /**
     * Set the resource class.
     */
    public function setResourceClass(string $resourceClass): self;

    /**
     * Set global filters for search functionality.
     */
    public function setGlobalFilters(array $filters): self;

    /**
     * Set column-specific filters.
     */
    public function setColumnFilters(array $filters): self;

    /**
     * Set custom filters using callables.
     */
    public function setCustomFilters(array $filters): self;

    /**
     * Set download columns.
     */
    public function setDownloadColumns(array $columns): self;

    /**
     * Set download mapper function.
     */
    public function setDownloadMapper(callable $mapper): self;

    /**
     * Fill data from HTTP request.
     */
    public function fillFromRequest(Request $request): self;

    /**
     * Apply all filters and return paginated results.
     */
    public function apply(): LengthAwarePaginator;

    /**
     * Check if download is expected.
     */
    public function expectsDownload(): bool;

    /**
     * Render the final result (paginated data or download).
     */
    public function render(): mixed;
}