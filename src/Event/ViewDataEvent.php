<?php
namespace TJM\WikiSite\Event;
use Symfony\Contracts\EventDispatcher\Event;

class ViewDataEvent extends Event{
	protected array $data;
	public function __construct(array $data){
		$this->data = $data;
	}
	public function getData(){
		return $this->data;
	}
	public function setData(array $value){
		$this->data = $value;
	}
}
