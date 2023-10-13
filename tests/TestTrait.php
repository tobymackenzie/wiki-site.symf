<?php
namespace TJM\WikiSite\Tests;

trait TestTrait{
	const WIKI_DIR = __DIR__ . '/tmp';

	static public function setUpBeforeClass(): void{
		mkdir(self::WIKI_DIR);
	}
	protected function tearDown(): void{
		shell_exec("rm -rf " . self::WIKI_DIR . "/.git && rm -rf " . self::WIKI_DIR . "/*");
		parent::tearDown();
	}
	static public function tearDownAfterClass(): void{
		rmdir(self::WIKI_DIR);
	}
}
