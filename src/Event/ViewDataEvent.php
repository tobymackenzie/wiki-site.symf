<?php
namespace TJM\WikiSite\Event;

class ViewDataEvent extends ViewActionEvent{
	public function setData(array $value){
		$this->data->setData($value);
	}
}
