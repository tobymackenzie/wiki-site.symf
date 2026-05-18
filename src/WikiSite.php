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
use TJM\Wiki\Wiki;
use TJM\Wiki\File;
use TJM\Wiki\Plugin;
use TJM\WikiSite\Data\ViewActionData;
use TJM\WikiSite\FormatConverter\ConverterInterface;
use TJM\WikiSite\Event\ViewContentEvent;
use TJM\WikiSite\Event\ViewLoadContentEvent;
use TJM\WikiSite\Event\ViewDataEvent;
use TJM\WikiSite\Event\ViewNameEvent;
use TJM\WikiSite\Event\ViewStartEvent;
use Twig\Environment as Twig_Environment;

class WikiSite extends Plugin{
	const CONFIG_DIR = __DIR__ . '/../config';
	protected array $converters = [];
	protected string $homePage = '/index';
	protected ?MimeTypes $mimeTypes = null;
	protected string $name = 'TJM Wiki';
	protected ?RouterInterface $router = null;
	protected ?string $shellTemplate = '@TJMWikiSite/shell';
	protected ?Twig_Environment $twig = null;
	protected string $viewRoute = 'tjm_wiki';
	protected ?string $viewTemplate = '@TJMWikiSite/view';

	public function __construct(array $opts = []){
		if($opts && is_array($opts)){
			foreach($opts as $opt=> $value){
				$this->$opt = $value;
			}
		}
	}

