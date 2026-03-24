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
use TJM\WikiSite\Data\ViewActionData;
use TJM\WikiSite\FormatConverter\ConverterInterface;
use TJM\WikiSite\Event\ViewContentEvent;
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
		$adat = new ViewActionData($path, $this->homePage);
		if($adat->canonical){
			return new RedirectResponse($this->getRoute($this->viewRoute, ['path'=> $adat->canonical]), 302);
		}
		if($adat->extension){
			$adat->setCanonical($this->wiki->getCanonicalPath($adat->path));
		}
		if(empty($adat->canonical)){
			$adat->setCanonical($this->wiki->getCanonicalPath($adat->pagePath));
			if($adat->extension){
				if($this->canConvertFile($this->wiki->getPage($adat->canonical), $adat->extension)){
					$adat->setCanonical($adat->canonical . '.' . strtolower($adat->extension));
				}else{
					$adat->setCanonical(null);
				}
			}
		}
		if($adat->canonical && $adat->canonical !== $adat->path && $adat->canonical !== $adat->pagePath){
			return new RedirectResponse($this->getRoute($this->viewRoute, ['path'=> $adat->canonical]), 302);
		}
		if($this->wiki->hasPage($adat->pagePath)){
			$adat->setFile($this->wiki->getPage($adat->pagePath));
		}else{
			if($this->wiki->hasFile($adat->path)){
				$adat->setFile($this->wiki->getFile($adat->path));
			}
		}
		if($adat->file){
			if(in_array($adat->extension, [
				'htm',
				'html',
				'asp',
				'cgi',
				'js',
				'jsp',
				'php',
				'pl',
				'rb',
			]) || substr($adat->path, -1) === '/'){
				return new RedirectResponse($this->getRoute($this->viewRoute, ['path'=> $adat->pagePath]), 302);
			//--force lowercase of extension if uppercase
			}elseif(
				$adat->extension && $adat->extension !== strtolower($adat->extension)
				&& (
					strtolower($adat->extension) === strtolower($adat->file->getExtension())
					|| $this->canConvertFile($adat->file, $adat->extension)
				)
			){
				$adat->setPath(explode('.', $adat->path, -1));
				$adat->setPath(implode('.', $adat->path) . '.' . strtolower($adat->extension));
				return new RedirectResponse($this->getRoute($this->viewRoute, ['path'=> $adat->path]), 302);
			}
			if(empty($adat->extension)){
				$adat->setExtension('html');
			}
			if($this->canConvertFile($adat->file, $adat->extension)){
				$adat->setContent($this->convertFile($adat->file, $adat->extension));
			}elseif($adat->extension === $adat->file->getExtension()){
				$adat->setContent($adat->file->getContent());
			}else{
				throw new NotFoundHttpException();
			}
			$isHtmlish = $adat->isHtmlish();
			$isTextish = $adat->isTextish();
			$adat->setTemplate($this->getTemplateForExtension($this->viewTemplate, $adat->extension));
			if($adat->template || $isHtmlish || $isTextish){
				if(
					($isHtmlish && preg_match(':<h1.*>(.*)</h1>:i', $adat->content, $matches))
					|| ($isTextish && preg_match("/(.*)\n===[=]*\n/m", $adat->content, $matches))
				){
					$adat->setName($matches[1]);
				}elseif($adat->pagePath === $this->homePage){
					$adat->setName($this->name);
				}else{
					//--use path as name
					$adat->setName($adat->file->getPath());
					//---without extension
					$fileExtension = pathinfo($adat->file->getPath(), PATHINFO_EXTENSION);
					if($fileExtension){
						$adat->setName(substr($adat->name, 0, -1 * (strlen($fileExtension) + 1)));
					}
					//---switch '/' to '-', reverse
					$adat->setName(implode(' - ', array_reverse(explode('/', $adat->name))));
					//---title case
					$adat->setName(ucwords($adat->name));
				}
				if($isHtmlish && strpos($adat->content, '<h1') === false){
					$adat->setContent("<h1>{$adat->name}</h1>\n{$adat->content}");
				}elseif($isTextish && strpos($adat->content, "\n===") === false){
					$adat->setContent("{$adat->name}\n==========\n\n{$adat->content}");
				}
			}
			if($adat->template){
				$adat->setData([
					'format'=> $adat->extension,
					'name'=> $adat->name,
					'content'=> $adat->content,
					'pagePath'=> substr($adat->pagePath, 1),
					'shellTemplate'=> $this->getTemplateForExtension($this->shellTemplate, $adat->extension),
					'wikiName'=> $this->name,
					'wikiRoute'=> $this->viewRoute,
				]);
				if($this->getEventDispatcher()){
					$this->getEventDispatcher()->dispatch(new ViewDataEvent($adat));
				}
				$adat->setContent($this->twig->render($adat->template, $adat->getData()));
			}elseif($isHtmlish){
				$adat->setContent("<!doctype html><title>{$adat->name} - {$this->name}</title>{$adat->content}");
			}
			if($this->getEventDispatcher()){
				$this->getEventDispatcher()->dispatch(new ViewContentEvent($adat));
			}
			$adat->setResponse(new Response());
			$adat->response->setContent($adat->content);
			$adat->response->headers->set('Content-Type', $this->getMimeType($adat->extension));
			return $adat->response;
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
