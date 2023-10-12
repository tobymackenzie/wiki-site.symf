<?php
namespace TJM\WikiSite\Tests;
use TJM\Wiki\Wiki;
use TJM\WikiSite\FormatConverter\HtmlToMarkdownConverter;
use TJM\WikiSite\FormatConverter\MarkdownToCleanMarkdownConverter;
use TJM\WikiSite\FormatConverter\MarkdownToHtmlConverter;
use TJM\WikiSite\WikiSite;
use Twig\Environment;
use Twig\TwigFunction;
use Twig\Loader\FilesystemLoader;

class TwigWikiSiteTest extends WikiSiteTest{
	protected $mdTemplatePrefix = "test\n";
	protected function getWikiSite(){
		$twigLoader = new FilesystemLoader(__DIR__);
		$twigLoader->addPath(__DIR__ . '/../templates', 'TJMWikiSite');
		$twigLoader->addPath(__DIR__ . '/resources', 'TJMWikiSite');
		$twig = new Environment($twigLoader);
		$twig->addFunction(new TwigFunction('asset', function($value){
			return $value;
		}));
		$twig->addFunction(new TwigFunction('path', function($value, $data){
			return $data['path'];
		}));
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
				'twig'=> $twig,
			]
		);
	}
}
