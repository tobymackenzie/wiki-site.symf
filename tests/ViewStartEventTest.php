<?php
namespace TJM\WikiSite\Tests;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use TJM\Wiki\File;
use TJM\Wiki\Wiki;
use TJM\WikiSite\FormatConverter\HtmlToMarkdownConverter;
use TJM\WikiSite\FormatConverter\MarkdownToCleanMarkdownConverter;
use TJM\WikiSite\FormatConverter\MarkdownToHtmlConverter;
use TJM\WikiSite\Event\ViewStartEvent;
use TJM\WikiSite\WikiSite;

class ViewStartEventTest extends TestCase{
	use TestTrait, TwigTestTrait;

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
				'twig'=> $this->getTwig(),
			]
		);
	}
	public function testModifyCanonical(){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'path'=> '/foo.md',
			'content'=> '<span>hello</span> <i>world</i>',
		]));
		$wsite->getEventDispatcher()->addListener(ViewStartEvent::class, function(ViewStartEvent $event){
			$event->setCanonical('/newfoo.txt');
		});
		$response = $wsite->viewAction('/foo.txt');
		$this->assertEquals(302, $response->getStatusCode());
		$this->assertEquals('/newfoo.txt', $response->getTargetUrl());
	}
	public function testModifyPath(){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'path'=> '/foo.md',
			'content'=> '<span>hello</span> <i>world</i>',
		]));
		$wsite->getEventDispatcher()->addListener(ViewStartEvent::class, function(ViewStartEvent $event){
			$event->setPath('/foo.txt');
			$event->setPagePath('/foo');
		});
		$response = $wsite->viewAction('/nonexistentfoo.txt');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("txt\nFoo\n==========\n\nhello *world*\n\ntxtend\n", $response->getContent());
	}
	public function testModifyTemplate(){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'path'=> '/foo.md',
			'content'=> '<span>hello</span> <i>world</i>',
		]));
		$wsite->getEventDispatcher()->addListener(ViewStartEvent::class, function(ViewStartEvent $event){
			$event->setTemplate('@TJMWikiSite/alt.txt.twig');
		});
		$response = $wsite->viewAction('/foo.txt');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("altxt\nFoo\n==========\n\nhello *world*\n\naltxtend\n", $response->getContent());
	}
}
