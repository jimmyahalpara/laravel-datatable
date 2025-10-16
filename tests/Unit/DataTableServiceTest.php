<?php

declare(strict_types=1);

namespace JimmyAhalpara\LaravelDatatable\Tests\Unit;

use Illuminate\Http\Request;
use JimmyAhalpara\LaravelDatatable\DataTableService;
use JimmyAhalpara\LaravelDatatable\Filters\ColumnFilter;
use JimmyAhalpara\LaravelDatatable\Filters\CustomFilter;
use JimmyAhalpara\LaravelDatatable\Filters\GlobalFilter;
use JimmyAhalpara\LaravelDatatable\Tests\Models\TestUser;
use JimmyAhalpara\LaravelDatatable\Tests\TestCase;

class DataTableServiceTest extends TestCase
{
    private TestUser $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new TestUser();
    }

    public function test_can_create_datatable_service(): void
    {
        $builder = $this->model->newQuery();
        $service = DataTableService::make($builder);
        
        $this->assertInstanceOf(DataTableService::class, $service);
        $this->assertEquals($builder, $service->getBuilder());
    }

    public function test_can_set_page(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        
        $service->setPage(2);
        $this->assertEquals(2, $service->getPage());
    }

    public function test_throws_exception_for_invalid_page(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        
        $this->expectException(\InvalidArgumentException::class);
        $service->setPage(0);
    }

    public function test_can_set_items_per_page(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        
        $service->setItemsPerPage(25);
        $this->assertEquals(25, $service->getItemsPerPage());
    }

    public function test_throws_exception_for_invalid_items_per_page(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        
        $this->expectException(\InvalidArgumentException::class);
        $service->setItemsPerPage(0);
    }

    public function test_throws_exception_for_too_many_items_per_page(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        
        $this->expectException(\InvalidArgumentException::class);
        $service->setItemsPerPage(1000);
    }

    public function test_can_set_sort_by(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        $sortBy = [
            ['key' => 'name', 'order' => 'asc'],
            ['key' => 'created_at', 'order' => 'desc']
        ];
        
        $service->setSortBy($sortBy);
        $this->assertEquals($sortBy, $service->getSortBy());
    }

    public function test_throws_exception_for_invalid_sort_structure(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        
        $this->expectException(\InvalidArgumentException::class);
        $service->setSortBy([['key' => 'name']]); // Missing 'order'
    }

    public function test_throws_exception_for_invalid_sort_order(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        
        $this->expectException(\InvalidArgumentException::class);
        $service->setSortBy([['key' => 'name', 'order' => 'invalid']]);
    }

    public function test_can_set_resource_class(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        
        $service->setResourceClass(TestUser::class);
        $this->assertEquals(TestUser::class, $service->getResourceClass());
    }

    public function test_throws_exception_for_non_existent_resource_class(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        
        $this->expectException(\InvalidArgumentException::class);
        $service->setResourceClass('NonExistentClass');
    }

    public function test_can_set_global_filters(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        $filters = [
            GlobalFilter::make('name'),
            GlobalFilter::make('email')
        ];
        
        $service->setGlobalFilters($filters);
        $this->assertEquals($filters, $service->getGlobalFilters());
    }

    public function test_throws_exception_for_invalid_global_filter(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        
        $this->expectException(\InvalidArgumentException::class);
        $service->setGlobalFilters(['invalid_filter']);
    }

    public function test_can_set_column_filters(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        $filters = [
            ColumnFilter::make('name'),
            ColumnFilter::make('email')
        ];
        
        $service->setColumnFilters($filters);
        $this->assertEquals($filters, $service->getColumnFilters());
    }

    public function test_throws_exception_for_invalid_column_filter(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        
        $this->expectException(\InvalidArgumentException::class);
        $service->setColumnFilters(['invalid_filter']);
    }

    public function test_can_set_custom_filters(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        $filters = [
            CustomFilter::make(function ($builder, $data) {
                return $builder->where('active', true);
            })
        ];
        
        $service->setCustomFilters($filters);
        $this->assertEquals($filters, $service->getCustomFilters());
    }

    public function test_throws_exception_for_invalid_custom_filter(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        
        $this->expectException(\InvalidArgumentException::class);
        $service->setCustomFilters(['invalid_filter']);
    }

    public function test_can_set_download_columns(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        $columns = ['name', 'email', 'created_at'];
        
        $service->setDownloadColumns($columns);
        $this->assertEquals($columns, $service->getDownloadColumns());
    }

    public function test_can_set_download_mapper(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        $mapper = function ($item) {
            return ['name' => strtoupper($item->name)];
        };
        
        $service->setDownloadMapper($mapper);
        // Can't directly test the mapper, but ensure no exception
        $this->assertTrue(true);
    }

    public function test_fill_from_request_sets_properties(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        
        // Set download columns first to enable download
        $service->setDownloadColumns(['name', 'email']);
        
        $request = Request::create('/', 'GET', [
            'page' => 3,
            'itemsPerPage' => 20,
            'sortBy' => [['key' => 'name', 'order' => 'asc']],
            'filter' => ['search' => 'test'],
            'download' => 1
        ]);
        
        $service->fillFromRequest($request);
        
        $this->assertEquals(3, $service->getPage());
        $this->assertEquals(20, $service->getItemsPerPage());
        $this->assertEquals([['key' => 'name', 'order' => 'asc']], $service->getSortBy());
        $this->assertEquals(['search' => 'test'], $service->getRequestFilter());
        $this->assertTrue($service->expectsDownload());
    }

    public function test_fill_from_request_uses_defaults(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        
        $request = Request::create('/', 'GET');
        $service->fillFromRequest($request);
        
        $this->assertEquals(1, $service->getPage());
        $this->assertEquals(10, $service->getItemsPerPage()); // Default from config
        $this->assertEquals([], $service->getSortBy());
        $this->assertEquals([], $service->getRequestFilter());
        $this->assertFalse($service->expectsDownload());
    }

    public function test_fill_from_request_validates_values(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        
        $request = Request::create('/', 'GET', [
            'page' => 0, // Invalid, should default to 1
            'itemsPerPage' => 1000, // Too high, should cap to max
        ]);
        
        $service->fillFromRequest($request);
        
        $this->assertEquals(1, $service->getPage());
        $this->assertEquals(100, $service->getItemsPerPage()); // Max from config
    }

    public function test_expects_download_only_when_both_flag_and_columns_set(): void
    {
        $service = DataTableService::make($this->model->newQuery());
        
        // No download flag, no columns
        $this->assertFalse($service->expectsDownload());
        
        // Download flag but no columns
        $request = Request::create('/', 'GET', ['download' => 1]);
        $service->fillFromRequest($request);
        $this->assertFalse($service->expectsDownload());
        
        // Download flag and columns
        $service->setDownloadColumns(['name', 'email']);
        $this->assertTrue($service->expectsDownload());
    }

    public function test_method_chaining_works(): void
    {
        $builder = $this->model->newQuery();
        
        $service = DataTableService::make($builder)
            ->setPage(2)
            ->setItemsPerPage(25)
            ->setSortBy([['key' => 'name', 'order' => 'asc']])
            ->setGlobalFilters([GlobalFilter::make('name')])
            ->setColumnFilters([ColumnFilter::make('email')])
            ->setDownloadColumns(['name', 'email']);
        
        $this->assertEquals(2, $service->getPage());
        $this->assertEquals(25, $service->getItemsPerPage());
        $this->assertCount(1, $service->getGlobalFilters());
        $this->assertCount(1, $service->getColumnFilters());
        $this->assertEquals(['name', 'email'], $service->getDownloadColumns());
    }
}