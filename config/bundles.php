<?php
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use TJM\WikiSiteBundle\TJMWikiSiteBundle;
return [
	FrameworkBundle::class=> ['all'=> true],
	TwigBundle::class=> ['all'=> true],
	TJMWikiSiteBundle::class=> ['all'=> true],
];
