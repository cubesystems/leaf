<?
class leafHtmlCleaner extends leafComponent{

	public static function clean($html)
	{
		// do nothing
		if (!extension_loaded('tidy') || round(phpversion('tidy')) < 2)
		{
			return $html;
		}

		// run tidy 1st time
		$config = array(
			'indent' => true,
			'output-xhtml' => true,
			'logical-emphasis' => true,
			'drop-proprietary-attributes' => true,
			'char-encoding' => 'utf8',
			'input-encoding' => 'utf8',
			'output-encoding' => 'utf8',
			'alt-text' => true,
			'drop-font-tags' => true,
			'show-body-only' => true,
			'word-2000' => true,
			'wrap' => 0
		);
		$tidy = new tidy;
		$tidy->parseString($html, $config, 'utf8');
		$tidy->cleanRepair();
		$html = (string) $tidy;

		// custom regexp
		$commoncrap = array(
			'font-weight: normal;',
			'font-style: normal;',
			'line-height: normal;',
			'font-size-adjust: none;',
			'font-stretch: normal;'
		); //If it is so normal, why they bother?
		$html = str_replace($commoncrap, "'", $html);
		$patterns = array();
		$replacements = array();
		$patterns[0] = '/(<table\s.*)(width=)(\d+%)(\D)/i'; # Fix unquoted non-alphanumeric characters in table tags
		$patterns[1] = '/(<td\s.*)(width=)(\d+%)(\D)/i';
		$patterns[2] = '/(<th\s.*)(width=)(\d+%)(\D)/i';
		$patterns[5] = '/<\/st1:address>(<\/st1:\w*>)?<\/p>[\n\r\s]*<p[\s\w="\']*>/i';
		$patterns[6] = '/<o:p.*?>/i';
		$patterns[7] = '/<\/o:p>/i';
		$patterns[8] = '/<o:SmartTagType[^>]*>/i';
		$patterns[9] = '/<st1:[\w\s"=]*>/i';
		$patterns[10] = '/<\/st1:\w*>/i';
		$patterns[11] = '/ class="Mso(.*?)"/i';
		$patterns[12] = '/ style="margin-top: 0cm;"/i';
		$patterns[14] = '/<ul(.*?)>/i';
		$patterns[15] = '/<ol(.*?)>/i';
		$patterns[17] = '/<br \/>&nbsp;<br \/>/i';
		$patterns[18] = '/&nbsp;<br \/>/i';
		$patterns[19] = '/<!--\[if([\s\S]*)\[endif\]-->/Ui';
		$patterns[20] = '/\s*style=(""|\'\')/';
		$patterns[21] = '/ style=[\'"]tab-interval:[^\'"]*[\'"]/i';
		$patterns[22] = '/behavior:[^;\'"]*;*(\n|\r)*/i';
		$patterns[23] = '/mso-[^:]*:"[^"]*";/i';
		$patterns[24] = '/mso-[^;\'"]*;*(\n|\r)*/i';
		$patterns[25] = '/\s*font-family:[^;"]*;?/i';
		$patterns[26] = '/margin[^"\';]*;?/i';
		$patterns[27] = '/text-indent[^"\';]*;?/i';
		$patterns[28] = '/tab-stops:[^\'";]*;?/i';
		$patterns[29] = '/border-color: *([^;\'"]*)/i';
		$patterns[30] = '/border-collapse: *([^;\'"]*)/i';
		$patterns[31] = '/page-break-before: *([^;\'"]*)/i';
		$patterns[32] = '/font-variant: *([^;\'"]*)/i';
		$patterns[33] = '/<span [^>]*><br \/><\/span><br \/>/i';
		$patterns[34] = '/" "/';
		$patterns[37] = '/ style=""/';
		$patterns[41] = '/;;/';
		$patterns[42] = '/";/';
		$patterns[43] = '/<li(.*?)>/i';
		$replacements[0] = '$1$2"$3"$4';
		$replacements[1] = '$1$2"$3"$4';
		$replacements[2] = '$1$2"$3"$4';
		$replacements[5] = '<br />';
		$replacements[6] = '';
		$replacements[7] = '<br />';
		$replacements[8] = '';
		$replacements[9] = '';
		$replacements[10] = '';
		$replacements[11] = '';
		$replacements[12] = '';
		$replacements[14] = '<ul>';
		$replacements[15] = '<ol>';
		$replacements[17] = '<br />';
		$replacements[18] = '<br />';
		$replacements[19] = '';
		$replacements[20] = '';
		$replacements[21] = '';
		$replacements[22] = '';
		$replacements[23] = '';
		$replacements[24] = '';
		$replacements[25] = '';
		$replacements[26] = '';
		$replacements[27] = '';
		$replacements[28] = '';
		$replacements[29] = '';
		$replacements[30] = '';
		$replacements[31] = '';
		$replacements[32] = '';
		$replacements[33] = '<br />';
		$replacements[34] = '""';
		$replacements[37] = '';


		$replacements[41] = ';';
		$replacements[42] = '"';
		$replacements[43] = '<li>';

		// remove on* attributes
		$patterns[44] = '/(<[^>]+[\s\r\n\"\'])(on|xmlns)[^>]*>/iU';
		$replacements[44] = '$1>';

		// remove lang attribute
		$patterns[45] = '/\slang\s*=\s*("|\')(.*?)\1/is';
		$replacements[45] = '';

		// remove xml:lang attribute
		$patterns[46] = '/\sxml:lang\s*=\s*("|\')(.*?)\1/is';
		$replacements[46] = '';

		// remove simple word comments
		$patterns[47] = '/<!-.*?>/';
		$replacements[47] = '';

		$patterns[48] = '/\xAD/u'; // kill soft hyphen
		$replacements[48] = '';

		$patterns[49] = '/(&lt;!--\s\/\*)\s([\w|\s]*)\s(\*\/)(.*?)(--&gt;)/'; // remove escaped comments
		$replacements[49] = '';


		ksort($patterns);
		ksort($replacements);


		$html = preg_replace($patterns, $replacements, $html);

		// clear styles
		$html = preg_replace_callback('/(<(\w+\s))([^>]*)((?<=\s)style=\"(.*?)\")/', array('leafHtmlCleaner', 'cleanInlineStyle'), $html);


		// run tidy 2nd time
		$config = array(
			'indent' => true,
			'output-xhtml' => true,
			'logical-emphasis' => true,
			'drop-proprietary-attributes' => true,
			'char-encoding' => 'utf8',
			'input-encoding' => 'utf8',
			'output-encoding' => 'utf8',
			'alt-text' => true,
			'show-body-only' => true,
			'word-2000' => true,
			'wrap' => 0
		);
		$tidy = new tidy;
		$tidy->parseString($html, $config, 'utf8');
		$tidy->cleanRepair();
        
		$html = (string) $tidy;
        
		return $html;
	}

