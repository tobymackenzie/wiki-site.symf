<?php
namespace TJM\WikiSite;
use BadMethodCallException;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use TJM\Wiki\Wiki;
use TJM\Wiki\File;
use TJM\WikiSite\FormatConverter\ConverterInterface;
use TJM\WikiSite\Event\ViewDataEvent;
use Twig\Environment as Twig_Environment;

class WikiSite{
	const CONFIG_DIR = __DIR__ . '/../config';
	protected array $converters = [];
	protected ?EventDispatcherInterface $eventDispatcher = null;
	protected string $homePage = '/index';
	protected ?MimeTypes $mimeTypes = null;
	protected string $name = 'TJM Wiki';
	protected ?RouterInterface $router = null;
	protected ?string $shellTemplate = '@TJMWikiSite/shell';
	protected ?Twig_Environment $twig = null;
	protected string $viewRoute = 'tjm_wiki';
	protected ?string $viewTemplate = '@TJMWikiSite/view';
	protected Wiki $wiki;

	public function __construct(Wiki $wiki, array $opts = []){
		$this->wiki = $wiki;
		if($opts && is_array($opts)){
			foreach($opts as $opt=> $value){
				$this->$opt = $value;
			}
		}
	}

	//-# primarily for testing
	public function getEventDispatcher(){
		return $this->eventDispatcher;
	}

	public function getName(){
		return $this->name;
	}
	public function getWiki(){
		return $this->wiki;
	}

	/*=====
	==controller
	=====*/
	public function viewAction($path){
		if(substr($path, 0, 1) !== '/'){
			$path = '/' . $path;
		}
		if($path === $this->homePage){
			return new RedirectResponse($this->getRoute($this->viewRoute, ['path'=> '/']), 302);
		}
		if($path === '/'){
			$path = $this->homePage;
		}
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		$pagePath = $extension ? substr($path, 0, -1 * (strlen($extension) + 1)) : $path;
		if(substr($pagePath, -1) === '/'){
			$pagePath = substr($pagePath, 0, -1);
		}
		if($this->wiki->hasPage($pagePath)){
			$file = $this->wiki->getPage($pagePath);
		}else{
			if($this->wiki->hasFile($path)){
				$file = $this->wiki->getFile($path);
			}
		}
		if(isset($file)){
			if(in_array($extension, [
				'htm',
				'html',
				'asp',
				'cgi',
				'js',
				'jsp',
				'php',
				'pl',
				'rb',
			]) || substr($path, -1) === '/'){
				return new RedirectResponse($this->getRoute($this->viewRoute, ['path'=> $pagePath]), 302);
			//--force lowercase of extension if uppercase
			}elseif(
				$extension && $extension !== strtolower($extension)
				&& (
					strtolower($extension) === strtolower($file->getExtension())
					|| $this->canConvertFile($file, $extension)
				)
			){
				$path = explode('.', $path, -1);
				$path = implode('.', $path) . '.' . strtolower($extension);
				return new RedirectResponse($this->getRoute($this->viewRoute, ['path'=> $path]), 302);
			}
			$response = new Response();
			if(empty($extension)){
				$extension = 'html';
			}
			if($this->canConvertFile($file, $extension)){
				$content = $this->convertFile($file, $extension);
			}elseif($extension === $file->getExtension()){
				$content = $file->getContent();
			}else{
				throw new NotFoundHttpException();
			}
			$isHtmlish = $extension === 'html' || $extension === 'xhtml';
			$isTextish = $extension === 'md' || $extension === 'txt';
			$viewTemplate = $this->getTemplateForExtension($this->viewTemplate, $extension);
			if($viewTemplate || $isHtmlish || $isTextish){
				if(
					($isHtmlish && preg_match(':<h1.*>(.*)</h1>:i', $content, $matches))
					|| ($isTextish && preg_match("/(.*)\n===[=]*\n/m", $content, $matches))
				){
					$name = $matches[1];
				}elseif($pagePath === $this->homePage){
					$name = $this->name;
				}else{
					//--use path as name
					$name = $file->getPath();
					//---without extension
					$fileExtension = pathinfo($file->getPath(), PATHINFO_EXTENSION);
					if($fileExtension){
						$name = substr($name, 0, -1 * (strlen($fileExtension) + 1));
					}
					//---switch '/' to '-', reverse
					$name = implode(' - ', array_reverse(explode('/', $name)));
					//---title case
					$name = ucwords($name);
				}
				if($isHtmlish && strpos($content, '<h1') === false){
					$content = "<h1>{$name}</h1>\n{$content}";
				}elseif($isTextish && strpos($content, "\n===") === false){
					$content = "{$name}\n==========\n\n{$content}";
				}
			}
			if($viewTemplate){
				$data = [
					'format'=> $extension,
					'name'=> $name,
					'content'=> $content,
					'pagePath'=> substr($pagePath, 1),
					'shellTemplate'=> $this->getTemplateForExtension($this->shellTemplate, $extension),
					'wikiName'=> $this->name,
					'wikiRoute'=> $this->viewRoute,
				];
				if($this->getEventDispatcher()){
					$event = new ViewDataEvent($data, $path);
					$this->getEventDispatcher()->dispatch($event);
					$data = $event->getData();
				}
				$content = $this->twig->render($viewTemplate, $data);
			}elseif($isHtmlish){
				$content = "<!doctype html><title>{$name} - {$this->name}</title>{$content}";
			}
			$response->setContent($content);
			$response->headers->set('Content-Type', $this->getMimeType($extension));
			return $response;
		}
		if($extension){
			$canonical = $this->wiki->getCanonicalPath($path);
		}
		if(empty($canonical)){
			$canonical = $this->wiki->getCanonicalPath($pagePath);
			if($extension){
				if($this->canConvertFile($this->wiki->getPage($canonical), $extension)){
					$canonical = $canonical . '.' . strtolower($extension);
				}else{
					$canonical = null;
				}
			}
		}
		if($canonical){
			return new RedirectResponse($this->getRoute($this->viewRoute, ['path'=> $canonical]), 302);
		}
		throw new NotFoundHttpException();
	}
	public function handleException(ExceptionEvent $event){
		$exception = $event->getThrowable();
		if($exception instanceof HttpExceptionInterface){
			$status = $exception->getStatusCode();
			$response = new Response();
			$response->setStatusCode($status);
			$response->setContent(isset(Response::$statusTexts[$status]) ? Response::$statusTexts[$status] : 'Error');
			$response->headers->replace($exception->getHeaders());
			$event->setResponse($response);
		}
	}

