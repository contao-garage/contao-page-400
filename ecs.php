<?php

declare(strict_types=1);

use Contao\EasyCodingStandard\Set\SetList;
use PhpCsFixer\Fixer\ArrayNotation\NoWhitespaceBeforeCommaInArrayFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAddMissingParamAnnotationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocOrderFixer;
use PhpCsFixer\Fixer\ReturnNotation\NoUselessReturnFixer;
use PhpCsFixer\Fixer\Semicolon\MultilineWhitespaceBeforeSemicolonsFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

$header = <<<EOF
This file is part of contao-garage/contao-page-400.

@author    Martin Schumann <martin.schumann@ontao-garage.de>
@license   MIT
@copyright Contao Garage %s
EOF;
$header = sprintf($header, date('Y'));

return ECSConfig::configure()
    ->withSets([SetList::CONTAO])
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withConfiguredRule(HeaderCommentFixer::class, ['header' => "$header"])
    ->withConfiguredRule(BlankLineBeforeStatementFixer::class, ['statements' => [
        'do',
        'exit',
        'for',
        'foreach',
        'if',
        'return',
        'switch',
        'try',
        'while',
    ]])
    ->withConfiguredRule(MultilineWhitespaceBeforeSemicolonsFixer::class, ['strategy' => 'new_line_for_chained_calls'])
    ->withRules(
        [ConcatSpaceFixer::class],
        [NotOperatorWithSuccessorSpaceFixer::class],
        [NoUselessReturnFixer::class],
        [NoWhitespaceBeforeCommaInArrayFixer::class],
        [PhpdocAddMissingParamAnnotationFixer::class],
        [PhpdocOrderFixer::class],
    )
;
