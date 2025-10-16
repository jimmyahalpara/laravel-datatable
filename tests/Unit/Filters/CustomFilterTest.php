<?php

declare(strict_types=1);

namespace JimmyAhalpara\LaravelDatatable\Tests\Unit\Filters;

use JimmyAhalpara\LaravelDatatable\Filters\CustomFilter;
use JimmyAhalpara\LaravelDatatable\Tests\Models\TestUser;
use JimmyAhalpara\LaravelDatatable\Tests\TestCase;

class CustomFilterTest extends TestCase
{
    private TestUser $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new TestUser();
    }

    public function test_can_create_custom_filter(): void
    {
        $callable = function ($builder, $data) {
            return $builder->where('name', $data['search'] ?? '');
        };
        
        $filter = CustomFilter::make($callable);
        
        $this->assertInstanceOf(CustomFilter::class, $filter);
        $this->assertEquals($callable, $filter->getCallable());
    }

    public function test_applies_custom_filter_correctly(): void
    {
        $called = false;
        $passedBuilder = null;
        $passedData = null;
        
        $callable = function ($builder, $data) use (&$called, &$passedBuilder, &$passedData) {
            $called = true;
            $passedBuilder = $builder;
            $passedData = $data;
            return $builder->where('name', 'test');
        };
        
        $filter = CustomFilter::make($callable);
        $builder = $this->model->newQuery();
        $testData = ['search' => 'test'];
        
        $filter->apply($builder, $testData);
        
        $this->assertTrue($called);
        $this->assertEquals($builder, $passedBuilder);
        $this->assertEquals($testData, $passedData);
    }

    public function test_can_modify_query_builder(): void
    {
        $callable = function ($builder, $data) {
            return $builder->where('name', 'John');
        };
        
        $filter = CustomFilter::make($callable);
        $builder = $this->model->newQuery();
        $originalSql = $builder->toSql();
        
        $filter->apply($builder, []);
        
        $this->assertNotEquals($originalSql, $builder->toSql());
        $this->assertStringContainsString('name', $builder->toSql());
    }

    public function test_handles_complex_custom_logic(): void
    {
        $callable = function ($builder, $data) {
            if (isset($data['age_range'])) {
                [$min, $max] = $data['age_range'];
                $builder->whereBetween('age', [$min, $max]);
            }
            
            if (isset($data['city'])) {
                $builder->where('city', $data['city']);
            }
            
            return $builder;
        };
        
        $filter = CustomFilter::make($callable);
        $builder = $this->model->newQuery();
        
        $filter->apply($builder, [
            'age_range' => [18, 65],
            'city' => 'New York'
        ]);
        
        $sql = $builder->toSql();
        $this->assertStringContainsString('age', $sql);
        $this->assertStringContainsString('between', strtolower($sql));
        $this->assertStringContainsString('city', $sql);
    }

    public function test_filter_can_return_void(): void
    {
        $callable = function ($builder, $data): void {
            $builder->where('name', 'test');
        };
        
        $filter = CustomFilter::make($callable);
        $builder = $this->model->newQuery();
        
        // Should not throw exception
        $filter->apply($builder, []);
        
        $this->assertTrue(true);
    }

    public function test_filter_receives_empty_data(): void
    {
        $receivedData = null;
        
        $callable = function ($builder, $data) use (&$receivedData) {
            $receivedData = $data;
        };
        
        $filter = CustomFilter::make($callable);
        $builder = $this->model->newQuery();
        
        $filter->apply($builder, []);
        
        $this->assertEquals([], $receivedData);
    }
}