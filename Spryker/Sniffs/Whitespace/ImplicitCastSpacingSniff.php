<?php

/**
 * (c) Spryker Systems GmbH copyright protected.
 */
namespace Spryker\Sniffs\Whitespace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * No whitespace should be between implicit cast and variable, the same as with other casts.
 * This includes incrementor and decrementor.
 */
class ImplicitCastSpacingSniff implements Sniff
{

    /**
     * @inheritdoc
     */
    public function register()
    {
        return [T_BOOLEAN_NOT, T_NONE, T_ASPERAND, T_INC, T_DEC];
    }

    /**
     * @inheritdoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] === T_INC || $tokens[$stackPtr]['code'] === T_DEC) {
            $this->processIncDec($phpcsFile, $stackPtr);
            return;
        }

        $nextIndex = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);

        if ($nextIndex - $stackPtr === 1) {
            return;
        }

        $fix = $phpcsFile->addFixableError('No whitespace should be between ' . $tokens[$stackPtr]['content'] . ' and variable.', $stackPtr);
        if ($fix && $phpcsFile->fixer->enabled) {
            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->replaceToken($stackPtr + 1, '');
            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function processIncDec(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if ($tokens[$nextIndex]['code'] === T_VARIABLE) {
            if ($nextIndex - $stackPtr === 1) {
                return;
            }

            $fix = $phpcsFile->addFixableError('No whitespace should be between incrementor and variable.', $stackPtr);
            if ($fix) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->replaceToken($stackPtr + 1, '');
                $phpcsFile->fixer->endChangeset();
            }
            return;
        }

        $prevIndex = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($tokens[$prevIndex]['code'] === T_VARIABLE) {
            if ($stackPtr - $prevIndex === 1) {
                return;
            }

            $fix = $phpcsFile->addFixableError('No whitespace should be between variable and incrementor.', $stackPtr);
            if ($fix) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->replaceToken($stackPtr - 1, '');
                $phpcsFile->fixer->endChangeset();
            }
            return;
        }
    }

}
