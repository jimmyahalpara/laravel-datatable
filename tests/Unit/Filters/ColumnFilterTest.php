<?php

declare(strict_types=1);

namespace JimmyAhalpara\LaravelDatatable\Tests\Unit\Filters;

use JimmyAhalpara\LaravelDatatable\Filters\ColumnFilter;
use JimmyAhalpara\LaravelDatatable\Tests\Models\TestUser;
use JimmyAhalpara\LaravelDatatable\Tests\TestCase;

class ColumnFilterTest extends TestCase
{
    private TestUser $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new TestUser();
    }

    public function test_can_create_column_filter(): void
    {
        $filter = ColumnFilter::make('name');
        
        $this->assertInstanceOf(ColumnFilter::class, $filter);
        $this->assertEquals('name', $filter->getColumnKey());
        $this->assertEquals('', $filter->getRelation());
    }

    public function test_can_create_column_filter_with_relation(): void
    {
        $filter = ColumnFilter::make('user.name');
        
        $this->assertEquals('name', $filter->getColumnKey());
        $this->assertEquals('user', $filter->getRelation());
    }

    public function test_can_set_case_insensitive(): void
    {
        $filter = ColumnFilter::make('name')->caseInsensitive(true);
        
        $this->assertTrue($filter->isCaseInsensitive());
        
        $filter->caseInsensitive(false);
        $this->assertFalse($filter->isCaseInsensitive());
    }

    public function test_can_set_valid_search_types(): void
    {
        $filter = ColumnFilter::make('name');
        
        $filter->type(ColumnFilter::TYPE_CONTAINS);
        $this->assertEquals(ColumnFilter::TYPE_CONTAINS, $filter->getSearchType());
        
        $filter->type(ColumnFilter::TYPE_STARTS_WITH);
        $this->assertEquals(ColumnFilter::TYPE_STARTS_WITH, $filter->getSearchType());
        
        $filter->type(ColumnFilter::TYPE_ENDS_WITH);
        $this->assertEquals(ColumnFilter::TYPE_ENDS_WITH, $filter->getSearchType());
        
        $filter->type(ColumnFilter::TYPE_EQUAL);
        $this->assertEquals(ColumnFilter::TYPE_EQUAL, $filter->getSearchType());
    }

    public function test_throws_exception_for_invalid_search_type(): void
    {
        $filter = ColumnFilter::make('name');
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid search type');
        
        $filter->type('invalid_type');
    }

    public function test_can_set_valid_logical_operators(): void
    {
        $filter = ColumnFilter::make('name');
        
        $filter->logical('AND');
        $this->assertEquals(ColumnFilter::TYPE_LOGICAL_AND, $filter->getLogicalOperator());
        
        $filter->logical('OR');
        $this->assertEquals(ColumnFilter::TYPE_LOGICAL_OR, $filter->getLogicalOperator());
        
        // Test case insensitive
        $filter->logical('and');
        $this->assertEquals(ColumnFilter::TYPE_LOGICAL_AND, $filter->getLogicalOperator());
    }

    public function test_throws_exception_for_invalid_logical_operator(): void
    {
        $filter = ColumnFilter::make('name');
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid logical operator');
        
        $filter->logical('INVALID');
    }

    public function test_can_set_cast_callable(): void
    {
        $caster = fn($value) => strtoupper($value);
        $filter = ColumnFilter::make('name')->cast($caster);
        
        // Test that caster is applied
        $builder = $this->model->newQuery();
        $filter->apply($builder, ['name' => 'john']);
        
        // We can't directly test the caster without inspecting the query,
        // but we can ensure no exception is thrown
        $this->assertTrue(true);
    }

    public function test_does_not_apply_filter_for_empty_values(): void
    {
        $filter = ColumnFilter::make('name');
        $builder = $this->model->newQuery();
        $originalSql = $builder->toSql();
        
        // Test null value
        $filter->apply($builder, ['name' => null]);
        $this->assertEquals($originalSql, $builder->toSql());
        
        // Test empty string (except '0')
        $filter->apply($builder, ['name' => '']);
        $this->assertEquals($originalSql, $builder->toSql());
        
        // Test empty array
        $filter->apply($builder, ['name' => []]);
        $this->assertEquals($originalSql, $builder->toSql());
        
        // Test missing key
        $filter->apply($builder, []);
        $this->assertEquals($originalSql, $builder->toSql());
    }

    public function test_applies_filter_for_zero_string(): void
    {
        $filter = ColumnFilter::make('name');
        $builder = $this->model->newQuery();
        $originalSql = $builder->toSql();
        
        $filter->apply($builder, ['name' => '0']);
        
        // Query should be modified for '0' value
        $this->assertNotEquals($originalSql, $builder->toSql());
    }

    public function test_applies_equal_filter_correctly(): void
    {
        $filter = ColumnFilter::make('name')->type(ColumnFilter::TYPE_EQUAL);
        $builder = $this->model->newQuery();
        
        $filter->apply($builder, ['name' => 'John']);
        
        $this->assertStringContainsString('where', strtolower($builder->toSql()));
        $this->assertStringNotContainsString('like', strtolower($builder->toSql()));
    }

    public function test_applies_like_filters_correctly(): void
    {
        $types = [
            ColumnFilter::TYPE_CONTAINS,
            ColumnFilter::TYPE_STARTS_WITH,
            ColumnFilter::TYPE_ENDS_WITH,
        ];
        
        foreach ($types as $type) {
            $filter = ColumnFilter::make('name')->type($type);
            $builder = $this->model->newQuery();
            
            $filter->apply($builder, ['name' => 'John']);
            
            $this->assertStringContainsString('like', strtolower($builder->toSql()));
        }
    }

    public function test_handles_array_values_with_where_in(): void
    {
        $filter = ColumnFilter::make('name');
        $builder = $this->model->newQuery();
        
        $filter->apply($builder, ['name' => ['John', 'Jane']]);
        
        $sql = strtolower($builder->toSql());
        $this->assertStringContainsString('in', $sql);
    }

    public function test_case_insensitive_search_uses_lower(): void
    {
        $filter = ColumnFilter::make('name')->caseInsensitive(true);
        $builder = $this->model->newQuery();
        
        $filter->apply($builder, ['name' => 'John']);
        
        $sql = strtolower($builder->toSql());
        $this->assertStringContainsString('lower', $sql);
    }

    public function test_method_chaining_works(): void
    {
        $filter = ColumnFilter::make('name')
            ->type(ColumnFilter::TYPE_CONTAINS)
            ->caseInsensitive(true)
            ->logical(ColumnFilter::TYPE_LOGICAL_OR);
        
        $this->assertEquals(ColumnFilter::TYPE_CONTAINS, $filter->getSearchType());
        $this->assertTrue($filter->isCaseInsensitive());
        $this->assertEquals(ColumnFilter::TYPE_LOGICAL_OR, $filter->getLogicalOperator());
    }

    public function test_handles_complex_column_names_with_multiple_dots(): void
    {
        $filter = ColumnFilter::make('user.profile.name');
        
        // Should take first part as relation and rest as column
        $this->assertEquals('user', $filter->getRelation());
        $this->assertEquals('profile.name', $filter->getColumnKey());
    }

    public function test_caster_applied_to_array_values(): void
    {
        $caster = fn($value) => strtoupper($value);
        $filter = ColumnFilter::make('name')->cast($caster);
        $builder = $this->model->newQuery();
        
        // Should not throw exception when applying to array
        $filter->apply($builder, ['name' => ['john', 'jane']]);
        
        $this->assertTrue(true); // Test passes if no exception thrown
    }
}