	//-# primarily for testing
	public function getEventDispatcher(){
		return $this->wiki->getEventDispatcher();
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
		if($this->wiki->hasEventDispatcher()){
			$this->wiki->dispatch(new ViewStartEvent($adat));
			if($adat->getResponse()){
				return $adat->getResponse();
			}
		}
		if($adat->getCanonical()/* && $adat->getCanonical() !== $path*/){
			return new RedirectResponse($this->getRoute($adat->getCanonical()), 302);
		}
		if($adat->getExtension()){
			$adat->setCanonical($this->wiki->getCanonicalPath($adat->getPath()));
		}
		if(empty($adat->getCanonical())){
			$adat->setCanonical($this->wiki->getCanonicalPath($adat->getPagePath()) ?? $adat->getPagePath());
			if($adat->getExtension()){
				if($this->canConvertFile($adat->getFile() ?? $this->wiki->getPage($adat->getCanonical()), $adat->getExtension())){
					$adat->setCanonical($adat->getCanonical() . '.' . strtolower($adat->getExtension()));
				}else{
					$adat->setCanonical(null);
				}
			}
		}
		if($adat->getCanonical() && $adat->getCanonical() !== $adat->getPath() && $adat->getCanonical() !== $adat->getPagePath()){
			return new RedirectResponse($this->getRoute($adat->getCanonical()), 302);
		}
		if(!$adat->getFile()){
			if($this->wiki->hasPage($adat->getPagePath())){
				$adat->setFile($this->wiki->getPage($adat->getPagePath()));
			}elseif($this->wiki->hasFile($adat->getPath())){
				$adat->setFile($this->wiki->getFile($adat->getPath()));
			}
		}
		if($adat->getFile()){
			if(in_array($adat->getExtension(), [
				'htm',
				'html',
				'asp',
				'cgi',
				'js',
				'jsp',
				'php',
				'pl',
				'rb',
			]) || substr($adat->getPath(), -1) === '/'){
				return new RedirectResponse($this->getRoute($adat->getPagePath()), 302);
			//--force lowercase of extension if uppercase
			}elseif(
				$adat->getExtension() && $adat->getExtension() !== strtolower($adat->getExtension())
				&& (
					strtolower($adat->getExtension()) === strtolower($adat->getFile()->getExtension())
					|| $this->canConvertFile($adat->getFile(), $adat->getExtension())
				)
			){
				$adat->setPath(implode('.', explode('.', $adat->getPath(), -1)) . '.' . strtolower($adat->getExtension()));
				return new RedirectResponse($this->getRoute($adat->getPath()), 302);
			}
			if(empty($adat->getExtension())){
				$adat->setExtension('html');
			}
			if(!$adat->getTemplate()){
				$adat->setTemplate($this->getTemplateForExtension($this->viewTemplate, $adat->getExtension()));
			}
			if(empty($adat->getContent())){
				if($this->canConvertFile($adat->getFile(), $adat->getExtension())){
					$adat->setContent($this->convertFile($adat->getFile(), $adat->getExtension()));
				}else{
					throw new NotFoundHttpException();
				}
				$adat->setRenderContent(true);
				if($this->wiki->hasEventDispatcher()){
					$this->wiki->dispatch(new ViewLoadContentEvent($adat));
					if($adat->getResponse()){
						return $adat->getResponse();
					}
				}
			}else{
				$adat->setRenderContent(false);
			}
			$isHtmlish = $adat->isHtmlish();
			$isTextish = $adat->isTextish();
			if(empty($adat->getName()) && ($adat->getTemplate() || $isHtmlish || $isTextish)){
				if(
					($isHtmlish && preg_match(':<h1.*>(.*)</h1>:i', $adat->getContent(), $matches))
					|| ($isTextish && preg_match("/(.*)\n===[=]*\n/m", $adat->getContent(), $matches))
				){
					$adat->setName($matches[1]);
				}elseif($adat->getPagePath() === $this->homePage){
					$adat->setName($this->name);
				}elseif($adat->getFile() && !empty($adat->getFile()->getMeta('name'))){
					$adat->setName(ucwords($adat->getFile()->getMeta('name')));
				}else{
					//--use path as name
					$adat->setName($adat->getFile()->getPath());
					//---without extension
					$fileExtension = pathinfo($adat->getFile()->getPath(), PATHINFO_EXTENSION);
					if($fileExtension){
						$adat->setName(substr($adat->getName(), 0, -1 * (strlen($fileExtension) + 1)));
					}
					//---switch '/' to '-', reverse
					$adat->setName(implode(' - ', array_reverse(explode('/', $adat->getName()))));
					//---title case
					$adat->setName(ucwords($adat->getName()));
				}
			}
			if($this->wiki->hasEventDispatcher()){
				$this->wiki->dispatch(new ViewNameEvent($adat));
				if($adat->getResponse()){
					return $adat->getResponse();
				}
			}
			if($adat->getRenderContent() && ($adat->getTemplate() || $isHtmlish || $isTextish)){
				if($isHtmlish && strpos($adat->getContent(), '<h1') === false){
					$adat->setContent("<h1>{$adat->getName()}</h1>\n{$adat->getContent()}");
				}elseif($isTextish && strpos($adat->getContent(), "\n===") === false){
					$adat->setContent("{$adat->getName()}\n==========\n\n{$adat->getContent()}");
				}
			}
			if($adat->getTemplate()){
				if($adat->getFile() && $adat->getFile()->getMeta()){
					$adat->setUnsetData($adat->getFile()->getMeta());
				}
				$adat->setData([
					'format'=> $adat->getExtension(),
					'name'=> $adat->getName(),
					'canonical'=> $this->getRoute($adat->getCanonical(), null, RouterInterface::ABSOLUTE_URL),
					'content'=> $adat->getContent(),
					'pagePath'=> substr($adat->getPagePath(), 1),
					'shellTemplate'=> $this->getTemplateForExtension($this->shellTemplate, $adat->getExtension()),
					'wikiName'=> $this->name,
					'wikiRoute'=> $this->viewRoute,
				]);
				if($this->wiki->hasEventDispatcher()){
					$this->wiki->dispatch(new ViewDataEvent($adat));
					if($adat->getResponse()){
						return $adat->getResponse();
					}
				}
			}
			if($adat->getRenderContent()){
				if($adat->getTemplate()){
					$adat->setContent($this->twig->render($adat->getTemplate(), $adat->getData()));
				}elseif($isHtmlish){
					$adat->setContent("<!doctype html><title>{$adat->getName()} - {$this->name}</title>{$adat->getContent()}");
				}
			}
			if($this->wiki->hasEventDispatcher()){
				$this->wiki->dispatch(new ViewContentEvent($adat));
				if($adat->getResponse()){
					return $adat->getResponse();
				}
			}
			$adat->setResponse(new Response());
			$adat->getResponse()->setContent($adat->getContent());
			$adat->getResponse()->headers->set('Content-Type', $this->getMimeType($adat->getExtension()));
			return $adat->getResponse();
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
	public function convertFile(File $file, $to){
		if(!$file->getContent()){
			return '';
		}
		foreach($this->converters as $converter){
			if($converter->supports($file->getExtension(), $to)){
				return $converter->convert($file->getContent(), $file->getExtension(), $to);
			}
		}
		if($file->getExtension() === $to){
			return $file->getContent();
		}
		throw new Exception("No converter found to convert from {$file->getExtension()} to {$to}");
	}
	public function canConvertFile(File $file, $to){
		foreach($this->converters as $converter){
			if($converter->supports($file->getExtension(), $to)){
				return true;
			}
		}
		return $file->getExtension() === $to;
	}

	/*=====
	==routing
	=====*/
	public function getPagePaths(?string $path = null, ?string $find = null, $grep = null, $sort = Wiki::SORT_ASC | Wiki::SORT_ALPHA){
		$paths = $this->wiki->getPagePaths($path, $find, $grep, $sort);
		$index = array_search($this->homePage, $paths);
		if($index !== false){
			$paths[$index] = '/';
		}
		return $paths;
	}
	public function getRoute($name, $opts = null, $abs = UrlGeneratorInterface::ABSOLUTE_PATH){
		if(!$opts){
			$opts = ['path'=> $name];
			$name = $this->viewRoute;
		}
		if($this->router){
			//--prevent leading double slash that happens in some cases
			if(substr($opts['path'], 0, 1) === '/'){
				$opts['path'] = substr($opts['path'], 1);
			}
			$path = $this->router->generate($name, $opts, $abs);
			return $path;
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
