<?php
namespace TJM\WikiSite\Event;

class ViewContentEvent extends ViewActionEvent{
	public function setContent(string $value){
		$this->data->setContent($value);
	}
}
