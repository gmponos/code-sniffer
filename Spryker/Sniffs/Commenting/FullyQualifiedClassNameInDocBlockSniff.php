<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * All doc blocks must use FQCN for class names.
 */
class FullyQualifiedClassNameInDocBlockSniff implements Sniff
{
    /**
     * @var array
     */
    public static $whitelistedTypes = [
        'string', 'int', 'integer', 'float', 'bool', 'boolean', 'resource', 'null', 'void', 'callable',
        'array', 'mixed', 'object', 'false', 'true', 'self', 'static', '$this',
    ];

    /**
     * @inheritdoc
     */
    public function register()
    {
        return [
            T_CLASS,
            T_INTERFACE,
            T_TRAIT,
            T_FUNCTION,
            T_VARIABLE,
            T_COMMENT,
        ];
    }

    /**
     * @inheritdoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);

        if (!$docBlockEndIndex) {
            return;
        }

        $tokens = $phpCsFile->getTokens();

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if (!in_array($tokens[$i]['content'], ['@return', '@param', '@throws', '@var', '@method', '@property'])) {
                continue;
            }

            $classNameIndex = $i + 2;

            if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
                continue;
            }

            $content = $tokens[$classNameIndex]['content'];

            $appendix = '';
            $spaceIndex = strpos($content, ' ');
            if ($spaceIndex) {
                $appendix = substr($content, $spaceIndex);
                $content = substr($content, 0, $spaceIndex);
            }

            if (!$content) {
                continue;
            }

            $classNames = explode('|', $content);
            $this->fixClassNames($phpCsFile, $classNameIndex, $classNames, $appendix);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $classNameIndex
     * @param array $classNames
     * @param string $appendix
     *
     * @return void
     */
    protected function fixClassNames(File $phpCsFile, int $classNameIndex, array $classNames, string $appendix): void
    {
        $classNameMap = $this->generateClassNameMap($phpCsFile, $classNameIndex, $classNames);
        if (!$classNameMap) {
            return;
        }

        $message = [];
        foreach ($classNameMap as $className => $useStatement) {
            $message[] = $className . ' => ' . $useStatement;
        }

        $fix = $phpCsFile->addFixableError(implode(', ', $message), $classNameIndex, 'FQCN');
        if ($fix) {
            $newContent = implode('|', $classNames);

            $phpCsFile->fixer->replaceToken($classNameIndex, $newContent . $appendix);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $classNameIndex
     * @param array $classNames
     *
     * @return array
     */
    protected function generateClassNameMap(File $phpCsFile, int $classNameIndex, array &$classNames): array
    {
        $result = [];

        foreach ($classNames as $key => $className) {
            if (strpos($className, '\\') !== false) {
                continue;
            }
            $arrayOfObject = false;
            if (substr($className, -2) === '[]') {
                $arrayOfObject = true;
                $className = substr($className, 0, -2);
            }
            if (in_array($className, static::$whitelistedTypes)) {
                continue;
            }
            $useStatement = $this->findUseStatementForClassName($phpCsFile, $className);
            if (!$useStatement) {
                $message = 'Invalid typehint `%s`';
                if (substr($className, 0, 1) === '$') {
                    $message = 'The typehint seems to be missing for `%s`';
                }
                $phpCsFile->addError(sprintf($message, $className), $classNameIndex, 'ClassNameInvalid');
                continue;
            }
            $classNames[$key] = $useStatement . ($arrayOfObject ? '[]' : '');
            $result[$className . ($arrayOfObject ? '[]' : '')] = $classNames[$key];
        }

        return $result;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param string $className
     *
     * @return string|null
     */
    protected function findUseStatementForClassName(File $phpCsFile, string $className): ?string
    {
        $useStatements = $this->parseUseStatements($phpCsFile);
        if (!isset($useStatements[$className])) {
            $useStatement = $this->findInSameNameSpace($phpCsFile, $className);
            if ($useStatement) {
                return $useStatement;
            }

            return null;
        }

        return $useStatements[$className];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param string $className
     *
     * @return string|null
     */
    protected function findInSameNameSpace(File $phpCsFile, string $className): ?string
    {
        $currentNameSpace = $this->getNamespace($phpCsFile);
        if (!$currentNameSpace) {
            return null;
        }

        $file = $phpCsFile->getFilename();
        $dir = dirname($file) . DIRECTORY_SEPARATOR;
        if (!file_exists($dir . $className . '.php')) {
            return null;
        }

        return '\\' . $currentNameSpace . '\\' . $className;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getNamespace(File $phpCsFile): string
    {
        $tokens = $phpCsFile->getTokens();

        $namespaceStart = null;
        foreach ($tokens as $id => $token) {
            if ($token['code'] !== T_NAMESPACE) {
                continue;
            }

            $namespaceStart = $id + 1;
            break;
        }
        if (!$namespaceStart) {
            return '';
        }

        $namespaceEnd = $phpCsFile->findNext(
            [
                T_NS_SEPARATOR,
                T_STRING,
                T_WHITESPACE,
            ],
            $namespaceStart,
            null,
            true
        );

        $namespace = trim($phpCsFile->getTokensAsString(($namespaceStart), ($namespaceEnd - $namespaceStart)));

        return $namespace;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return int|null Stackpointer value of docblock end tag, or null if cannot be found
     */
    protected function findRelatedDocBlock(File $phpCsFile, int $stackPointer): ?int
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
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return array
     */
    protected function parseUseStatements(File $phpCsFile): array
    {
        $useStatements = [];
        $tokens = $phpCsFile->getTokens();

        foreach ($tokens as $id => $token) {
            if ($token['type'] !== 'T_USE') {
                continue;
            }

            $endIndex = $phpCsFile->findEndOfStatement($id);
            $useStatement = '';
            for ($i = $id + 2; $i < $endIndex; $i++) {
                $useStatement .= $tokens[$i]['content'];
            }

            $useStatement = trim($useStatement);

            if (strpos($useStatement, ' as ') !== false) {
                [$useStatement, $className] = explode(' as ', $useStatement);
            } else {
                $className = $useStatement;
                if (strpos($useStatement, '\\') !== false) {
                    $lastSeparator = strrpos($useStatement, '\\');
                    $className = substr($useStatement, $lastSeparator + 1);
                }
            }

            $useStatement = '\\' . ltrim($useStatement, '\\');

            $useStatements[$className] = $useStatement;
        }

        return $useStatements;
    }
}
