<?php
namespace TJM\WikiSite\Tests;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;
use TJM\Wiki\File;
use TJM\Wiki\Wiki;
// use TJM\WikiSite\FormatConverter\HtmlToMarkdownConverter;
// use TJM\WikiSite\FormatConverter\MarkdownToCleanMarkdownConverter;
use TJM\WikiSite\FormatConverter\MarkdownToHtmlConverter;
use TJM\WikiSite\Event\ViewContentEvent;
use TJM\WikiSite\Event\ViewDataEvent;
use TJM\WikiSite\Event\ViewStartEvent;
use TJM\WikiSite\WikiSite;

class ViewActionEventTest extends TestCase{
	use TwigTestTrait;

	protected function getWikiSite(){
		return new WikiSite(
			new Wiki([
				'path'=> __DIR__ . '/resources',
			]),
			[
				'converters'=> [
					// new HtmlToMarkdownConverter(),
					// new MarkdownToCleanMarkdownConverter(),
					new MarkdownToHtmlConverter(),
				],
				'eventDispatcher'=> new EventDispatcher(),
				'twig'=> $this->getTwig(),
			]
		);
	}
	public function testViewEventSetName(){
		$test = $this;
		foreach([
			ViewStartEvent::class,
		] as $event){
			$wsite = $this->getWikiSite();
			$wsite->getEventDispatcher()->addListener($event, function($e){
				$e->setName('This is a different name');
			});
			$response = $wsite->viewAction('/clean');
			$this->assertEquals(200, $response->getStatusCode());
			$this->assertStringContainsString("<title>This is a different name", $response->getContent(), "$event event should override name.");
		}
	}
	public function testViewEventSetResponse(){
		$test = $this;
		foreach([
			ViewStartEvent::class,
			ViewContentEvent::class,
			ViewDataEvent::class,
		] as $event){
			$wsite = $this->getWikiSite();
			$wsite->getEventDispatcher()->addListener($event, function($e) use($event, $test){
				$test->assertEquals($event, get_class($e));
				$response = new Response();
				$response->setContent('Hello world');
				$e->setResponse($response);
			});
			$response = $wsite->viewAction('/clean.md');
			$this->assertEquals(200, $response->getStatusCode());
			$this->assertEquals("Hello world", $response->getContent(), "$event event should override response content.");
		}
	}
}
