<?php
namespace TJM\WikiSite\Tests;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use TJM\Wiki\File;
use TJM\WikiSite\Kernel;
use TJM\WikiSite\WikiSite;

class KernelTest extends WebTestCase{
	const WIKI_DIR = __DIR__ . '/tmp';

	static public function setUpBeforeClass(): void{
		mkdir(self::WIKI_DIR);
	}
	protected function tearDown(): void{
		shell_exec("rm -rf " . self::WIKI_DIR . "/.git && rm -rf " . self::WIKI_DIR . "/*");
		parent::tearDown();
	}
	static public function tearDownAfterClass(): void{
		rmdir(self::WIKI_DIR);
	}
	protected static function createKernel(array $options = []): KernelInterface{
		return new Kernel([
			'config'=> __DIR__ . '/resources/config.yml',
			'debug'=> $options['debug'] ?? false,
			'environment'=> $options['environment'] ?? 'test',
		]);
	}

	public function testNotFoundViewFile(){
		$client = static::createClient();

		//--hide error, -@ <https://github.com/symfony/symfony/issues/28023#issuecomment-406850193>
		$client->catchExceptions(false);
		$this->expectException(NotFoundHttpException::class);

		static::getContainer()->get(WikiSite::class)->getWiki()->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$client->request('GET', '/bar');
		$this->assertResponseStatusCodeSame(404);
	}
	public function testFoundViewFile(){
		$client = static::createClient();
		static::getContainer()->get(WikiSite::class)->getWiki()->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$client->request('GET', '/foo');
		$this->assertResponseIsSuccessful();
		$response = $client->getResponse();
		$this->assertMatchesRegularExpression('/^<\!doctype html>/i', $response->getContent());
		$this->assertMatchesRegularExpression('/hello world/', $response->getContent());
	}
	public function testHomePageName(){
		$client = static::createClient();
		static::getContainer()->get(WikiSite::class)->getWiki()->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/index.md',
		]));
		$client->request('GET', '/');
		$response = $client->getResponse();
		$this->assertMatchesRegularExpression('/<title>TJM Wiki<\/title>/', $response->getContent());
		$this->assertMatchesRegularExpression('/<h1>TJM Wiki<\/h1>/', $response->getContent());
	}
	public function testInternalPageName(){
		$client = static::createClient();
		static::getContainer()->get(WikiSite::class)->getWiki()->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo/bar.md',
		]));
		$client->request('GET', '/foo/bar');
		$response = $client->getResponse();
		$this->assertMatchesRegularExpression('/<title>Bar - Foo - TJM Wiki<\/title>/', $response->getContent());
		$this->assertMatchesRegularExpression('/<h1>Bar - Foo<\/h1>/', $response->getContent());
	}
	public function testRedirectHome(){
		$client = static::createClient();
		static::getContainer()->get(WikiSite::class)->getWiki()->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/index',
		]));
		$client->followRedirects(false);
		$client->request('GET', '/index');
		$this->assertResponseRedirects('/');
	}
	public function testRedirectHTMLExtension(){
		$client = static::createClient();
		static::getContainer()->get(WikiSite::class)->getWiki()->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$client->followRedirects(false);
		$client->request('GET', '/foo.html');
		$this->assertResponseRedirects('/foo');
	}
	public function testRedirectTrailingSlash(){
		$client = static::createClient();
		static::getContainer()->get(WikiSite::class)->getWiki()->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$client->followRedirects(false);
		$client->request('GET', '/foo/');
		$this->assertResponseRedirects('/foo');
	}
}
