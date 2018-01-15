<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Traits\UseStatementsTrait;

/**
 * Ensures all use statements with aliasing have lowercase "as"
 */
class UseWithAliasingSniff extends AbstractSprykerSniff
{
    use UseStatementsTrait;

    /**
     * @inheritdoc
     */
    public function register()
    {
        return [T_AS];
    }

    /**
     * @inheritdoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $content = $tokens[$stackPtr]['content'];
        if ($content === 'as') {
            return;
        }

        if (!empty($tokens[$stackPtr]['conditions']) || !empty($tokens[$stackPtr]['nested_parenthesis'])) {
            // Let Squiz.ControlStructures.ForEachLoopDeclaration handle this
            return;
        }

        $newContent = strtolower($content);

        $fix = $phpcsFile->addFixableError(sprintf('Alias keyword `%s` should be `%s`', $content, $newContent), $stackPtr, 'InvalidAliasKeyword');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->replaceToken($stackPtr, $newContent);
    }
}
