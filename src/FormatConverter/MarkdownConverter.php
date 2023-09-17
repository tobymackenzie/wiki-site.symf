<?php
namespace TJM\WikiSite\FormatConverter;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\ConverterInterface as LeagueConverterInterface;

class MarkdownConverter implements ConverterInterface{
	protected $converter;
	public function __construct(LeagueConverterInterface $converter = null){
		$this->converter = $converter ?? new CommonMarkConverter();
	}
	public function supports(string $from, string $to){
		return $from === 'md' && in_array($to, ['html', 'xhtml']);
	}
	public function convert(string $content, string $from = null, string $to = null){
		return $this->converter->convert($content);
	}
}
