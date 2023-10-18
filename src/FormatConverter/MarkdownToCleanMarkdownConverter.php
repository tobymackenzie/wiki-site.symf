<?php
namespace TJM\WikiSite\FormatConverter;
use League\HTMLToMarkdown\HtmlConverter;

/*
Like `HtmlConverter`, but easier reading / more correct:

- force strip front matter
- no conversion to HTML entities
- don't strip HTML in codefence
*/
class MarkdownToCleanMarkdownConverter implements ConverterInterface{
	protected $htmlConverter;
	public function __construct(HtmlConverter $htmlConverter = null){
		$this->htmlConverter = $htmlConverter ?? new HtmlConverter([
			'hard_break'=> true,
			'strip_tags'=> true,
		]);
	}
	public function supports(string $from, string $to){
		return strtolower($from) === 'md' && in_array(strtolower($to), ['md', 'txt']);
	}
	public function convert(string $content, string $from = null, string $to = null){
		$explodedContent = explode("\n", trim($content));
		$content
			= $multilineHtmlContent
			= $lineFinal
			= $lineTmp
			= $explodedContent[] //-# so we can use same logic for last line
			= ''
		;
		$inCodeFence
			= $isCommentOpened
			= false
		;
		//--strip front matter
		if(substr($explodedContent[0], 0, 3) === '---'){
			unset($explodedContent[0]);
			$done = false;
			foreach($explodedContent as $key=> $line){
				if($done && !empty($line)){
					break;
				}
				unset($explodedContent[$key]);
				if(substr($line, 0, 3) === '---'){
					$done = true;
				}
			}
		}
		foreach($explodedContent as $line){
			//--start / stop codefence
			if(substr($line, 0, 3) === '```'){
				$content .= "{$line}\n";
				$inCodeFence = !$inCodeFence;
			//--output codefence lines directly
			}elseif($inCodeFence || substr($line, 0, 4) === '    '){
				$content .= "{$line}\n";
			//--if full HTML line, stick in var to be converted all at once
			//-! naive, can we do better?
			}elseif(
				$isCommentOpened
				|| substr($line, 0, 1) === "\t"
				|| (substr(trim($line), 0, 1) === '<' && substr(trim($line), -1) === '>')
				|| substr(trim($line), 0, 4) === '<!--'
			){
				$openCommentPos = strrpos($line, '<!--');
				$closeCommentPos = strrpos($line, '-->');
				if($openCommentPos !== false && ($closeCommentPos === false || $closeCommentPos < $openCommentPos)){
					$isCommentOpened = true;
				}elseif($closeCommentPos !== false){
					$isCommentOpened = false;
				}
				$multilineHtmlContent .= "{$line}\n";
			}else{
				if($multilineHtmlContent){
					//--strip comments: opinionated
					$multilineHtmlContent = preg_replace('/<!--.*-->/s', '', $multilineHtmlContent);
					$content .= $this->htmlConverter->convert($multilineHtmlContent) . "\n";
					$multilineHtmlContent = '';
				}
				if($line){
					$content .= $this->convertMarkdownLine($line) . "\n";
				}else{
					$content .= "\n";
				}
			}
		}
		return trim($content) . "\n";
	}
	protected function convertMarkdownLine($line){
		$newLine = '';
		$inCodeFence = substr($line, 0, 1) === '`';
		$context = $this;
		$pregCallback = function($matches) use ($context){
			return $context->htmlConverter->convert($matches[0]);
		};
		foreach(explode('`', $line) as $lineBit){
			if($inCodeFence){
				$newLine .= "`{$lineBit}`";
			}else{
				$tmp = preg_replace_callback('@<[\w\-:]+.*>.*</[\w\-:]+>@', $pregCallback, $lineBit);
				$tmp = preg_replace_callback('@<[\w\-:]+.*/>@', $pregCallback, $tmp);
				$newLine .= $tmp;
			}
			$inCodeFence = !$inCodeFence;
		}
		return $newLine;
	}
}
