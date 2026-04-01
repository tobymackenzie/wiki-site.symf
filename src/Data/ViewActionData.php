<?php
namespace TJM\WikiSite\Data;
use Symfony\Component\HttpFoundation\Response;
use TJM\Wiki\File;

class ViewActionData{
	protected ?string $canonical = null;
	protected ?string $content = null;
	protected array $data = [];
	protected ?string $extension = null;
	protected ?File $file = null;
	protected ?string $name = null;
	protected ?string $pagePath = null;
	protected ?string $path = null;
	protected string $rawPath;
	protected bool $renderContent = true;
	protected ?Response $response = null;
	protected ?string $template = null;

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
	public function getCanonical(){
		return $this->canonical;
	}
	public function setCanonical(?string $path){
		$this->canonical = $path;
	}
	public function getContent(){
		return $this->content;
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
	public function setUnsetData($a, $b = null){
		if(is_array($a)){
			foreach($a as $key=> $value){
				if(!isset($this->data[$key])){
					$this->data[$key] = $value;
				}
			}
		}elseif(!isset($this->data[$a])){
			$this->data[$a] = $b;
		}
	}
	public function getExtension(){
		return $this->extension;
	}
	public function setExtension(string $extension){
		$this->extension = $extension;
	}
	public function getFile(){
		return $this->file;
	}
	public function setFile(File $file){
		$this->file = $file;
	}
	public function getName(){
		return $this->name;
	}
	public function setName(string $name){
		$this->name = $name;
	}
	public function getPagePath(){
		return $this->pagePath;
	}
	public function setPagePath(string $path){
		$this->pagePath = $path;
	}
	public function getPath(){
		return $this->path;
	}
	public function setPath(string $path){
		$this->path = $path;
	}
	public function getRenderContent(){
		return $this->renderContent;
	}
	public function setRenderContent(bool $val){
		$this->renderContent = $val;
	}
	public function getResponse(){
		return $this->response;
	}
	public function setResponse(Response $response){
		$this->response = $response;
	}
	public function getTemplate(){
		return $this->template;
	}
	public function setTemplate(?string $template){
		$this->template = $template;
	}
}
