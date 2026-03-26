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
	public function getContent(){
		return $this->data->getContent();
	}
	public function getData($key = null){
		return $this->data->getData($key);
	}
	public function getPath(){
		return $this->data->getPath();
	}
	public function setResponse(Response $response){
		$this->data->setResponse($response);
	}
	public function getTemplate(){
		return $this->data->getTemplate();
	}
}
