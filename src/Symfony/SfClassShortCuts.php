<?php

/**
 * This file is part of the CoobixUtil package.
 *
 * (c) Coobix <https://github.com/coobix/util>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Coobix\Util\Symfony;

/**
 * Translate class namespace to Symfony Naming Conventions
 * 
 * @author Nicolás Rizo <nicolas@coobix.com>
 */
abstract class SfClassShortCuts
{
    /**
     * Return the Entity Shortcut Name
     * @param  Object $class The class instance
     * @return string        The Entity Shortcut Name
     */
	static public function getEntityShortcutName($class)
    {
    	$rC = new \ReflectionClass($class);
        $nameSpace = explode("\\", $rC->getName());
        $key = array_search('Entity', $nameSpace);
        if ($key === null) {
            //hacer una exception
            echo 'EXCEPTION';
        }
        $nameSpace = array_slice($nameSpace, $key +1);
        return self::getBundleName($class) . ':' . join('\\', $nameSpace);
    }

    static public function getBundleName($class)
    {
    	$rC = new \ReflectionClass($class);
        $nameSpace = explode("\\", $rC->getNamespaceName());
        return (count($nameSpace) >= 3) ? $nameSpace[0] . $nameSpace[1] : $nameSpace[0];
    }

    static public function getControllerFullyName()
    {
        $nameSpace = explode("\\", $this->rC->getNamespaceName());
        //AppBundle\Entity
        if (count($nameSpace) == 2) {
            return $nameSpace[0] . '\\Admin\\Controller\\' . $this->getClassName() . 'AdminController';
        }
        //Vendor\AppBundle\Entity
        return $nameSpace[0] . '\\' . $nameSpace[1] . '\\Admin\\Controller\\' . $this->getClassName() . 'AdminController';
    }
}