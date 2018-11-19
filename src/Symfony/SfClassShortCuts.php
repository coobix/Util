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
            throw new \Exception("Class doesn't have 'Entity' in namespace");
        }
        $nameSpace = array_slice($nameSpace, $key +1);
        return self::getBundleName($class) . ':' . join('\\', $nameSpace);
    }

    /**
     * Return the Entity Bundle Name
     * @param  Object $class The class instance
     * @return string        The Entity Bundle Name
     */
    static public function getBundleName($class)
    {
    	$rC = new \ReflectionClass($class);
        $nameSpace = explode("\\", $rC->getNamespaceName());
        return (count($nameSpace) >= 3) ? $nameSpace[0] . $nameSpace[1] : $nameSpace[0];
    }

    /**
     * Return the Entity Controller Fully Name
     * @param  Object $class The class instance
     * @return string        The Controller Fully Name
     */
    static public function getControllerFullyName($class)
    {
        $rC = new \ReflectionClass($class);
        $nameSpace = explode("\\", $rC->getNamespaceName());
        //AppBundle\Entity
        if (count($nameSpace) == 2) {
            return $nameSpace[0] . '\\Controller\\' . $rC->getName() . 'Controller';
        }
        //Vendor\AppBundle\Entity
        return $nameSpace[0] . '\\' . $nameSpace[1] . '\\Controller\\' . $rC->getName() . 'Controller';
    }
}