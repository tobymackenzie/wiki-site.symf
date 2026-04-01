<?php
namespace TJM\WikiSite\Event;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;
use TJM\WikiSite\Data\ViewActionData;

class ViewActionEvent extends Event{
	protected ViewActionData $data;
	public function __construct(ViewActionData $data){
		$this->data = $data;
	}
	public function isHtmlish(){
		return $this->data->isHtmlish();
	}
	public function isTextish(){
		return $this->data->isTextish();
	}
	public function setCanonical(string $value){
		$this->data->setCanonical($value);
	}
	public function getContent(){
		return $this->data->getContent();
	}
	public function setContent(string $value){
		$this->data->setContent($value);
	}
	public function setData($a, $b = null){
		$this->data->setData($a, $b);
	}
	public function setUnsetData($a, $b = null){
		$this->data->setUnsetData($a, $b);
	}
	public function getData($key = null){
		return $this->data->getData($key);
	}
	public function getExtension(){
		return $this->data->getExtension();
	}
	public function setExtension($extension){
		$this->data->setExtension($extension);
	}
	public function setExtra($a, $b = null){
		$this->data->setExtra($a, $b);
	}
	public function getExtra($key = null){
		return $this->data->getExtra($key);
	}
	public function getFile(){
		return $this->data->getFile();
	}
	public function setFile($file){
		$this->data->setFile($file);
	}
	public function setName($name){
		$this->data->setName($name);
	}
	public function setPagePath($path){
		$this->data->setPagePath($path);
	}
	public function getPath(){
		return $this->data->getPath();
	}
	public function setPath($path){
		$this->data->setPath($path);
	}
	public function getRenderContent(){
		return $this->data->getRenderContent();
	}
	public function setRenderContent(bool $val){
		$this->data->setRenderContent($val);
	}
	public function setResponse(Response $response){
		$this->data->setResponse($response);
	}
	public function getTemplate(){
		return $this->data->getTemplate();
	}
	public function setTemplate(string $value){
		$this->data->setTemplate($value);
	}
}
