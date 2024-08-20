<?php
namespace TJM\WikiSite\Tests;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use TJM\Wiki\File;
use TJM\Wiki\Wiki;
use TJM\WikiSite\FormatConverter\HtmlToMarkdownConverter;
use TJM\WikiSite\FormatConverter\MarkdownToCleanMarkdownConverter;
use TJM\WikiSite\FormatConverter\MarkdownToHtmlConverter;
use TJM\WikiSite\Event\ViewContentEvent;
use TJM\WikiSite\WikiSite;

class ContentEventTest extends TestCase{
	use TestTrait;

	protected function getWikiSite(){
		return new WikiSite(
			new Wiki([
				'path'=> self::$WIKI_DIR,
			]),
			[
				'converters'=> [
					new HtmlToMarkdownConverter(),
					new MarkdownToCleanMarkdownConverter(),
					new MarkdownToHtmlConverter(),
				],
				'eventDispatcher'=> new EventDispatcher(),
			]
		);
	}
	public function testModifyContent(){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'path'=> '/foo.md',
			'content'=> '<span>hello</span> <a href="{internal}/world">world</a>',
		]));
		$wsite->getEventDispatcher()->addListener(ViewContentEvent::class, function(ViewContentEvent $event){
			$content = $event->getContent();
			$content = preg_replace(':(href=["\'])\{internal\}:', '$1/subdir', $content);
			$event->setContent($content);
		});
		$response = $wsite->viewAction('/foo');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("<!doctype html><title>Foo - TJM Wiki</title><h1>Foo</h1>\n<p><span>hello</span> <a href=\"/subdir/world\">world</a></p>\n", $response->getContent());
	}
}
