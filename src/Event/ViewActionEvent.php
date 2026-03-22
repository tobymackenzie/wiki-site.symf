<?php
namespace TJM\WikiSite\Event;
use Symfony\Contracts\EventDispatcher\Event;
use TJM\WikiSite\Data\ViewActionData;

class ViewActionEvent extends Event{
	protected ViewActionData $data;
	public function __construct(ViewActionData $data){
		$this->data = $data;
	}
	public function getContent(){
		return $this->data->content;
	}
	public function getData(){
		return $this->data->data;
	}
	public function getPath(){
		return $this->data->path;
	}
}
