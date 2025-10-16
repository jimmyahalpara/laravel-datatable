# Laravel DataTable Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jimmyahalpara/laravel-datatable.svg?style=flat-square)](https://packagist.org/packages/jimmyahalpara/laravel-datatable)
[![Total Downloads](https://img.shields.io/packagist/dt/jimmyahalpara/laravel-datatable.svg?style=flat-square)](https://packagist.org/packages/jimmyahalpara/laravel-datatable)
[![License](https://img.shields.io/packagist/l/jimmyahalpara/laravel-datatable.svg?style=flat-square)](https://packagist.org/packages/jimmyahalpara/laravel-datatable)
[![PHP Version](https://img.shields.io/packagist/php-v/jimmyahalpara/laravel-datatable.svg?style=flat-square)](https://packagist.org/packages/jimmyahalpara/laravel-datatable)

A powerful Laravel package for building advanced DataTable functionality with column filtering, global search, custom filters, sorting, and pagination. This package provides a fluent, type-safe API for creating complex data tables with minimal code.

## Features

- 🔍 **Global Search**: Search across multiple columns with configurable search types
- 🎯 **Column Filtering**: Apply specific filters to individual columns
- 🔧 **Custom Filters**: Create complex custom filtering logic with callables
- 📊 **Sorting**: Multi-column sorting with validation
- 📄 **Pagination**: Built-in pagination with customizable page sizes
- 🏗️ **Fluent API**: Chainable methods for clean, readable code
- 🎨 **Resource Integration**: Seamless integration with Laravel API resources
- 📤 **Export Support**: Built-in download functionality with custom mappers
- 🔒 **Type Safe**: Full PHP 8.1+ type hints and strict typing
- 🧪 **Well Tested**: Comprehensive test suite with high code coverage
- ⚡ **Performance**: Optimized queries with relation-aware filtering

## Requirements

- PHP 8.1+
- Laravel 9.0+

## Installation

You can install the package via composer:

```bash
composer require jimmyahalpara/laravel-datatable
```

The package will automatically register itself via Laravel's package discovery.

Optionally, you can publish the configuration file:

```bash
php artisan vendor:publish --tag="datatable-config"
```

## Quick Start

Here's a simple example to get you started:

```php
use JimmyAhalpara\LaravelDatatable\DataTableService;
use JimmyAhalpara\LaravelDatatable\Filters\ColumnFilter;
use JimmyAhalpara\LaravelDatatable\Filters\GlobalFilter;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query();
        
        $dataTable = DataTableService::make($users)
            ->setGlobalFilters([
                GlobalFilter::make('name')->type('contains'),
                GlobalFilter::make('email')->type('contains'),
            ])
            ->setColumnFilters([
                ColumnFilter::make('status')->type('equal'),
                ColumnFilter::make('created_at')->cast(fn($date) => Carbon::parse($date)),
            ])
            ->fillFromRequest($request);
            
        return $dataTable->render();
    }
}
```

## Configuration

The package comes with a configuration file that allows you to customize default behavior:

```php
return [
    // Default number of items per page
    'default_items_per_page' => 10,
    
    // Maximum items per page (prevents performance issues)
    'max_items_per_page' => 100,
    
    // Default search type for filters
    'default_search_type' => 'contains',
    
    // Enable case insensitive search by default
    'case_insensitive' => true,
    
    // Download configuration
    'download' => [
        'max_execution_time' => 1800, // 30 minutes
        'default_filename' => 'export',
        'default_format' => 'xlsx',
    ],
    
    // Cache configuration
    'cache' => [
        'enabled' => false,
        'ttl' => 300, // 5 minutes
        'prefix' => 'datatable',
    ],
];
```

## Usage Guide

### Creating a DataTable Service

Start by creating a DataTable service with an Eloquent builder:

```php
use JimmyAhalpara\LaravelDatatable\DataTableService;
use App\Models\Post;

$posts = Post::with('user', 'categories');
$dataTable = DataTableService::make($posts);
```

### Global Filters

Global filters are applied when the user performs a global search. They typically search across multiple columns:

```php
use JimmyAhalpara\LaravelDatatable\Filters\GlobalFilter;

$dataTable->setGlobalFilters([
    GlobalFilter::make('title')
        ->type(GlobalFilter::TYPE_CONTAINS)
        ->caseInsensitive(true),
        
    GlobalFilter::make('content')
        ->type(GlobalFilter::TYPE_CONTAINS),
        
    // Search in related models
    GlobalFilter::make('user.name')
        ->type(GlobalFilter::TYPE_CONTAINS),
]);
```

#### Global Filter Options

- **Search Types**: `contains`, `startsWith`, `endsWith`, `equal`
- **Case Sensitivity**: Enable/disable case-insensitive search
- **Logical Operators**: `AND`, `OR` (defaults to `OR` for global search)
- **Value Casting**: Transform search values before applying

```php
GlobalFilter::make('published_at')
    ->type('equal')
    ->cast(function ($value) {
        return Carbon::parse($value)->format('Y-m-d');
    });
```

### Column Filters

Column filters are applied to specific columns based on user input:

```php
use JimmyAhalpara\LaravelDatatable\Filters\ColumnFilter;

$dataTable->setColumnFilters([
    // Simple equality filter
    ColumnFilter::make('status')
        ->type(ColumnFilter::TYPE_EQUAL),
        
    // Case-insensitive contains filter
    ColumnFilter::make('title')
        ->type(ColumnFilter::TYPE_CONTAINS)
        ->caseInsensitive(true),
        
    // Filter with value transformation
    ColumnFilter::make('price')
        ->type(ColumnFilter::TYPE_EQUAL)
        ->cast(fn($value) => (float) $value),
        
    // Relation filtering
    ColumnFilter::make('category.name')
        ->type(ColumnFilter::TYPE_EQUAL),
]);
```

#### Column Filter Features

**Array Value Support**: Column filters automatically handle array values using `IN` clauses:

```php
// Request: filter[status][] = ['active', 'pending']
// Generates: WHERE status IN ('active', 'pending')
```

**Relation Support**: Filter on related model columns:

```php
// This will use whereHas() automatically
ColumnFilter::make('user.email')
    ->type(ColumnFilter::TYPE_CONTAINS)
```

### Custom Filters

For complex filtering logic, use custom filters with callables:

```php
use JimmyAhalpara\LaravelDatatable\Filters\CustomFilter;

$dataTable->setCustomFilters([
    CustomFilter::make(function ($builder, $requestData) {
        // Age range filter
        if (isset($requestData['age_min'], $requestData['age_max'])) {
            $builder->whereBetween('age', [
                $requestData['age_min'],
                $requestData['age_max']
            ]);
        }
        
        // Complex date filtering
        if (isset($requestData['date_range'])) {
            [$start, $end] = explode(' to ', $requestData['date_range']);
            $builder->whereBetween('created_at', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay(),
            ]);
        }
        
        // Conditional filtering
        if (isset($requestData['include_archived']) && !$requestData['include_archived']) {
            $builder->whereNull('archived_at');
        }
    }),
]);
```

### Sorting

Configure multi-column sorting with validation:

```php
$dataTable->setSortBy([
    ['key' => 'created_at', 'order' => 'desc'],
    ['key' => 'name', 'order' => 'asc'],
]);
```

Sorting can also be handled automatically from request parameters:

```js
// Frontend request
{
    "sortBy": [
        {"key": "name", "order": "asc"},
        {"key": "created_at", "order": "desc"}
    ]
}
```

### Pagination

Control pagination settings:

```php
$dataTable
    ->setPage(1)
    ->setItemsPerPage(25);
```

The service automatically validates pagination parameters and applies limits based on configuration.

### Resource Integration

Integrate with Laravel API Resources for consistent JSON responses:

```php
use App\Http\Resources\PostResource;

$dataTable
    ->setResourceClass(PostResource::class)
    ->fillFromRequest($request);
    
return $dataTable->render();
```

This returns a structured response:

```json
{
    "current_page": 1,
    "data": [...],
    "first_page_url": "http://localhost/posts?page=1",
    "from": 1,
    "last_page": 5,
    "last_page_url": "http://localhost/posts?page=5",
    "next_page_url": "http://localhost/posts?page=2",
    "path": "http://localhost/posts",
    "per_page": 10,
    "prev_page_url": null,
    "to": 10,
    "total": 50
}
```

### Export/Download Functionality

Enable data export with custom formatting:

```php
$dataTable
    ->setDownloadColumns(['name', 'email', 'created_at', 'status'])
    ->setDownloadMapper(function ($item) {
        return [
            'Name' => $item->name,
            'Email' => $item->email,
            'Registration Date' => $item->created_at->format('Y-m-d H:i:s'),
            'Status' => ucfirst($item->status),
        ];
    });

// Check if download is requested
if ($dataTable->expectsDownload()) {
    return $dataTable->render(); // Returns download response
}
```

### Request Integration

Automatically populate the DataTable from HTTP requests:

```php
// The request can contain:
// - page: Page number
// - itemsPerPage: Items per page
// - sortBy: Array of sorting configurations
// - filter: Object containing all filter values
// - download: Boolean flag for export

$dataTable->fillFromRequest($request);
return $dataTable->render();
```

Example request structure:

```json
{
    "page": 2,
    "itemsPerPage": 25,
    "sortBy": [
        {"key": "name", "order": "asc"}
    ],
    "filter": {
        "search": "john doe",
        "status": "active",
        "category_id": [1, 2, 3],
        "date_range": "2023-01-01 to 2023-12-31"
    },
    "download": false
}
```

## Advanced Usage

### Method Chaining

The package supports full method chaining for clean, readable code:

```php
return DataTableService::make(User::with('posts'))
    ->setGlobalFilters([
        GlobalFilter::make('name')->type('contains'),
        GlobalFilter::make('email')->type('contains'),
    ])
    ->setColumnFilters([
        ColumnFilter::make('status')->type('equal'),
        ColumnFilter::make('posts.title')->type('contains'),
    ])
    ->setCustomFilters([
        CustomFilter::make(function ($builder, $data) {
            if (isset($data['has_posts'])) {
                $builder->has('posts');
            }
        }),
    ])
    ->setSortBy([['key' => 'created_at', 'order' => 'desc']])
    ->setItemsPerPage(50)
    ->setResourceClass(UserResource::class)
    ->setDownloadColumns(['name', 'email', 'posts_count'])
    ->fillFromRequest($request)
    ->render();
```

### Complex Filtering Example

```php
class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['category', 'brand', 'reviews']);
        
        $dataTable = DataTableService::make($products)
            ->setGlobalFilters([
                GlobalFilter::make('name')->type('contains'),
                GlobalFilter::make('description')->type('contains'),
                GlobalFilter::make('sku')->type('startsWith'),
                GlobalFilter::make('category.name')->type('contains'),
                GlobalFilter::make('brand.name')->type('contains'),
            ])
            ->setColumnFilters([
                ColumnFilter::make('category_id')->type('equal'),
                ColumnFilter::make('brand_id')->type('equal'),
                ColumnFilter::make('status')
                    ->type('equal')
                    ->caseInsensitive(false),
                ColumnFilter::make('price')
                    ->type('equal')
                    ->cast(fn($value) => (float) $value),
                ColumnFilter::make('is_featured')
                    ->type('equal')
                    ->cast(fn($value) => (bool) $value),
            ])
            ->setCustomFilters([
                CustomFilter::make(function ($builder, $data) {
                    // Price range filter
                    if (isset($data['price_min'])) {
                        $builder->where('price', '>=', (float) $data['price_min']);
                    }
                    if (isset($data['price_max'])) {
                        $builder->where('price', '<=', (float) $data['price_max']);
                    }
                    
                    // Rating filter
                    if (isset($data['min_rating'])) {
                        $builder->whereHas('reviews', function ($q) use ($data) {
                            $q->havingRaw('AVG(rating) >= ?', [(float) $data['min_rating']]);
                        });
                    }
                    
                    // Availability filter
                    if (isset($data['in_stock']) && $data['in_stock']) {
                        $builder->where('stock_quantity', '>', 0);
                    }
                    
                    // Date range filter
                    if (isset($data['created_from'])) {
                        $builder->whereDate('created_at', '>=', $data['created_from']);
                    }
                    if (isset($data['created_to'])) {
                        $builder->whereDate('created_at', '<=', $data['created_to']);
                    }
                }),
            ])
            ->setResourceClass(ProductResource::class)
            ->setDownloadColumns([
                'name', 'sku', 'category.name', 'brand.name', 
                'price', 'stock_quantity', 'status', 'created_at'
            ])
            ->setDownloadMapper(function ($product) {
                return [
                    'Product Name' => $product->name,
                    'SKU' => $product->sku,
                    'Category' => $product->category->name ?? 'N/A',
                    'Brand' => $product->brand->name ?? 'N/A',
                    'Price' => '$' . number_format($product->price, 2),
                    'Stock' => $product->stock_quantity,
                    'Status' => ucfirst($product->status),
                    'Created Date' => $product->created_at->format('Y-m-d'),
                    'Average Rating' => $product->reviews_avg_rating ? 
                        round($product->reviews_avg_rating, 1) . '/5' : 'No reviews',
                ];
            })
            ->fillFromRequest($request);
            
        return $dataTable->render();
    }
}
```

## Facade Usage

You can use the facade for a more concise syntax:

```php
use JimmyAhalpara\LaravelDatatable\Facades\DataTable;

return DataTable::make(User::query())
    ->setGlobalFilters([
        GlobalFilter::make('name')->type('contains')
    ])
    ->fillFromRequest($request)
    ->render();
```

## Error Handling

The package provides comprehensive validation and error handling:

```php
try {
    $dataTable = DataTableService::make($builder)
        ->setPage(-1); // Will throw InvalidArgumentException
} catch (\InvalidArgumentException $e) {
    // Handle validation error
    return response()->json(['error' => $e->getMessage()], 400);
}
```

Common validation errors:
- Invalid page numbers (< 1)
- Items per page exceeding maximum limit
- Invalid sort order (not 'asc' or 'desc')
- Non-existent resource classes
- Invalid filter instances

## Performance Considerations

### Query Optimization

- Use `with()` to eager load relationships when using relation filters
- Add database indexes on frequently filtered columns
- Consider using `select()` to limit retrieved columns
- Use `chunk()` for large exports

### Caching

Enable query result caching in the configuration:

```php
'cache' => [
    'enabled' => true,
    'ttl' => 300, // 5 minutes
    'prefix' => 'datatable',
],
```

### Memory Management

For large datasets:
- Set appropriate `max_items_per_page` limits
- Use streaming for large exports
- Implement pagination limits based on user roles

## Testing

The package includes comprehensive tests. Run them with:

```bash
composer test
```

Generate coverage report:

```bash
composer test-coverage
```

## API Reference

### DataTableService

#### Methods

- `make(Builder $builder): self` - Create new instance
- `setPage(int $page): self` - Set current page
- `setItemsPerPage(int $itemsPerPage): self` - Set items per page
- `setSortBy(array $sortBy): self` - Set sorting configuration
- `setResourceClass(string $resourceClass): self` - Set API resource class
- `setGlobalFilters(array $filters): self` - Set global search filters
- `setColumnFilters(array $filters): self` - Set column-specific filters
- `setCustomFilters(array $filters): self` - Set custom filters
- `setDownloadColumns(array $columns): self` - Set export columns
- `setDownloadMapper(callable $mapper): self` - Set export data mapper
- `fillFromRequest(Request $request): self` - Fill from HTTP request
- `apply(): LengthAwarePaginator` - Apply filters and get paginated results
- `expectsDownload(): bool` - Check if download is requested
- `render(): mixed` - Render final response

### GlobalFilter

#### Constants

- `TYPE_STARTS_WITH` - Starts with search
- `TYPE_ENDS_WITH` - Ends with search  
- `TYPE_CONTAINS` - Contains search (default)
- `TYPE_EQUAL` - Exact match
- `TYPE_LOGICAL_AND` - AND operator
- `TYPE_LOGICAL_OR` - OR operator (default)

#### Methods

- `make(string $columnKey): self` - Create new global filter
- `type(string $type): self` - Set search type
- `caseInsensitive(bool $caseInsensitive = true): self` - Set case sensitivity
- `cast(callable $castCallable): self` - Set value transformer
- `logical(string $operator): self` - Set logical operator

### ColumnFilter

Same interface as GlobalFilter with additional array value support.

### CustomFilter

#### Methods

- `make(callable $callable): self` - Create new custom filter
- `apply($builder, $data): void` - Apply filter to query builder

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Jimmy Ahalpara](https://github.com/jimmyahalpara)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.