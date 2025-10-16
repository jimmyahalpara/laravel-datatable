# Changelog

All notable changes to `laravel-datatable` will be documented in this file.

## 1.0.0 - 2024-01-15

### Added
- Initial release of Laravel DataTable package
- Global search filters with configurable search types
- Column-specific filters with array value support
- Custom filters using callables
- Multi-column sorting with validation
- Built-in pagination with configurable limits
- Laravel API Resource integration
- Export/download functionality with custom mappers
- Comprehensive test coverage
- Type-safe PHP 8.1+ implementation
- Fluent API with method chaining
- Relation-aware filtering with `whereHas()` support
- Case-insensitive search options
- Configuration file for package customization
- Facade support for easier usage
- Comprehensive documentation and examples

### Features
- **ColumnFilter**: Advanced column filtering with support for arrays, relations, and type casting
- **GlobalFilter**: Multi-column search functionality for global search features
- **CustomFilter**: Flexible custom filtering using callables for complex logic
- **DataTableService**: Main service class orchestrating all filtering, sorting, and pagination
- **Request Integration**: Automatic population from HTTP requests
- **Resource Integration**: Seamless Laravel API Resource support
- **Export System**: Built-in download functionality with custom data transformation

### Technical Improvements
- Full PHP 8.1+ type hints and strict typing
- PSR-12 coding standards compliance
- Comprehensive PHPUnit test suite with edge case coverage
- Modern Laravel package structure with service provider auto-discovery
- Proper namespace organization and autoloading
- Exception handling with descriptive error messages
- Performance optimizations for large datasets