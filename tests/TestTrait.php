<?php
namespace TJM\WikiSite\Tests;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\ErrorHandler\ErrorHandler;

trait TestTrait{
	static protected $WIKI_DIR = __DIR__ . '/tmp';

	static public function setUpBeforeClass(): void{
		mkdir(self::$WIKI_DIR);
		//--hack, for Symfony's exception handler in PHPUnit 11+ see <https://github.com/symfony/symfony/issues/53812#issuecomment-1958859357>
		if(is_subclass_of(static::class, WebTestCase::class)){
			ErrorHandler::register(null, false);
		}
	}
	protected function tearDown(): void{
		shell_exec("rm -rf " . self::$WIKI_DIR . "/.git && rm -rf " . self::$WIKI_DIR . "/*");
		parent::tearDown();
	}
	static public function tearDownAfterClass(): void{
		rmdir(self::$WIKI_DIR);
	}
}
