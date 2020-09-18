<?php

declare(strict_types=1);


$root = __DIR__;

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->in($root);

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR1' => true,
        '@PSR2' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PhpCsFixer' => true,
        'align_multiline_comment' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'return_assignment' => false,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'dir_constant' => true,
        'elseif' => false,
        'ereg_to_preg' => true,
        'is_null' => true,
        'list_syntax' => ['syntax' => 'short'],
        'mb_str_functions' => true,
        'phpdoc_order' => true,
        'concat_space' => ['spacing' => 'one'],
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
        ],
        'phpdoc_to_comment' => false,
        'method_argument_space' => ['on_multiline' => 'ignore'],
        'php_unit_ordered_covers' => false,
        'no_superfluous_elseif' => false,
    ])
    ->setCacheFile(sys_get_temp_dir() . '/php_cs.cache')
    ->setFinder($finder);
