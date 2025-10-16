<?php

declare(strict_types=1);

namespace JimmyAhalpara\LaravelDatatable\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use JimmyAhalpara\LaravelDatatable\Contracts\FilterInterface;

class ColumnFilter implements FilterInterface
{
    public const TYPE_STARTS_WITH = 'startsWith';
    public const TYPE_ENDS_WITH = 'endsWith';
    public const TYPE_CONTAINS = 'contains';
    public const TYPE_EQUAL = 'equal';

    public const TYPE_LOGICAL_AND = 'AND';
    public const TYPE_LOGICAL_OR = 'OR';

    private string $columnKey;
    private string $relation = '';
    private bool $searchCaseInsensitive = false;
    private string $searchType = self::TYPE_EQUAL;
    private $castCallable = null;
    private string $logicalOperator = self::TYPE_LOGICAL_AND;

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
     * Apply the filter to the query builder.
     */
    public function apply($builder, $data): void
    {
        $requestData = is_array($data) ? $data : [];
        $searchValue = $requestData[$this->columnKey] ?? null;

        // Apply caster if provided
        if ($this->castCallable !== null) {
            if (is_array($searchValue)) {
                $searchValue = array_map($this->castCallable, $searchValue);
            } else {
                $searchValue = ($this->castCallable)($searchValue);
            }
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

        if (is_array($searchValue)) {
            return empty($searchValue);
        }

        return $searchValue === '' && $searchValue !== '0';
    }

    /**
     * Apply filter for relation columns.
     */
    private function applyRelationFilter(Builder|QueryBuilder $builder, mixed $searchValue): void
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
        Builder|QueryBuilder $builder,
        mixed $searchValue,
        string $operator = self::TYPE_LOGICAL_AND
    ): void {
        // Handle array values (IN semantics)
        if (is_array($searchValue)) {
            $this->applyArrayFilter($builder, $searchValue, $operator);
            return;
        }

        // Handle scalar values
        $this->applyScalarFilter($builder, $searchValue, $operator);
    }

    /**
     * Apply filter for array values using IN semantics.
     */
    private function applyArrayFilter(
        Builder|QueryBuilder $builder,
        array $searchValue,
        string $operator
    ): void {
        $values = array_values($searchValue);

        if ($this->searchCaseInsensitive) {
            // Use grouped OR of LOWER(column) = ? for case-insensitive IN
            $lowered = array_map(
                fn($v) => is_string($v) ? mb_strtolower($v) : $v,
                $values
            );

            $group = function ($query) use ($lowered) {
                $first = true;
                foreach ($lowered as $val) {
                    $method = $first ? 'whereRaw' : 'orWhereRaw';
                    $query->{$method}('LOWER(' . $this->wrapColumn($this->columnKey) . ') = ?', [$val]);
                    $first = false;
                }
            };

            if ($operator === self::TYPE_LOGICAL_OR) {
                $builder->orWhere($group);
            } else {
                $builder->where($group);
            }
        } else {
            // Native IN
            if ($operator === self::TYPE_LOGICAL_OR) {
                $builder->orWhereIn($this->columnKey, $values);
            } else {
                $builder->whereIn($this->columnKey, $values);
            }
        }
    }

    /**
     * Apply filter for scalar values.
     */
    private function applyScalarFilter(
        Builder|QueryBuilder $builder,
        mixed $searchValue,
        string $operator
    ): void {
        $searchKey = $this->buildSearchKey();
        $value = $this->transformSearchValue((string) $searchValue);
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
        if ($this->searchCaseInsensitive && is_string($value)) {
            return mb_strtolower($value);
        }

        return $value;
    }

    /**
     * Apply LIKE filter.
     */
    private function applyLikeFilter(
        Builder|QueryBuilder $builder,
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
        Builder|QueryBuilder $builder,
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
        // Basic column wrapping - can be enhanced based on database grammar
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