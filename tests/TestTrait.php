<?php
namespace TJM\WikiSite\Tests;
use Symfony\Component\ErrorHandler\ErrorHandler;

trait TestTrait{
	const WIKI_DIR = __DIR__ . '/tmp';

	static public function setUpBeforeClass(): void{
		mkdir(self::WIKI_DIR);
		//--hack, for Symfony's exception handler in PHPUnit 11+ see <https://github.com/symfony/symfony/issues/53812#issuecomment-1958859357>
		ErrorHandler::register(null, false);
	}
	protected function tearDown(): void{
		shell_exec("rm -rf " . self::WIKI_DIR . "/.git && rm -rf " . self::WIKI_DIR . "/*");
		parent::tearDown();
	}
	static public function tearDownAfterClass(): void{
		rmdir(self::WIKI_DIR);
	}
}
