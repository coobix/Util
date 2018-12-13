<?php
/**
 * This file is part of the CoobixUtil package.
 *
 * (c) Coobix <https://github.com/coobix/util>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Coobix\Util\Tests\File;

use org\bovigo\vfs\vfsStream,
    org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Coobix\Util\File\File;

class FileTest extends WebTestCase
{
	
	/**
     * @var  vfsStreamDirectory
     */
    private $root;

     /**
     * set up test environmemt
     */
	public function setUp()
    {
    	$structure = [
		    'csv' => [
			   'input.csv' => 'Test'
		    ]
		];
        $this->root = vfsStream::setup('root', 444, $structure);
    	
    }
    public function testCreateDir() {

        File::createDir($this->root->url().'/newdir');
        $this->assertTrue($this->root->hasChild('newdir'));
    }
    public function testCantCreateDir() {

        File::createDir($this->root->url().'/newdir', 0000);
        $this->expectException(\Exception::class);
        File::createDir($this->root->url().'/newdir/errordir/');
    }
    public function testDeleteFile() {

    	$this->assertTrue(File::deleteFile($this->root->url().'/csv/input.csv'));
    }
    
    public function testCantDeleteFile() {
        
        $this->expectException(\Exception::class);
        File::deleteFile($this->root->url().'/examples.txt');
    }
    

}