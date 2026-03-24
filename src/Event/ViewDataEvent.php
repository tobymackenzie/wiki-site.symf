<?php
namespace TJM\WikiSite\Event;

class ViewDataEvent extends ViewActionEvent{
	public function setData($a, $b = null){
		$this->data->setData($a, $b);
	}
}