	/*=====
	==converters
	=====*/
	public function addConverter(ConverterInterface $converter){
		$this->converters[] = $converter;
	}
	protected function convertFile(File $file, $to){
		foreach($this->converters as $converter){
			if($converter->supports($file->getExtension(), $to)){
				return $converter->convert($file->getContent(), $file->getExtension(), $to);
			}
		}
		throw new Exception("No converter found to convert from {$file->getExtension()} to {$to}");
	}
	protected function canConvertFile(File $file, $to){
		foreach($this->converters as $converter){
			if($converter->supports($file->getExtension(), $to)){
				return true;
			}
		}
		return false;
	}

	/*=====
	==routing
	=====*/
	public function getPagePaths(){
		$paths = $this->wiki->getPagePaths();
		$index = array_search($this->homePage, $paths);
		if($index !== false){
			$paths[$index] = '/';
		}
		return $paths;
	}
	//-! maybe we should just move actions to a regular controller so we don't need this
	protected function getRoute($name, $opts, $abs = UrlGeneratorInterface::ABSOLUTE_PATH){
		if($this->router){
			return str_replace('//', '/', $this->router->generate($name, $opts, $abs));
		}elseif($name === $this->viewRoute && isset($opts['path'])){
			return $opts['path'];
		}
	}

	/*=====
	==templating
	=====*/
	protected function getTemplateForExtension(?string $template, string $extension){
		if($template && $this->twig){
			$templateOpts = [];
			if(preg_match('/\.([\w]+)\.twig$/i', $template, $matches)){
				if($matches[1] !== $extension){
					$templateOpts[] = preg_replace('/\.' . $matches[1] . '\.twig$/i', ".{$extension}.twig", $template);
				}
				if(
					$matches[1] === $extension
					|| ($matches[1] === 'html' && $extension === 'xhtml')
				){
					$templateOpts[] = $template;
				}
			}else{
				$templateOpts[] = $template . '.' . $extension . '.twig';
				if($extension === 'xhtml'){
					$templateOpts[] = $template . '.html.twig';
				}
			}
			foreach($templateOpts as $template){
				if($this->twig->getLoader()->exists($template)){
					return $template;
				}
			}
		}
		return null;
	}

	/*=====
	==util
	=====*/
	protected function getMimeType($extension){
		if(empty($this->mimeTypes)){
			$this->mimeTypes = new MimeTypes();
		}
		$types = $this->mimeTypes->getMimeTypes($extension);
		return $types ? $types[0] : 'text/plain';
	}
}
