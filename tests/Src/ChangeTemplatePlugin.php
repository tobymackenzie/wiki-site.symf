<?php
namespace TJM\WikiSite\Tests\Src;
use TJM\WikiSite\Event\ViewStartEvent;
use TJM\Wiki\Plugin;

class ChangeTemplatePlugin extends Plugin{
	static public function getSubscribedEvents(): array{
		return [
			ViewStartEvent::class=> 'onViewStart',
		];
	}
	public function onViewStart(ViewStartEvent $event){
		$event->setTemplate('@TJMWikiSite/alt.txt.twig');
	}
}
