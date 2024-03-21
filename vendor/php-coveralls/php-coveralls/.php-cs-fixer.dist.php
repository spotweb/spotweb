<?php

$finder = PhpCsFixer\Finder::create()
    ->ignoreDotFiles(false)
    ->ignoreVCSIgnored(true)
    ->exclude('tests/Fixture')
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
        'concat_space' => ['spacing' => 'one'],
        'explicit_indirect_variable' => false,
        'explicit_string_variable' => false,
        'method_chaining_indentation' => false,
        'php_unit_test_annotation' => false,
        'single_line_comment_style' => false,
        'ternary_to_elvis_operator' => false,
        'visibility_required' => ['elements' => ['method', 'property']],
    ])
    ->setFinder($finder)
;

return $config;
