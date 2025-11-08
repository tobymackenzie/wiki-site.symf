<?php
namespace TJM\WikiSite\Event;
use Symfony\Contracts\EventDispatcher\Event;

class ViewContentEvent extends Event{
	protected string $content;
	protected ?string $path;
	public function __construct(string $content, ?string $path = null){
		$this->content = $content;
		$this->path = $path;
	}
	public function getContent(){
		return $this->content;
	}
	public function setContent(string $value){
		$this->content = $value;
	}
	public function getPath(){
		return $this->path;
	}
}
