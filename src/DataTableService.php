<?php

declare(strict_types=1);

namespace JimmyAhalpara\LaravelDatatable;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use JimmyAhalpara\LaravelDatatable\Contracts\DataTableServiceInterface;
use JimmyAhalpara\LaravelDatatable\Contracts\FilterInterface;
use JimmyAhalpara\LaravelDatatable\Filters\ColumnFilter;
use JimmyAhalpara\LaravelDatatable\Filters\CustomFilter;
use JimmyAhalpara\LaravelDatatable\Filters\GlobalFilter;

class DataTableService implements DataTableServiceInterface
{
    private Builder $builder;
    private int $page = 1;
    private int $itemsPerPage = 10;
    private array $sortBy = [];
    private string $resourceClass = '';
    private bool $download = false;
    
    /** @var GlobalFilter[] */
    private array $globalFilters = [];
    
    /** @var ColumnFilter[] */
    private array $columnFilters = [];
    
    /** @var CustomFilter[] */
    private array $customFilters = [];
    
    private array $requestFilter = [];
    private array $downloadColumns = [];
    
    /** @var callable|null */
    private $downloadMapper = null;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
        $this->itemsPerPage = config('datatable.default_items_per_page', 10);
    }

    public static function make(Builder $builder): self
    {
        return new self($builder);
    }

    public function setPage(int $page): self
    {
        if ($page < 1) {
            throw new \InvalidArgumentException('Page number must be greater than 0');
        }

        $this->page = $page;
        return $this;
    }

    public function setItemsPerPage(int $itemsPerPage): self
    {
        $maxItems = config('datatable.max_items_per_page', 100);
        
        if ($itemsPerPage < 1) {
            throw new \InvalidArgumentException('Items per page must be greater than 0');
        }
        
        if ($itemsPerPage > $maxItems) {
            throw new \InvalidArgumentException("Items per page cannot exceed {$maxItems}");
        }

        $this->itemsPerPage = $itemsPerPage;
        return $this;
    }

    public function setSortBy(array $sortBy): self
    {
        // Validate sorting array structure
        foreach ($sortBy as $sort) {
            if (!is_array($sort) || !isset($sort['key'], $sort['order'])) {
                throw new \InvalidArgumentException(
                    'Sort array must contain "key" and "order" fields'
                );
            }
            
            $order = strtolower($sort['order']);
            if (!in_array($order, ['asc', 'desc'], true)) {
                throw new \InvalidArgumentException(
                    'Sort order must be "asc" or "desc"'
                );
            }
        }

        $this->sortBy = $sortBy;
        return $this;
    }

    public function setResourceClass(string $resourceClass): self
    {
        if (!class_exists($resourceClass)) {
            throw new \InvalidArgumentException("Resource class '{$resourceClass}' does not exist");
        }

        $this->resourceClass = $resourceClass;
        return $this;
    }

    public function getResourceClass(): ?string
    {
        return $this->resourceClass;
    }

    public function setGlobalFilters(array $filters): self
    {
        foreach ($filters as $filter) {
            if (!$filter instanceof GlobalFilter) {
                throw new \InvalidArgumentException(
                    'All global filters must be instances of ' . GlobalFilter::class
                );
            }
        }

        $this->globalFilters = $filters;
        return $this;
    }

    public function setColumnFilters(array $filters): self
    {
        foreach ($filters as $filter) {
            if (!$filter instanceof ColumnFilter) {
                throw new \InvalidArgumentException(
                    'All column filters must be instances of ' . ColumnFilter::class
                );
            }
        }

        $this->columnFilters = $filters;
        return $this;
    }

    public function setCustomFilters(array $filters): self
    {
        foreach ($filters as $filter) {
            if (!$filter instanceof CustomFilter) {
                throw new \InvalidArgumentException(
                    'All custom filters must be instances of ' . CustomFilter::class
                );
            }
        }

        $this->customFilters = $filters;
        return $this;
    }

    public function setDownloadColumns(array $columns): self
    {
        $this->downloadColumns = $columns;
        return $this;
    }

    public function setDownloadMapper(callable $mapper): self
    {
        $this->downloadMapper = $mapper;
        return $this;
    }

    /**
     * Apply global filters to the query.
     */
    private function applyGlobalFilters(): self
    {
        if (empty($this->globalFilters)) {
            return $this;
        }

        $searchValue = $this->requestFilter['search'] ?? '';
        
        if ($searchValue !== '' && $searchValue !== null) {
            $this->builder->where(function ($query) use ($searchValue) {
                foreach ($this->globalFilters as $filter) {
                    $filter->apply($query, $searchValue);
                }
            });
        }

        return $this;
    }

    /**
     * Apply column-specific filters to the query.
     */
    private function applyColumnFilters(): self
    {
        if (empty($this->columnFilters)) {
            return $this;
        }

        $this->builder->where(function ($query) {
            foreach ($this->columnFilters as $filter) {
                $filter->apply($query, $this->requestFilter);
            }
        });

        return $this;
    }

    /**
     * Apply custom filters to the query.
     */
    private function applyCustomFilters(): self
    {
        foreach ($this->customFilters as $filter) {
            $filter->apply($this->builder, $this->requestFilter);
        }

        return $this;
    }

    /**
     * Apply sorting to the query.
     */
    private function applySorting(?array $sorting = null): self
    {
        $sortBy = $sorting ?? $this->sortBy;
        
        if (empty($sortBy)) {
            return $this;
        }

        foreach ($sortBy as $field) {
            $this->builder->orderBy($field['key'], $field['order']);
        }

        return $this;
    }

    public function fillFromRequest(Request $request): self
    {
        $this->page = (int) $request->get('page', 1);
        $this->itemsPerPage = (int) $request->get('itemsPerPage', $this->itemsPerPage);
        $this->sortBy = (array) $request->get('sortBy', []);
        $this->requestFilter = (array) $request->get('filter', []);
        $this->download = (bool) $request->get('download', false);

        // Apply validation
        if ($this->page < 1) {
            $this->page = 1;
        }

        $maxItems = config('datatable.max_items_per_page', 100);
        if ($this->itemsPerPage > $maxItems) {
            $this->itemsPerPage = $maxItems;
        }

        $this->applyGlobalFilters();
        $this->applyColumnFilters();
        $this->applyCustomFilters();
        $this->applySorting();

        return $this;
    }

    public function apply(): LengthAwarePaginator
    {
        return $this->builder->paginate($this->itemsPerPage, ['*'], 'page', $this->page);
    }

    /**
     * Apply download functionality.
     */
    private function applyDownload(): mixed
    {
        // Set PHP timeout for large exports
        $maxExecutionTime = config('datatable.download.max_execution_time', 1800);
        ini_set('max_execution_time', (string) $maxExecutionTime);

        $data = $this->builder->get();

        // Apply download mapper if provided
        if ($this->downloadMapper !== null) {
            $data = $data->map($this->downloadMapper);
        }

        // This would need to be implemented based on your export needs
        // For now, returning the data as array
        return $data->toArray();
    }

    public function expectsDownload(): bool
    {
        return $this->download && !empty($this->downloadColumns);
    }

    public function render(): mixed
    {
        if ($this->expectsDownload()) {
            return $this->applyDownload();
        }

        $paginator = $this->apply();

        if ($this->resourceClass !== '') {
            $resourceCollection = $this->resourceClass::collection($paginator);

            return [
                'current_page' => $paginator->currentPage(),
                'data' => $resourceCollection->collection,
                'first_page_url' => $paginator->url(1),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'last_page_url' => $paginator->url($paginator->lastPage()),
                'next_page_url' => $paginator->nextPageUrl(),
                'path' => $paginator->path(),
                'per_page' => $paginator->perPage(),
                'prev_page_url' => $paginator->previousPageUrl(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ];
        }

        return $paginator;
    }

    /**
     * Get the current query builder instance.
     */
    public function getBuilder(): Builder
    {
        return $this->builder;
    }

    /**
     * Get current page number.
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * Get items per page.
     */
    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    /**
     * Get sorting configuration.
     */
    public function getSortBy(): array
    {
        return $this->sortBy;
    }

    /**
     * Get global filters.
     */
    public function getGlobalFilters(): array
    {
        return $this->globalFilters;
    }

    /**
     * Get column filters.
     */
    public function getColumnFilters(): array
    {
        return $this->columnFilters;
    }

    /**
     * Get custom filters.
     */
    public function getCustomFilters(): array
    {
        return $this->customFilters;
    }

    /**
     * Get request filters.
     */
    public function getRequestFilter(): array
    {
        return $this->requestFilter;
    }

    /**
     * Get download columns.
     */
    public function getDownloadColumns(): array
    {
        return $this->downloadColumns;
    }
}