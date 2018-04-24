<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Sniffs\Factory;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Spryker Factory classes should use create() to create classes and get()
 * for everything else.
 */
class CreateVsGetMethodsSniff extends AbstractSprykerSniff
{
    /**
     * @inheritdoc
     */
    public function register()
    {
        return [
            T_FUNCTION,
        ];
    }

    /**
     * @inheritdoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();

        if (!$this->isFactory($phpCsFile)) {
            return;
        }

        $markedAsDeprecated = $this->isMarkedAsDeprecated($phpCsFile, $tokens, $stackPointer);
        if ($markedAsDeprecated) {
            return;
        }

        $methodName = $this->getMethodName($phpCsFile, $stackPointer);
        $requiresCreatePrefix = $this->containsNew($tokens, $stackPointer) || $this->containsCreateMethod($tokens, $stackPointer);

        $startsWithCreate = preg_match('/create[A-Z]/', $methodName);
        $startsWithGet = preg_match('/get[A-Z]/', $methodName);

        if (!$startsWithCreate && !$startsWithGet) {
            return;
        }

        $classMethod = $this->getClassMethod($phpCsFile, $stackPointer);

        if ($startsWithCreate && !$requiresCreatePrefix) {
            $phpCsFile->addError($classMethod . ' is called create...(), should be get...()', $stackPointer, 'CreateVsGet');
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function getMethodName(File $phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();
        $methodNamePosition = $phpCsFile->findNext(T_STRING, $stackPointer);
        $methodName = $tokens[$methodNamePosition]['content'];

        return $methodName;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isFactory(File $phpCsFile)
    {
        $className = $this->getClassName($phpCsFile);

        $hasFactorySuffix = (substr($className, -7) === 'Factory');
        if (!$hasFactorySuffix) {
            return false;
        }

        return (substr($className, -15, -7) === 'Business' || substr($className, -20, -7) === 'Communication');
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
        $sourceDirectoryPosition = array_search('src', array_values($fileNameParts));
        $classNameParts = array_slice($fileNameParts, $sourceDirectoryPosition + 1);
        $className = implode('\\', $classNameParts);
        $className = str_replace('.php', '', $className);

        return $className;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function getClassMethod(File $phpCsFile, $stackPointer)
    {
        $className = $this->getClassName($phpCsFile);
        $methodName = $this->getMethodName($phpCsFile, $stackPointer);

        $classMethod = $className . '::' . $methodName;

        return $classMethod;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param string $newMethodName
     *
     * @return void
     */
    protected function correctMethodName(File $phpCsFile, $stackPointer, $newMethodName)
    {
        $phpCsFile->fixer->beginChangeset();
        $phpCsFile->fixer->replaceToken($stackPointer, $newMethodName);
        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param array $tokens
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function containsNew($tokens, $stackPointer)
    {
        $begin = $tokens[$stackPointer]['scope_opener'] + 1;
        $end = $tokens[$stackPointer]['scope_closer'] - 1;

        for ($i = $begin; $i <= $end; $i++) {
            $token = $tokens[$i];

            if ($token['code'] === T_NEW) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $tokens
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function containsCreateMethod($tokens, $stackPointer)
    {
        $begin = $tokens[$stackPointer]['scope_opener'] + 1;
        $end = $tokens[$stackPointer]['scope_closer'] - 1;

        for ($i = $begin; $i <= $end; $i++) {
            $token = $tokens[$i];

            if ($token['code'] === T_OBJECT_OPERATOR && $tokens[$i + 1]['code'] === T_STRING) {
                if (strpos($tokens[$i + 1]['content'], 'create') === 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
