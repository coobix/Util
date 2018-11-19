<?php
include ('../../src/Symfony/SfClassShortCuts.php')
/**
 * This file is part of the CoobixUtil package.
 *
 * (c) Coobix <https://github.com/coobix/util>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//namespace Coobix\Util\Tests\Symfony;

//use PHPUnit\Framework\TestCase;
//use Coobix\Util\Symfony\SfClassShortCuts;
//use Coobix\Util\Tests\Symfony\DummyClass; 

class SfClassShortCutsTest extends TestCase
{
	
    public function testEntityShortcutName() {
        $obj = new DummyClass();
        
        $this->assertSame('Horse', SfClassShortCuts::getEntityShortcutName($obj));
    }
       

}