<?php
namespace TJM\WikiSite\Data;
use Symfony\Component\HttpFoundation\Response;
use TJM\Wiki\File;

class ViewActionData{
	public ?string $canonical = null;
	public ?string $content = null;
	protected array $data = [];
	public ?string $extension = null;
	public ?File $file = null;
	public ?string $name = null;
	public ?string $pagePath = null;
	public ?string $path = null;
	public string $rawPath;
	public ?Response $response = null;
	public ?string $template = null;

	public function __construct(string $path, string $homePage){
		$this->rawPath = $path;
		if(substr($path, 0, 1) !== '/'){
			$path = '/' . $path;
		}
		if($path !== $homePage){
			if($path === '/'){
				$path = $homePage;
			}
			$this->setPath($path);
			$this->extension = pathinfo($this->path, PATHINFO_EXTENSION);
			$this->pagePath = $this->extension ? substr($this->path, 0, -1 * (strlen($this->extension) + 1)) : $this->path;
			if(substr($this->pagePath, -1) === '/'){
				$this->pagePath = substr($this->pagePath, 0, -1);
			}
		}else{
			//--force redirect to proper home page path in view action
			$this->canonical = '/';
			$this->path = $path;
		}
	}
	public function isHtmlish(){
		return $this->extension === 'html' || $this->extension === 'xhtml';
	}
	public function isTextish(){
		return $this->extension === 'md' || $this->extension === 'txt';
	}
	public function setCanonical(?string $path){
		$this->canonical = $path;
	}
	public function setContent(string $content){
		$this->content = $content;
	}
	public function getData($key = null){
		if($key === null){
			return $this->data;
		}else{
			return $this->data[$key] ?? null;
		}
	}
	public function setData($a, $b = null){
		if(is_array($a)){
			if($b){
				$this->data = $a;
			}else{
				foreach($a as $key=> $value){
					$this->data[$key] = $value;
				}
			}
		}else{
			$this->data[$a] = $b;
		}
	}
	public function setExtension(string $extension){
		$this->extension = $extension;
	}
	public function setFile(File $file){
		$this->file = $file;
	}
	public function setName(string $name){
		$this->name = $name;
	}
	public function setPath(string $path){
		$this->path = $path;
	}
	public function setResponse(Response $response){
		$this->response = $response;
	}
	public function setTemplate(?string $template){
		$this->template = $template;
	}
}
