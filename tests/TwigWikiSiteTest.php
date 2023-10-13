<?php
namespace TJM\WikiSite\Tests;
use TJM\Wiki\Wiki;
use TJM\WikiSite\FormatConverter\HtmlToMarkdownConverter;
use TJM\WikiSite\FormatConverter\MarkdownToCleanMarkdownConverter;
use TJM\WikiSite\FormatConverter\MarkdownToHtmlConverter;
use TJM\WikiSite\WikiSite;

class TwigWikiSiteTest extends WikiSiteTest{
	use TwigTestTrait;
	protected $mdTemplatePrefix = "test\n";
	protected $txtTemplatePrefix = "txt\n";
	protected $txtTemplateSuffix = "\ntxtend\n";
	protected function getWikiSite(){
		return new WikiSite(
			new Wiki([
				'path'=> self::WIKI_DIR,
			]),
			[
				'converters'=> [
					new HtmlToMarkdownConverter(),
					new MarkdownToCleanMarkdownConverter(),
					new MarkdownToHtmlConverter(),
				],
				'twig'=> $this->getTwig(),
			]
		);
	}
}
