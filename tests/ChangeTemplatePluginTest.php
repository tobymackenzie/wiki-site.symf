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
		$wiki = new Wiki([
			'eventDispatcher'=> new EventDispatcher(),
			'path'=> __DIR__ . '/resources',
		]);
		$site = new WikiSite([
			'converters'=> [
				// new HtmlToMarkdownConverter(),
				new MarkdownToCleanMarkdownConverter(),
				// new MarkdownToHtmlConverter(),
			],
			'twig'=> $this->getTwig(),
		]);
		$wiki->addPlugin($site);
		return $site;
	}
	public function testChangingTemplatePlugin(){
		$ws = $this->getWikiSite();
		$ws->getWiki()->addPlugin(new ChangeTemplatePlugin());
		$response = $ws->viewAction('/meta.txt');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("altxt\nMeta\n====\n\nHello world\n\naltxtend\n", $response->getContent());
	}
}
