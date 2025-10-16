<?php

declare(strict_types=1);

namespace JimmyAhalpara\LaravelDatatable\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use JimmyAhalpara\LaravelDatatable\Contracts\FilterInterface;

class GlobalFilter implements FilterInterface
{
    public const TYPE_STARTS_WITH = 'startsWith';
    public const TYPE_ENDS_WITH = 'endsWith';
    public const TYPE_CONTAINS = 'contains';
    public const TYPE_EQUAL = 'equal';

    public const TYPE_LOGICAL_AND = 'AND';
    public const TYPE_LOGICAL_OR = 'OR';

    private string $columnKey;
    private string $relation = '';
    private bool $searchCaseInsensitive = true;
    private string $searchType = self::TYPE_CONTAINS;
    /** @var callable|null */
    private $castCallable = null;
    private string $logicalOperator = self::TYPE_LOGICAL_OR;

    public function __construct(string $columnKey)
    {
        $this->columnKey = $columnKey;

        // If column key contains a dot, it means it's a relation
        if (str_contains($columnKey, '.')) {
            [$this->relation, $this->columnKey] = explode('.', $columnKey, 2);
        }
    }

    public static function make(string $columnKey): self
    {
        return new self($columnKey);
    }

    public function caseInsensitive(bool $caseInsensitive = true): self
    {
        $this->searchCaseInsensitive = $caseInsensitive;
        return $this;
    }

    public function type(string $type): self
    {
        $validTypes = [
            self::TYPE_STARTS_WITH,
            self::TYPE_ENDS_WITH,
            self::TYPE_CONTAINS,
            self::TYPE_EQUAL,
        ];

        if (!in_array($type, $validTypes, true)) {
            throw new \InvalidArgumentException(
                "Invalid search type '{$type}'. Valid types are: " . implode(', ', $validTypes)
            );
        }

        $this->searchType = $type;
        return $this;
    }

    public function cast(callable $castCallable): self
    {
        $this->castCallable = $castCallable;
        return $this;
    }

    public function logical(string $operator): self
    {
        $operator = strtoupper($operator);
        
        if (!in_array($operator, [self::TYPE_LOGICAL_AND, self::TYPE_LOGICAL_OR], true)) {
            throw new \InvalidArgumentException(
                "Invalid logical operator '{$operator}'. Valid operators are: AND, OR"
            );
        }

        $this->logicalOperator = $operator;
        return $this;
    }

    /**
     * Apply the global filter to the query builder.
     */
    public function apply($builder, $data): void
    {
        $searchValue = is_string($data) ? $data : '';

        // Apply caster if provided
        if ($this->castCallable !== null) {
            $searchValue = ($this->castCallable)($searchValue);
        }

        // Skip if no valid search value (allow "0")
        if ($this->isEmptySearchValue($searchValue)) {
            return;
        }

        // Apply relation-aware filtering
        if ($this->relation !== '') {
            $this->applyRelationFilter($builder, $searchValue);
        } else {
            $this->applyDirectFilter($builder, $searchValue, $this->logicalOperator);
        }
    }

    /**
     * Check if search value is considered empty.
     */
    private function isEmptySearchValue(mixed $searchValue): bool
    {
        if ($searchValue === null) {
            return true;
        }

        return $searchValue === '' && $searchValue !== '0';
    }

    /**
     * Apply filter for relation columns.
     */
    private function applyRelationFilter($builder, string $searchValue): void
    {
        if ($this->logicalOperator === self::TYPE_LOGICAL_OR) {
            $builder->orWhereHas($this->relation, function ($query) use ($searchValue) {
                $this->applyDirectFilter($query, $searchValue, self::TYPE_LOGICAL_AND);
            });
        } else {
            $builder->whereHas($this->relation, function ($query) use ($searchValue) {
                $this->applyDirectFilter($query, $searchValue, self::TYPE_LOGICAL_AND);
            });
        }
    }

    /**
     * Apply direct column filter.
     */
    private function applyDirectFilter(
        $builder,
        string $searchValue,
        string $operator = self::TYPE_LOGICAL_AND
    ): void {
        $searchKey = $this->buildSearchKey();
        $value = $this->transformSearchValue($searchValue);
        $searchExpr = DB::raw($searchKey);

        if ($this->searchType !== self::TYPE_EQUAL) {
            $this->applyLikeFilter($builder, $searchExpr, $value, $operator);
        } else {
            $this->applyEqualFilter($builder, $searchExpr, $value, $operator);
        }
    }

    /**
     * Build the search key for the column.
     */
    private function buildSearchKey(): string
    {
        if ($this->searchCaseInsensitive) {
            return 'LOWER(' . $this->wrapColumn($this->columnKey) . ')';
        }

        return $this->wrapColumn($this->columnKey);
    }

    /**
     * Transform search value based on search type.
     */
    private function transformSearchValue(string $searchValue): string
    {
        $value = match ($this->searchType) {
            self::TYPE_STARTS_WITH => $searchValue . '%',
            self::TYPE_ENDS_WITH => '%' . $searchValue,
            self::TYPE_CONTAINS => '%' . $searchValue . '%',
            default => $searchValue,
        };

        // Apply case transformation if needed
        if ($this->searchCaseInsensitive) {
            return mb_strtolower($value);
        }

        return $value;
    }

    /**
     * Apply LIKE filter.
     */
    private function applyLikeFilter(
        $builder,
        mixed $searchExpr,
        string $value,
        string $operator
    ): void {
        if ($operator === self::TYPE_LOGICAL_OR) {
            $builder->orWhere($searchExpr, 'like', $value);
        } else {
            $builder->where($searchExpr, 'like', $value);
        }
    }

    /**
     * Apply exact match filter.
     */
    private function applyEqualFilter(
        $builder,
        mixed $searchExpr,
        string $value,
        string $operator
    ): void {
        if ($operator === self::TYPE_LOGICAL_OR) {
            $builder->orWhere($searchExpr, $value);
        } else {
            $builder->where($searchExpr, $value);
        }
    }

    /**
     * Wrap column name for safe SQL usage.
     */
    private function wrapColumn(string $column): string
    {
        return $column;
    }

    /**
     * Get the column key.
     */
    public function getColumnKey(): string
    {
        return $this->columnKey;
    }

    /**
     * Get the relation name.
     */
    public function getRelation(): string
    {
        return $this->relation;
    }

    /**
     * Check if case insensitive search is enabled.
     */
    public function isCaseInsensitive(): bool
    {
        return $this->searchCaseInsensitive;
    }

    /**
     * Get the search type.
     */
    public function getSearchType(): string
    {
        return $this->searchType;
    }

    /**
     * Get the logical operator.
     */
    public function getLogicalOperator(): string
    {
        return $this->logicalOperator;
    }
}