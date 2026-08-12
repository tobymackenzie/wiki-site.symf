<?php
namespace TJM\WikiSite\Tests;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TJM\Wiki\File;
use TJM\Wiki\Wiki;
use TJM\WikiSite\FormatConverter\HtmlToMarkdownConverter;
use TJM\WikiSite\FormatConverter\MarkdownToCleanMarkdownConverter;
use TJM\WikiSite\FormatConverter\MarkdownToHtmlConverter;
use TJM\WikiSite\WikiSite;

class WikiSiteTest extends TestCase{
	use TestTrait, TwigTestTrait;
	protected $mdTemplatePrefix = '';
	protected $txtTemplatePrefix = '';
	protected $txtTemplateSuffix = '';

	protected function getWikiSite(array $conf = [], ?string $wikiPath = null){
		$wiki = new Wiki([
			'path'=> $wikiPath ?? self::$WIKI_DIR,
		]);
		$site = new WikiSite(array_merge([
			'converters'=> [
				new HtmlToMarkdownConverter(),
				new MarkdownToCleanMarkdownConverter(),
				new MarkdownToHtmlConverter(),
			],
		], $conf));
		$wiki->addPlugin($site);
		return $site;
	}
	static public function getNotFoundViewData(){
		return [
			['/'],
			['/bar'],
			['/fo'],
			['/foobar'],
			['/foo.php7'],
			['/*'],
		];
	}
	public function testGetDomain(){
		$site = $this->getWikiSite();
		$this->assertEquals('localhost', $site->getDomain());
		$site = $this->getWikiSite(['domain'=> 'tobymackenzie.com']);
		$this->assertEquals('tobymackenzie.com', $site->getDomain());
	}
	/**
	 * @dataProvider getNotFoundViewData
	 */
	#[DataProvider('getNotFoundViewData')]
	public function testNotFoundViewAction($path){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$this->expectException(NotFoundHttpException::class);
		$wsite->viewAction($path);
	}
	public function testFoundViewAction(){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'path'=> '/foo.md',
			'content'=> 'hello world',
		]));
		$response = $wsite->viewAction('/foo');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertMatchesRegularExpression('/^<\!doctype html>/i', $response->getContent());
		$this->assertMatchesRegularExpression('/hello world/', $response->getContent());
	}
	public function testFoundMarkdownViewAction(){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'path'=> '/foo.md',
			'content'=> 'hello <i>world</i>. `<span>`, &c.',
		]));
		$response = $wsite->viewAction('/foo.md');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals($this->mdTemplatePrefix . "Foo\n==========\n\nhello *world*. `<span>`, &c.\n", $response->getContent());
	}
	public function testMarkdownWithFrontMatter(){
		$wiki = new Wiki([
			'path'=> __DIR__ . '/resources',
		]);
		$wsite = new WikiSite(
			[
				'converters'=> [
					new HtmlToMarkdownConverter(),
					new MarkdownToCleanMarkdownConverter(),
					new MarkdownToHtmlConverter(/*$converter*/),
				],
				'twig'=> $this->getTwig(),
				'viewTemplate'=> '@TJMWikiSite/meta',
			]
		);
		$wiki->addPlugin($wsite);
		$response = $wsite->viewAction('/meta.txt');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertStringContainsString('Comment: This is top matter', $response->getContent());
		$this->assertStringContainsString('Meta2: This is meta 2', $response->getContent());
	}
	public function testGetPagePaths(){
		$wiki = new Wiki([
			'path'=> __DIR__ . '/resources/www',
		]);
		$wsite = new WikiSite();
		$wiki->addPlugin($wsite);
		$paths = $wsite->getPagePaths();
		$expect = [
			'/',
			'/dir',
			'/dir/foo',
		];
		$this->assertSame(array_diff($expect, $paths), array_diff($paths, $expect));
	}
	public function testInsertHeadingViewAction(){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'path'=> '/foo.md',
			'content'=> 'hello <i>world</i>',
		]));
		$response = $wsite->viewAction('/foo');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertMatchesRegularExpression(":<h1>Foo</h1>:", $response->getContent());
		$response = $wsite->viewAction('/foo.md');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals($this->mdTemplatePrefix . "Foo\n==========\n\nhello *world*\n", $response->getContent());
	}
	public function testNoInsertHeadingViewAction(){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'path'=> '/foo.md',
			'content'=> "Bar\n=====\n\nhello <i>world</i>",
		]));
		$response = $wsite->viewAction('/foo');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertMatchesRegularExpression(":<h1>Bar</h1>:", $response->getContent());
		$response = $wsite->viewAction('/foo.md');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals($this->mdTemplatePrefix . "Bar\n=====\n\nhello *world*\n", $response->getContent());
	}
	public function testGetTitleFromHeadingForViewAction(){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'path'=> '/foo.md',
			'content'=> "Bar\n======\n\nhello <i>world</i>",
		]));
		$response = $wsite->viewAction('/foo');
		$this->assertMatchesRegularExpression(":<title>Bar - TJM Wiki</title>:", $response->getContent());
	}
	public function testFoundTxtViewAction(){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'path'=> '/foo.md',
			'content'=> '<span>hello</span> <i>world</i>, &c.',
		]));
		$response = $wsite->viewAction('/foo.txt');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals($this->txtTemplatePrefix . "Foo\n==========\n\nhello *world*, &c.\n" . $this->txtTemplateSuffix, $response->getContent());
	}
	public function testNoConverterFoundViewAction(){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$this->expectException(NotFoundHttpException::class);
		$wsite->viewAction('/foo.asdf');
	}
	public function testRedirectHome(){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/index',
		]));
		$response = $wsite->viewAction('/index');
		$this->assertEquals(302, $response->getStatusCode());
	}
	public function testRedirectHTMLExtension(){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$response = $wsite->viewAction('/foo.html');
		$this->assertEquals(302, $response->getStatusCode());
	}
	public function testRedirectScriptExtension(){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		foreach([
			'asp',
			'cgi',
			'js',
			'jsp',
			'php',
			'pl',
			'rb',
		] as $ext){
			$response = $wsite->viewAction('/foo.' . $ext);
			$this->assertEquals(302, $response->getStatusCode(), 'Should redirect for extension ' . $ext);
			$this->assertEquals('/foo', $response->getTargetUrl(), 'Should redirect to page base');
		}
	}
	public function testRedirectTrailingSlash(){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$response = $wsite->viewAction('/foo/');
		$this->assertEquals(302, $response->getStatusCode());
	}
	public function testRedirectWrongCase(){
		$wsite = $this->getWikiSite();
		$wsite->getWiki()->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/index.md',
		]));
		$wsite->getWiki()->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$wsite->getWiki()->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/bar/bar.md',
		]));
		foreach([
			'/index.XHTML'=> '/index.xhtml',
			'/INDEX.xhtml'=> '/index.xhtml',
			'/INDEX.md'=> '/index.md',
			'/indeX'=> '/index', //--should really be "/" but gets there with second redirect, so shrug for now
			'/Foo'=> '/foo',
			'/FOO'=> '/foo',
			'/FOO.md'=> '/foo.md',
			'/bar/Bar.md'=> '/bar/bar.md',
			'/bar/Bar'=> '/bar/bar',
			'/BAR/Bar.md'=> '/BAR/bar.md', //--would like folders to be normalized too
		] as $path=> $expect){
			$response = $wsite->viewAction($path);
			$this->assertEquals(302, $response->getStatusCode(), "Path should cause a redirect.");
			$this->assertEquals($expect, $response->getTargetUrl(), "{$path} should redirect to {$expect}.");
		}
	}

	//==aliases
	public function testAlias(){
		$site = $this->getWikiSite([], self::$FIXED_WIKI_DIR);
		foreach([
			'/*'=> '/42',
			'/*.md'=> '/42.md',
			'/blog/2004/03/31/waste-recycling-2'=> '/blog/2004/03/31/waste-recycling',
			'/home'=> '/',
			'/home.xhtml'=> '/index.xhtml',
			'/olddir'=> '/dir',
			'/olddir.txt'=> '/dir.txt',
		] as $path=> $expect){
			$response = $site->viewAction($path);
			$this->assertEquals(302, $response->getStatusCode(), "Path should cause a redirect.");
			$this->assertEquals($expect, $response->getTargetUrl(), "{$path} should redirect to {$expect}.");
		}
	}
	public function testNotAlias(){
		$site = $this->getWikiSite([], self::$FIXED_WIKI_DIR);
		foreach([
			'/**',
			'/*/*',
			'/olddirr',
		] as $path){
			$this->expectException(NotFoundHttpException::class);
			$response = $site->viewAction($path);
		}
	}

	//==helpers
	static public function getCanConvertData(){
		return [
			['md'],
			['html'],
		];
	}
	#[DataProvider('getCanConvertData')]
	public function testCanConvert($from){
		$wsite = $this->getWikiSite();
		$this->assertTrue($wsite->canConvertExtension($from, 'html'));
	}
	static public function getCantConvertData(){
		return [
			['mdd'],
			['htmll'],
			['php7'],
		];
	}
	#[DataProvider('getCantConvertData')]
	public function testCantConvert($from){
		$wsite = $this->getWikiSite();
		$this->assertFalse($wsite->canConvertExtension($from, 'html'));
	}
}
