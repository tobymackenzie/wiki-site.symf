<?php
namespace TJM\WikiSite\Tests;
use PHPUnit\Framework\TestCase;
use TJM\WikiSite\FormatConverter\MarkdownToCleanMarkdownConverter;

class CleanMarkdownTest extends TestCase{
	use TestTrait;

	protected function getConverter(){
		return new MarkdownToCleanMarkdownConverter();
	}

	public function testCleanMarkdown(){
		$this->assertEquals(file_get_contents(__DIR__ . '/resources/clean.md'), (string) $this->getConverter()->convert(file_get_contents(__DIR__ . '/resources/markdown.md')));
	}
	public function testCleanMessyMarkdown(){
		$this->assertEquals(file_get_contents(__DIR__ . '/resources/messy-cleaned.md'), (string) $this->getConverter()->convert(file_get_contents(__DIR__ . '/resources/messy.md')));
	}
}
