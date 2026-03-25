<?php
namespace TJM\WikiSite\Event;

class ViewStartEvent extends ViewActionEvent{
	public function setTemplate(string $value){
		$this->data->setTemplate($value);
	}
}
