<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Do not use namespaced function usage.
 */
class FunctionNamespaceSniff implements Sniff
{
    /**
     * @inheritdoc
     */
    public function register()
    {
        return [T_STRING];
    }

    /**
     * @inheritdoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $tokenContent = $tokens[$stackPtr]['content'];

        $openingBrace = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if (!$openingBrace || $tokens[$openingBrace]['type'] !== 'T_OPEN_PARENTHESIS') {
            return;
        }

        $separatorIndex = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if (!$separatorIndex || $tokens[$separatorIndex]['type'] !== 'T_NS_SEPARATOR') {
            return;
        }

        // We skip for non trivial cases
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($separatorIndex - 1), null, true);
        if (!$previous || $tokens[$previous]['type'] === 'T_STRING') {
            return;
        }

        $error = 'Function name ' . $tokenContent . '() found, should not be \ prefixed.';
        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'NamespaceInvalid');
        if ($fix) {
            $phpcsFile->fixer->replaceToken($separatorIndex, '');
        }
    }
}
