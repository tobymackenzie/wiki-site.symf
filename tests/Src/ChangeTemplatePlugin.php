<?php
namespace TJM\WikiSite\Tests\Src;
use TJM\WikiSite\Event\ViewStartEvent;
use TJM\WikiSite\PluginInterface;

class ChangeTemplatePlugin implements PluginInterface{
	static public function getSubscribedEvents(): array{
		return [
			ViewStartEvent::class=> 'onViewStart',
		];
	}
	public function onViewStart(ViewStartEvent $event){
		$event->setTemplate('@TJMWikiSite/alt.txt.twig');
	}
}
