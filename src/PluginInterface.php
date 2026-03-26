<?php
/*
Currently just an EventSubscriberInterface, but allows expansion or modification in the future.
*/
namespace TJM\WikiSite;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface PluginInterface extends EventSubscriberInterface{}
