<?php
namespace TJM\WikiSite\Tests;
use Twig\Environment;
use Twig\TwigFunction;
use Twig\Loader\FilesystemLoader;

trait TwigTestTrait{
	public function getTwig(){
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
		return $twig;
	}
}
