<?php
namespace TJM\WikiSite\Tests;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use TJM\Wiki\Wiki;
use TJM\WikiSite\FormatConverter\MarkdownToCleanMarkdownConverter;
use TJM\WikiSite\Tests\Src\ChangeTemplatePlugin;
use TJM\WikiSite\WikiSite;

class ChangeTemplatePluginTest extends TestCase{
	use TwigTestTrait;
	protected function getWikiSite(){
		return new WikiSite(
			new Wiki([
				'path'=> __DIR__ . '/resources',
			]),
			[
				'converters'=> [
					// new HtmlToMarkdownConverter(),
					new MarkdownToCleanMarkdownConverter(),
					// new MarkdownToHtmlConverter(),
				],
				'eventDispatcher'=> new EventDispatcher(),
				'twig'=> $this->getTwig(),
			]
		);
	}
	public function testChangingTemplatePlugin(){
		$ws = $this->getWikiSite();
		$ws->addPlugin(new ChangeTemplatePlugin());
		$response = $ws->viewAction('/meta.txt');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("altxt\nMeta\n====\n\nHello world\n\naltxtend\n", $response->getContent());
	}
}
