<?php
namespace TJM\WikiSite\Tests;
use PHPUnit\Framework\TestCase;
use TJM\WikiSite\FormatConverter\MarkdownToHtmlConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;

class MarkdownToHtmlConverterTest extends TestCase{
	use TestTrait;

	protected function getConverter(){
		$env = new Environment();
		$env->addExtension(new CommonMarkCoreExtension());
		$env->addExtension(new FrontMatterExtension());
		$converter = new MarkdownConverter($env);
		return new MarkdownToHtmlConverter($converter);
	}

	public function testSupports(){
		$this->assertTrue($this->getConverter()->supports('md', 'html'));
		$this->assertTrue($this->getConverter()->supports('md', 'xhtml'));
	}
	public function testNoSupports(){
		$this->assertFalse($this->getConverter()->supports('md', 'md'));
		$this->assertFalse($this->getConverter()->supports('md', 'txt'));
		$this->assertFalse($this->getConverter()->supports('html', 'md'));
		$this->assertFalse($this->getConverter()->supports('xhtml', 'md'));
	}
	public function testConvert(){
		$result = $this->getConverter()->convert(file_get_contents(__DIR__ . '/resources/markdown.md'));
		$this->assertInstanceOf(RenderedContentWithFrontMatter::class, $result);
		$this->assertEquals($result->getFrontMatter()['comment'], 'This is top matter');
	}
}
