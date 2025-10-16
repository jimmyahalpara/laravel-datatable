<?php

declare(strict_types=1);

namespace JimmyAhalpara\LaravelDatatable\Tests\Unit\Filters;

use JimmyAhalpara\LaravelDatatable\Filters\GlobalFilter;
use JimmyAhalpara\LaravelDatatable\Tests\Models\TestUser;
use JimmyAhalpara\LaravelDatatable\Tests\TestCase;

class GlobalFilterTest extends TestCase
{
    private TestUser $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new TestUser();
    }

    public function test_can_create_global_filter(): void
    {
        $filter = GlobalFilter::make('name');
        
        $this->assertInstanceOf(GlobalFilter::class, $filter);
        $this->assertEquals('name', $filter->getColumnKey());
        $this->assertEquals('', $filter->getRelation());
    }

    public function test_defaults_to_case_insensitive_contains(): void
    {
        $filter = GlobalFilter::make('name');
        
        $this->assertTrue($filter->isCaseInsensitive());
        $this->assertEquals(GlobalFilter::TYPE_CONTAINS, $filter->getSearchType());
        $this->assertEquals(GlobalFilter::TYPE_LOGICAL_OR, $filter->getLogicalOperator());
    }

    public function test_can_create_global_filter_with_relation(): void
    {
        $filter = GlobalFilter::make('user.name');
        
        $this->assertEquals('name', $filter->getColumnKey());
        $this->assertEquals('user', $filter->getRelation());
    }

    public function test_does_not_apply_filter_for_empty_values(): void
    {
        $filter = GlobalFilter::make('name');
        $builder = $this->model->newQuery();
        $originalSql = $builder->toSql();
        
        // Test null value
        $filter->apply($builder, null);
        $this->assertEquals($originalSql, $builder->toSql());
        
        // Test empty string (except '0')
        $filter->apply($builder, '');
        $this->assertEquals($originalSql, $builder->toSql());
    }

    public function test_applies_filter_for_zero_string(): void
    {
        $filter = GlobalFilter::make('name');
        $builder = $this->model->newQuery();
        $originalSql = $builder->toSql();
        
        $filter->apply($builder, '0');
        
        // Query should be modified for '0' value
        $this->assertNotEquals($originalSql, $builder->toSql());
    }

    public function test_applies_contains_filter_by_default(): void
    {
        $filter = GlobalFilter::make('name');
        $builder = $this->model->newQuery();
        
        $filter->apply($builder, 'John');
        
        $sql = strtolower($builder->toSql());
        $this->assertStringContainsString('like', $sql);
        // Should wrap with % for contains and be lowercase (case insensitive by default)
        $bindings = $builder->getBindings();
        $this->assertStringContainsString('%john%', $bindings[0] ?? '');
    }

    public function test_case_insensitive_search_by_default(): void
    {
        $filter = GlobalFilter::make('name');
        $builder = $this->model->newQuery();
        
        $filter->apply($builder, 'John');
        
        $sql = strtolower($builder->toSql());
        $this->assertStringContainsString('lower', $sql);
    }

    public function test_can_disable_case_insensitive(): void
    {
        $filter = GlobalFilter::make('name')->caseInsensitive(false);
        $builder = $this->model->newQuery();
        
        $filter->apply($builder, 'John');
        
        $sql = strtolower($builder->toSql());
        $this->assertStringNotContainsString('lower', $sql);
    }

    public function test_can_set_different_search_types(): void
    {
        $filter = GlobalFilter::make('name');
        $builder = $this->model->newQuery();
        
        // Test starts with (case insensitive by default)
        $filter->type(GlobalFilter::TYPE_STARTS_WITH);
        $filter->apply($builder, 'John');
        $bindings = $builder->getBindings();
        $this->assertStringContainsString('john%', $bindings[0] ?? '');
        
        // Reset builder
        $builder = $this->model->newQuery();
        
        // Test ends with (case insensitive by default)
        $filter->type(GlobalFilter::TYPE_ENDS_WITH);
        $filter->apply($builder, 'John');
        $bindings = $builder->getBindings();
        $this->assertStringContainsString('%john', $bindings[0] ?? '');
    }

    public function test_can_apply_cast_function(): void
    {
        $caster = fn($value) => strtoupper($value);
        $filter = GlobalFilter::make('name')->cast($caster);
        $builder = $this->model->newQuery();
        
        $filter->apply($builder, 'john');
        
        // Should not throw exception
        $this->assertTrue(true);
    }

    public function test_method_chaining_works(): void
    {
        $filter = GlobalFilter::make('name')
            ->type(GlobalFilter::TYPE_STARTS_WITH)
            ->caseInsensitive(false)
            ->logical(GlobalFilter::TYPE_LOGICAL_AND);
        
        $this->assertEquals(GlobalFilter::TYPE_STARTS_WITH, $filter->getSearchType());
        $this->assertFalse($filter->isCaseInsensitive());
        $this->assertEquals(GlobalFilter::TYPE_LOGICAL_AND, $filter->getLogicalOperator());
    }
}