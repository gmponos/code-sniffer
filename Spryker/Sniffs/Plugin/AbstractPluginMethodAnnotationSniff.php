<?php

namespace Spryker_CodeSniffer\Sniffs\Plugin;

use PHP_CodeSniffer\Files\File;
use Spryker_CodeSniffer\Sniffs\AbstractSniffs\AbstractMethodAnnotationSniff;

abstract class AbstractPluginMethodAnnotationSniff extends AbstractMethodAnnotationSniff
{

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isPlugin(File $phpCsFile, $stackPointer)
    {
        if ($this->isFileInPluginDirectory($phpCsFile) && $this->extendsAbstractPlugin($phpCsFile, $stackPointer)) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return int
     */
    private function isFileInPluginDirectory(File $phpCsFile)
    {
        return preg_match('/Communication\/Plugin/', $phpCsFile->getFilename());
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    private function extendsAbstractPlugin(File $phpCsFile, $stackPointer)
    {
        $extendedClassName = $phpCsFile->findExtendedClassName($stackPointer);

        if ($extendedClassName === 'AbstractPlugin') {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return int
     */
    protected function getStackPointerOfClassBegin(File $phpCsFile, $stackPointer)
    {
        $abstractPosition = $phpCsFile->findPrevious(T_ABSTRACT, $stackPointer);
        if ($abstractPosition) {
            return $abstractPosition;
        }

        return $stackPointer;
    }

}
