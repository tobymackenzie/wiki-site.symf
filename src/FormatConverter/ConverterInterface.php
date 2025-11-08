<?php
namespace TJM\WikiSite\FormatConverter;

interface ConverterInterface{
	/*
	Method: supports
	Arguments:
		$from: format extension to convert from
		$to: format extension to convert to
	*/
	public function supports(string $from, string $to);
	public function convert(string $content, ?string $from = null, ?string $to = null);
}
