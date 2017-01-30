<?php

namespace Spryker_CodeSniffer\Sniffs\AbstractSniffs;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use Spryker_CodeSniffer\Traits\BasicsTrait;

abstract class AbstractSprykerSniff implements Sniff
{

    use BasicsTrait;

    const NAMESPACE_SPRYKER = 'Spryker';

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getNamespace(File $phpCsFile)
    {
        $className = $this->getClassName($phpCsFile);
        $classNameParts = explode('\\', $className);

        return $classNameParts[0];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getBundle(File $phpCsFile)
    {
        $className = $this->getClassName($phpCsFile);
        $classNameParts = explode('\\', $className);

        if (count($classNameParts) < 3) {
            return '';
        }

        return $classNameParts[2];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getLayer(File $phpCsFile)
    {
        $className = $this->getClassName($phpCsFile);
        $classNameParts = explode('\\', $className);

        if (count($classNameParts) < 4) {
            return '';
        }

        return $classNameParts[3];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getClassName(File $phpCsFile)
    {
        $fileName = $phpCsFile->getFilename();
        $fileNameParts = explode(DIRECTORY_SEPARATOR, $fileName);
        $directoryPosition = array_search('src', array_values($fileNameParts));
        if (!$directoryPosition) {
            $directoryPosition = array_search('tests', array_values($fileNameParts)) + 1;
        }
        $classNameParts = array_slice($fileNameParts, $directoryPosition + 1);
        $className = implode('\\', $classNameParts);
        $className = str_replace('.php', '', $className);

        return $className;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param array $missingUses
     *
     * @return void
     */
    protected function addUseStatements(File $phpCsFile, $stackPointer, array $missingUses)
    {
        $useStatements = $this->parseUseStatements($phpCsFile, $stackPointer);
        foreach ($missingUses as $missingUse) {
            if (!in_array($missingUse, $useStatements)) {
                $this->addMissingUse($phpCsFile, $stackPointer, $missingUse);
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return array
     */
    protected function parseUseStatements(File $phpCsFile, $stackPointer)
    {
        $useStatements = [];
        $tokens = $phpCsFile->getTokens();
        if ($phpCsFile->findPrevious(T_USE, $stackPointer)) {
            $position = $phpCsFile->findPrevious(T_USE, $stackPointer);
            while ($position !== false) {
                $position = $phpCsFile->findPrevious(T_USE, $position);
                if ($position !== false) {
                    $end = $phpCsFile->findEndOfStatement($position);
                    if ($tokens[$position]['type'] === 'T_USE') {
                        $useTokens = array_slice($tokens, $position + 2, $end - $position - 2);
                        $useStatements[] = $this->parseUseParts($useTokens);
                    }
                }
                $position--;
            }
        }

        return $useStatements;
    }

    /**
     * @param array $useTokens
     *
     * @return string
     */
    protected function parseUseParts(array $useTokens)
    {
        $useClass = '';
        foreach ($useTokens as $useToken) {
            $useClass .= $useToken['content'];
        }

        return $useClass;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param string $missingUse
     *
     * @return void
     */
    protected function addMissingUse(File $phpCsFile, $stackPointer, $missingUse)
    {
        $previousUsePosition = $phpCsFile->findPrevious(T_USE, $stackPointer);
        if ($previousUsePosition !== false) {
            $endOfLastUse = $phpCsFile->findEndOfStatement($previousUsePosition);

            $phpCsFile->fixer->addNewline($endOfLastUse);
            $phpCsFile->fixer->addContent($endOfLastUse, 'use ' . $missingUse . ';');
        }
    }

    /**
     * Checks if the given token scope contains a single or multiple token codes/types.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param string|array $search
     * @param int $start
     * @param int $end
     * @param bool $skipNested
     *
     * @return bool
     */
    protected function contains(File $phpcsFile, $search, $start, $end, $skipNested = true)
    {
        $tokens = $phpcsFile->getTokens();

        for ($i = $start; $i <= $end; $i++) {
            if ($skipNested && $tokens[$i]['code'] === T_OPEN_PARENTHESIS) {
                $i = $tokens[$i]['parenthesis_closer'];
                continue;
            }
            if ($skipNested && $tokens[$i]['code'] === T_OPEN_SHORT_ARRAY) {
                $i = $tokens[$i]['bracket_closer'];
                continue;
            }
            if ($skipNested && $tokens[$i]['code'] === T_OPEN_CURLY_BRACKET) {
                $i = $tokens[$i]['bracket_closer'];
                continue;
            }

            if ($this->isGivenKind($search, $tokens[$i])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the given token scope requires brackets when used standalone.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $openingBraceIndex
     * @param int $closingBraceIndex
     *
     * @return bool
     */
    protected function needsBrackets(File $phpcsFile, $openingBraceIndex, $closingBraceIndex)
    {
        $tokens = $phpcsFile->getTokens();

        $whitelistedCodes = [
            T_LNUMBER,
            T_STRING,
            T_BOOL_CAST,
            T_STRING_CAST,
            T_INT_CAST,
            T_ARRAY_CAST,
            T_COMMENT,
            T_WHITESPACE,
            T_VARIABLE,
            T_DOUBLE_COLON,
            T_OBJECT_OPERATOR,
        ];

        for ($i = $openingBraceIndex + 1; $i < $closingBraceIndex; $i++) {
            if ($tokens[$i]['type'] === 'T_OPEN_PARENTHESIS') {
                $i = $tokens[$i]['parenthesis_closer'];
                continue;
            }
            if (in_array($tokens[$i]['code'], $whitelistedCodes)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return int|null Stackpointer value of docblock end tag, or null if cannot be found
     */
    protected function findRelatedDocBlock(File $phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();

        $line = $tokens[$stackPointer]['line'];
        $beginningOfLine = $stackPointer;
        while (!empty($tokens[$beginningOfLine - 1]) && $tokens[$beginningOfLine - 1]['line'] === $line) {
            $beginningOfLine--;
        }

        if (!empty($tokens[$beginningOfLine - 2]) && $tokens[$beginningOfLine - 2]['type'] === 'T_DOC_COMMENT_CLOSE_TAG') {
            return $beginningOfLine - 2;
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $index
     * @param int $count
     *
     * @return void
     */
    protected function outdent(File $phpcsFile, $index, $count = 1)
    {
        $tokens = $phpcsFile->getTokens();
        $char = $this->getIndentationCharacter($tokens[$index]['content'], true);

        $phpcsFile->fixer->replaceToken($index, $this->strReplaceOnce($char, '', $tokens[$index]['content']));
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $index
     * @param int $count
     *
     * @return void
     */
    protected function indent(File $phpcsFile, $index, $count = 1)
    {
        $tokens = $phpcsFile->getTokens();

        $phpcsFile->fixer->replaceToken($index, $this->strReplaceOnce("\t", "\t\t", $tokens[$index]['content']));
    }

    /**
     * @param string $search
     * @param string $replace
     * @param string $subject
     *
     * @return string
     */
    protected function strReplaceOnce($search, $replace, $subject)
    {
        $pos = strpos($subject, $search);
        if ($pos === false) {
            return $subject;
        }

        return substr($subject, 0, $pos) . $replace . substr($subject, $pos + strlen($search));
    }

    /**
     * @param string $content
     * @param bool $correctLength
     *
     * @return string
     */
    protected function getIndentationCharacter($content, $correctLength = false)
    {
        if (strpos($content, "\n")) {
            $parts = explode("\n", $content);
            array_shift($parts);
        } else {
            $parts = (array)$content;
        }

        $char = "\t";
        $countTabs = $countSpaces = 0;
        foreach ($parts as $part) {
            $countTabs += substr_count($part, $char);
            $countSpaces += (int)(substr_count($part, ' ') / 4);
        }

        if ($countSpaces > $countTabs) {
            $char = $correctLength ? '    ' : ' ';
        }

        return $char;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $prevIndex
     *
     * @return string
     */
    protected function getIndentationWhitespace(File $phpcsFile, $prevIndex)
    {
        $tokens = $phpcsFile->getTokens();

        $firstIndex = $this->getFirstTokenOfLine($tokens, $prevIndex);
        $whitespace = '';
        if ($tokens[$firstIndex]['type'] === 'T_WHITESPACE') {
            $whitespace = $tokens[$firstIndex]['content'];
        }

        return $whitespace;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $prevIndex
     *
     * @return int
     */
    protected function getIndentationColumn(File $phpcsFile, $prevIndex)
    {
        $tokens = $phpcsFile->getTokens();

        $firstIndex = $this->getFirstTokenOfLine($tokens, $prevIndex);

        $nextIndex = $phpcsFile->findNext(T_WHITESPACE, ($firstIndex + 1), null, true);
        if ($tokens[$nextIndex]['line'] !== $tokens[$prevIndex]['line']) {
            return 0;
        }

        return $tokens[$nextIndex]['column'] - 1;
    }

    /**
     * @param array $tokens
     * @param int $index
     *
     * @return int
     */
    protected function getFirstTokenOfLine(array $tokens, $index)
    {
        $line = $tokens[$index]['line'];

        $currentIndex = $index;
        while ($tokens[$currentIndex - 1]['line'] === $line) {
            $currentIndex--;
        }

        return $currentIndex;
    }

    /**
     * @param array $tokens
     * @param int $index
     *
     * @return int
     */
    protected function getLastTokenOfLine(array $tokens, $index)
    {
        $line = $tokens[$index]['line'];

        $currentIndex = $index;
        while ($tokens[$currentIndex + 1]['line'] === $line) {
            $currentIndex++;
        }

        return $currentIndex;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isMarkedAsDeprecated(File $phpCsFile, $tokens, $stackPointer)
    {
        $begin = $tokens[$stackPointer]['scope_opener'] + 1;
        $end = $tokens[$stackPointer]['scope_closer'] - 1;
        for ($i = $begin; $i <= $end; $i++) {
            $token = $tokens[$i];
            if ($token['code'] === T_CONSTANT_ENCAPSED_STRING) {
                if (strpos(strtolower($token['content']), 'deprecated') !== false) {
                    return true;
                }
            }
        }

        if ($this->isMarkedDeprecatedInDocBlock($phpCsFile, $tokens, $stackPointer)) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param array $tokens
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isMarkedDeprecatedInDocBlock(File $phpCsFile, $tokens, $stackPointer)
    {
        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);
        if (!$docBlockEndIndex) {
            return false;
        }
        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];
        for ($i = $docBlockStartIndex + 1;
             $i < $docBlockEndIndex;
             $i++
        ) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if (!in_array($tokens[$i]['content'], ['@deprecated'])) {
                continue;
            }
            return true;
        }

        return false;
    }

}
