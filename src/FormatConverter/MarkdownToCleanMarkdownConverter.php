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
	protected bool $stripComments = true;
	public function __construct(HtmlConverter $htmlConverter = null, $opts = []){
		if(!is_array($opts)){
			$opts = ['stripComments'=> $opts];
		}
		foreach($opts as $key=> $value){
			$this->$key = $value;
		}
		$this->htmlConverter = $htmlConverter ?? new HtmlConverter([
			'hard_break'=> true,
			'strip_tags'=> true,
			'preserve_comments'=> !$this->stripComments,
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
		$unclosedTagCount = 0;
		foreach($explodedContent as $line){
			//--start / stop codefence
			if(substr(trim($line), 0, 3) === '```'){
				$content .= "{$line}\n";
				$inCodeFence = !$inCodeFence;
			//--output codefence lines directly
			}elseif($inCodeFence || substr(trim($line), 0, 4) === '    '){
				$content .= "{$line}\n";
			//--if full HTML line, stick in var to be converted all at once
			}elseif(
				$isCommentOpened
				|| substr($line, 0, 1) === "\t"
				|| (substr(trim($line), 0, 1) === '<' && strpos($line, '>') !== false)
				|| substr(trim($line), 0, 4) === '<!--'
				|| $unclosedTagCount > 0
			){
				$openCommentPos = strrpos($line, '<!--');
				$closeCommentPos = strrpos($line, '-->');
				if($openCommentPos !== false && ($closeCommentPos === false || $closeCommentPos < $openCommentPos)){
					$isCommentOpened = true;
				}elseif($closeCommentPos !== false){
					$isCommentOpened = false;
				}
				$thisTagCount = 0;
				if(!$isCommentOpened && ($openMatchCount = preg_match_all(':<[\w][^/>]*>:', $line)) && $openMatchCount > 0){
					$thisTagCount += $openMatchCount;
				}
				if(!$isCommentOpened && ($unclosedTagCount > 0 || $thisTagCount > 0) && ($closeMatchCount = preg_match_all(':</[\w][^/>]*>:', $line)) && $closeMatchCount > 0){
					$thisTagCount -= $closeMatchCount;
				}
				$unclosedTagCount += $thisTagCount;
				$multilineHtmlContent .= "{$line}\n";
			}else{
				if($multilineHtmlContent){
					if($content && substr($content, -2) !== "\n\n"){
						$content .= "\n";
					}
					$converted = $this->convertMultilineHTML($multilineHtmlContent);
					$content .= $converted;
					if($line && trim($converted)){
						$content .= "\n";
					}
					$multilineHtmlContent = '';
				}
				if($line){
					$content .= $this->convertMarkdownLine($line) . "\n";
				}else{
					$content .= "\n";
				}
			}
		}
		if($multilineHtmlContent){
			$content .= $this->convertMultilineHTML($multilineHtmlContent) . "\n";
		}

		$content = trim($content);

		//--fix some things in final output. must handle code fences differently
		$inCodeFence = substr($content, 0, 3) === '```';
		$tmp = [];
		foreach(explode('```', $content) as $part){
			if($inCodeFence){
				$inCodeFence = false;
			}else{
				$tmp2 = [];
				$inCodeSpan = substr($content, 0, 3) === '`';
				foreach(explode('`', $part) as $subpart){
					if($inCodeSpan){
						$inCodeSpan = false;
					}else{
						//--fix `&amp;` that are erroneously converted the wrong way
						$subpart = str_replace('&amp;', '&', $subpart);
						//--fix extra line breaks
						$subpart = preg_replace(":\n[\n]+\n:", "\n\n", $subpart);
						$inCodeSpan = true;
						//--fix extra trailing double spaces inserted in some cases
						$subpart = str_replace("  \n\n", "\n\n", $subpart);
					}
					$tmp2[] = $subpart;
				}
				$part = implode('`', $tmp2);
				$inCodeFence = true;
			}
			$tmp[] = $part;
		}
		$content = implode('```', $tmp);

		return trim($content) . "\n";
	}
	protected function convertBit($bit){
		//-# string that is only a comment throws errors, skip if so
		if(!preg_match(':^[\s]*<\!--.*-->[\s]*$:s', $bit)){
			//--convert
			$bit = $this->htmlConverter->convert($bit);
			//--fix ws for periods (stops)
			$bit = preg_replace('/\. ([\w])/', '.  \1', $bit);
		}elseif($this->stripComments){
			$bit = '';
		}
		return $bit;
	}
	protected function convertMarkdownLine($line){
		$newLine = '';
		$inCodeFence = substr($line, 0, 1) === '`';
		$context = $this;
		$pregCallback = function($matches) use ($context){
			return $context->convertBit($matches[0]);
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
	protected function convertMultilineHTML($bit){
		$out = $bit;
		//--phpleague stripping doctype even in code blocks: store as a something else so converter doesn't see
		$out = preg_replace(':&lt;\!DOCTYPE:', '&lt;!@@@DOCTYPE', $out);
		//--fix extra line breaks in pre-code blocks
		$out = preg_replace(':[\s]*(<pre[^>]*>)[\s]*(<code[^>]*>)[\s]+:s', '\1\2', $out);
		$out = preg_replace(':[\s]+(</code>)[\s]*(</pre>)[\s]*:s', '\1\2', $out);
		//--convert
		$out = "{$this->convertBit($out)}\n";
		//--restore doctype from above change
		$out = str_replace('<!@@@DOCTYPE', '<!DOCTYPE', $out);
		//--ensure if we are ending with paragraph that output keeps the paragraph
		if(substr(trim($bit), -4, 4) === '</p>'){
			$out .= "\n";
		}
		//--better formatting of code block language
		$out = preg_replace('/^```([\w -]+)$/m', '``` \1', $out);
		return $out;
	}
}
