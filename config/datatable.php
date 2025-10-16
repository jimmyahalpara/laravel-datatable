<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Items Per Page
    |--------------------------------------------------------------------------
    |
    | The default number of items to display per page when no specific
    | value is provided in the request.
    |
    */
    'default_items_per_page' => 10,

    /*
    |--------------------------------------------------------------------------
    | Maximum Items Per Page
    |--------------------------------------------------------------------------
    |
    | The maximum number of items that can be displayed per page.
    | This helps prevent performance issues with large datasets.
    |
    */
    'max_items_per_page' => 100,

    /*
    |--------------------------------------------------------------------------
    | Default Search Type
    |--------------------------------------------------------------------------
    |
    | The default search type to use for filters when no specific
    | type is specified. Available options:
    | - 'contains' (default)
    | - 'startsWith'
    | - 'endsWith' 
    | - 'equal'
    |
    */
    'default_search_type' => 'contains',

    /*
    |--------------------------------------------------------------------------
    | Case Insensitive Search
    |--------------------------------------------------------------------------
    |
    | Whether searches should be case insensitive by default.
    |
    */
    'case_insensitive' => true,

    /*
    |--------------------------------------------------------------------------
    | Download Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for data export/download functionality.
    |
    */
    'download' => [
        'max_execution_time' => 1800, // 30 minutes
        'default_filename' => 'export',
        'default_format' => 'xlsx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for caching query results to improve performance.
    |
    */
    'cache' => [
        'enabled' => false,
        'ttl' => 300, // 5 minutes
        'prefix' => 'datatable',
    ],
];