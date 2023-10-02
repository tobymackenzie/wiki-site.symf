<?php
namespace TJM\WikiSite\FormatConverter;
use League\HTMLToMarkdown\HtmlConverter;

class HtmlToMarkdownConverter implements ConverterInterface{
	protected $converter;
	public function __construct(HtmlConverter $converter = null){
		$this->converter = $converter ?? new HtmlConverter([
			'hard_break'=> true,
			'strip_tags'=> true,
		]);
	}
	public function supports(string $from, string $to){
		return in_array(strtolower($from), ['html', 'xhtml']) && in_array(strtolower($to), ['md', 'txt']);
	}
	public function convert(string $content, string $from = null, string $to = null){
		return $this->converter->convert($content);
	}
}
