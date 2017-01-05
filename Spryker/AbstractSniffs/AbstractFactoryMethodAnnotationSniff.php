<?php

namespace Spryker\AbstractSniffs;

use PHP_CodeSniffer\Files\File;

abstract class AbstractFactoryMethodAnnotationSniff extends AbstractMethodAnnotationSniff
{

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isFactory(File $phpCsFile)
    {
        $className = $this->getClassName($phpCsFile);

        return (
            substr($className, -15) === 'BusinessFactory'
            || substr($className, -20) === 'CommunicationFactory'
            || substr($className, -18) === 'PersistenceFactory'
        );
    }

}
