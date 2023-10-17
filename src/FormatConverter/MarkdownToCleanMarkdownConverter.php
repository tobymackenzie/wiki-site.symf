<?php
namespace TJM\WikiSite\FormatConverter;

class MarkdownToCleanMarkdownConverter implements ConverterInterface{
	protected $toHtmlConverter;
	protected $toMarkdownConverter;
	public function __construct(MarkdownToHtmlConverter $toHtmlConverter = null, HtmlToMarkdownConverter $toMarkdownConverter = null){
		$this->toHtmlConverter = $toHtmlConverter ?? new MarkdownToHtmlConverter();
		$this->toMarkdownConverter = $toMarkdownConverter ?? new HtmlToMarkdownConverter();
	}
	public function supports(string $from, string $to){
		return strtolower($from) === 'md' && in_array(strtolower($to), ['md', 'txt']);
	}
	public function convert(string $content, string $from = null, string $to = null){
		return $this->toMarkdownConverter->convert($this->toHtmlConverter->convert($content));
	}
}
