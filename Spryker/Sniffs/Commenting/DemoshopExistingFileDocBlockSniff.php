<?php

namespace Spryker_CodeSniffer\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;

/**
 * Checks if Spryker Demoshop's files have doc block comment and have the expected content.
 * This sniff is skipped for customer's projects.
 */
class DemoshopExistingFileDocBlockSniff extends AbstractDemoshopFileDocBlockSniff
{

    const FIRST_COMMENT_LINE_POSITION = 5;
    const SECOND_COMMENT_LINE_POSITION = 10;
    const EXPECTED_FILE_DOC_BLOCK_TOKEN_COUNT = 14;

    /**
     * @inheritdoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->isPyzNamespace($phpCsFile, $stackPointer) || !$this->isDemoshop($phpCsFile)) {
            return;
        }

        if ($this->existsFileDocBlock($phpCsFile, $stackPointer)
            && ($this->hasNotExpectedLength($phpCsFile, $stackPointer) || $this->hasWrongContent($phpCsFile, $stackPointer))
        ) {
            $this->addFixableExistingDocBlock($phpCsFile, $stackPointer);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasNotExpectedLength(File $phpCsFile, $stackPointer)
    {
        $fileDockBlockTokens = $this->getFileDocBlockTokens($phpCsFile, $stackPointer);

        return (count($fileDockBlockTokens) !== static::EXPECTED_FILE_DOC_BLOCK_TOKEN_COUNT);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasWrongContent(File $phpCsFile, $stackPointer)
    {
        $fileDockBlockTokens = $this->getFileDocBlockTokens($phpCsFile, $stackPointer);

        $firstLineComment = $fileDockBlockTokens[static::FIRST_COMMENT_LINE_POSITION]['content'];
        $secondLineComment = $fileDockBlockTokens[static::SECOND_COMMENT_LINE_POSITION]['content'];

        if ($firstLineComment !== static::EXPECTED_COMMENT_FIRST_LINE_STRING
            || $secondLineComment !== static::EXPECTED_COMMENT_SECOND_LINE_STRING) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return array
     */
    protected function getFileDocBlockTokens(File $phpCsFile, $stackPointer)
    {
        $fileDocBlockStartPosition = $phpCsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPointer);
        $fileDocBlockEndPosition = $phpCsFile->findNext(T_DOC_COMMENT_CLOSE_TAG, $fileDocBlockStartPosition) + 1;

        $tokens = $phpCsFile->getTokens();

        return array_slice($tokens, $fileDocBlockStartPosition, $fileDocBlockEndPosition - $fileDocBlockStartPosition);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function addFixableExistingDocBlock(File $phpCsFile, $stackPointer)
    {
        $fix = $phpCsFile->addFixableError(basename($phpCsFile->getFilename()) . ' has the wrong file doc block', $stackPointer);
        if ($fix) {
            $this->addFileDocBlock($phpCsFile, 0);
        }
    }

}