	public static function combine_inline ($string)
	{
		$config = array(
			'combine_inline' => array('em', 'strong', 'u', 'i', 'b', 'sup', 'sub', 'span'),
			'combine_inline_chars' => '(([\s,.:;\'"\[\]\(\)\+=_\-\`\\\\\/~!@#\$%\^*&\{\}]*|(&#?[a-z0-9]*;)*)*)'
		);

		$nr = count($config['combine_inline'])-1;
		$f = false;
		$c = -1;
		$ct = 0;
		for ($i = 0; $i <= $nr; $i++)
		{
			$regex = '/<\/' . $config['combine_inline'][$i] . '>' . $config['combine_inline_chars'] . '<' . $config['combine_inline'][$i] . '>/i';
			$string = preg_replace($regex, '$1', $string, -1, $c);
			$ct += $c;
			if ($c > 0)
				$f = true;

			if ($i == $nr && $f)
			{
				$i = -1;
				$f = false;
			}
		}

		return $string;
	}

	public static function cleanInlineStyle($matches)
	{
	    /*
        $matches contains something like this:
        (
            [0] => <img class="fromTree id-1237" src="../?object_id=1237" border="0" alt="230x230_fight_club_norton_soap.jpg" title="230x230_fight_club_norton_soap.jpg" style="display: block;  "
            [1] => <img
            [2] => img
            [3] => class="fromTree id-1237" src="../?object_id=1237" border="0" alt="230x230_fight_club_norton_soap.jpg" title="230x230_fight_club_norton_soap.jpg"
            [4] => style="display: block;  "
            [5] => display: block;
        )
	    */

	    // debug ($matches);

	    $tagHtml                = $matches[0];  // not the full tag, only till the end of style=""
	    $tagName                = strtolower(trim($matches[2]));
	    $originalStyleAttribute = $matches[4];
	    $styleAttributeValue    = trim($matches[5]);


		$styleMatches = array();
		preg_match_all('/(.*?)\:(.*?)\s*?(;|$)/i', $styleAttributeValue, $styleMatches);


		$styles = array();
		$allowedStyles = array('text-align');

		// color style
		$allowInlineColor = true;
		$config = leaf_get('properties', 'leafHtmlCleaner');
		if (
            (is_array($config))
            &&
            (array_key_exists('allowInlineStyleColor', $config))
        )
        {
            $allowInlineColor = (bool) $config['allowInlineStyleColor'];
        }
		if ($allowInlineColor)
		{
		    $allowedStyles[] = 'color';
		}

		if( is_array( $config ) && array_key_exists( 'allowedStyles', $config ) )
        {
            $allowedStyles = array_merge( $allowedStyles, $config['allowedStyles'] );
        }

		// for img tags allow display, margin-left, margin-right if display is set to block (needed for tinymce centering)
		// but since all margins are already removed earlier ($patterns[26])
		// the code actually re-adds the margin-left/right: auto for all imgs with display: block;
		if (
            ($tagName == 'img')
            &&
            (!empty($styleMatches[1]))
            &&
            (($displayKey = array_search('display', $styleMatches[1])) !== FALSE)
            &&
            (trim($styleMatches[2][$displayKey]) == 'block')
        )
		{
		    $allowedStyles[] = 'display';
            $allowedStyles[] = 'margin-left';
            $allowedStyles[] = 'margin-right';

            // add auto margins
            $styleMatches[1][] = 'margin-left';
            $styleMatches[2][] = 'auto';

            $styleMatches[1][] = 'margin-right';
            $styleMatches[2][] = 'auto';
		}

		foreach ($styleMatches[1] as $key => $styleName)
		{
			$styleName = trim(strtolower($styleName));
			if(in_array($styleName, $allowedStyles))
			{
				$styles[] = $styleName . ':' . $styleMatches[2][$key];
			}
		}
		$newStyleAttribute = '';
		if (!empty($styles))
		{
			$newStyleAttribute = 'style="' . implode('; ', $styles) . '"';
		}

		$tagHtml = str_replace($originalStyleAttribute, $newStyleAttribute, $tagHtml);

		return $tagHtml;
	}
}
