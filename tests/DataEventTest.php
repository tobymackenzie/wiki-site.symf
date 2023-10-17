<?php
namespace TJM\WikiSite\Tests;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use TJM\Wiki\File;
use TJM\Wiki\Wiki;
use TJM\WikiSite\FormatConverter\HtmlToMarkdownConverter;
use TJM\WikiSite\FormatConverter\MarkdownToCleanMarkdownConverter;
use TJM\WikiSite\FormatConverter\MarkdownToHtmlConverter;
use TJM\WikiSite\Event\ViewDataEvent;
use TJM\WikiSite\WikiSite;

class DataEventTest extends TestCase{
	use TestTrait, TwigTestTrait;

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
				'eventDispatcher'=> new EventDispatcher(),
				'twig'=> $this->getTwig(),
			]
		);
	}
	public function testModifyContent(){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'path'=> '/foo.md',
			'content'=> '<span>hello</span> <i>world</i>',
		]));
		$wsite->getEventDispatcher()->addListener(ViewDataEvent::class, function(ViewDataEvent $event){
			$data = $event->getData();
			$data['content'] = 'prefix: ' . $data['content'] . ' suffix';
			$event->setData($data);
		});
		$response = $wsite->viewAction('/foo.txt');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("txt\nprefix: Foo\n==========\n\nhello *world* suffix\ntxtend\n", $response->getContent());
	}
}
