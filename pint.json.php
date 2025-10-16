<?php

use Laravel\Pint\Config\RuleSetInterface;

$finder = Symfony\Component\Finder\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return [
    'finder' => $finder,
    'rules' => [
        '@Laravel' => true,
        'binary_operator_spaces' => [
            'operators' => ['=>' => 'align'],
        ],
        'blank_line_after_opening_tag' => false,
        'linebreak_after_opening_tag' => false,
        'declare_strict_types' => true,
        'final_internal_class' => false,
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => false,
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
    ],
];