<?php

namespace Spryker_CodeSniffer\Sniffs\Facade;

use PHP_CodeSniffer\Files\File;
use Spryker_CodeSniffer\Sniffs\AbstractSniffs\AbstractMethodAnnotationSniff;

abstract class AbstractFacadeMethodAnnotationSniff extends AbstractMethodAnnotationSniff
{

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isFacade(File $phpCsFile)
    {
        $className = $this->getClassName($phpCsFile);
        $bundleName = $this->getBundle($phpCsFile);

        $facadeName = $bundleName . 'Facade';
        $stringLength = strlen($facadeName);
        $relevantClassNamePart = substr($className, -$stringLength);

        return ($relevantClassNamePart === $facadeName);
    }

}
