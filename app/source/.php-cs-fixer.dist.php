<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/classes')
    //->in(__DIR__ . '/tests')

;
$header = <<<EOF
Leafpub: Simple, beautiful publishing. (https://leafpub.org)

@link      https://github.com/Leafpub/leafpub
@copyright Copyright (c) 2016 Leafpub Team
@license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
EOF;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'declare_strict_types' => true,
        'header_comment' => ['header' => $header, 'separate' => 'bottom', 'comment_type' => 'PHPDoc'],
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => true,
        'phpdoc_order' => true,
        'phpdoc_summary' => false,
        'blank_line_after_opening_tag' => false,
        'concat_space' => ['spacing' => 'one'],
        'array_syntax' => ['syntax' => 'short'],
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
        'single_import_per_statement' => false,
    ])
    ->setFinder($finder)
    ;