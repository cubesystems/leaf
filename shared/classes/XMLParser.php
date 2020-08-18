<?php

//
// Based on code found online at:
// http://php.net/manual/en/function.xml-parse-into-struct.php
//
// Author: Eric Pollmann
// Released into public domain September 2003
// http://eric.pollmann.net/work/public_domain/
//

class XMLParser {
	var $data;		// Input XML data buffer
	var $vals;		// Struct created by xml_parse_into_struct

	// Read in XML on object creation.
	// We can take raw XML data, a stream, a filename, or a url.
	function XMLParser($data_source, $data_source_type = 'raw') {
		$this->data = '';
		if ($data_source_type == 'raw')
		{
			$this->data = $data_source;
		}
		elseif ($data_source_type == 'stream')
		{
			$this->data = file_get_contents($data_source);
		// try filename, then if that fails...
		}
		elseif (file_exists($data_source))
		{
			$this->data = file_get_contents($data_source);
		}
		else
		{
			trigger_error('no file:' . $data_source, E_USER_ERROR);
		}
	}

	// Parse the XML file into a verbose, flat array struct.
	// Then, coerce that into a simple nested array.
	function getTree() {
		$parser = xml_parser_create('utf-8');
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, $this->data, $vals, $index); 
		xml_parser_free($parser);

		$i = -1;
		return $this->getchildren($vals, $i);
	}

	// internal function: build a node of the tree
	function buildtag($thisvals, $vals, &$i, $type) {
		$tag = array();
		if (isset($thisvals['attributes']))
			$tag['@'] = $thisvals['attributes']; 

		// complete tag, just return it for storage in array
		if ($type === 'complete')
		{
			$tag['value'] = empty($thisvals['value']) ? NULL : $thisvals['value'];
		}
		// open tag, recurse
		else
			$tag = array_merge($tag, $this->getchildren($vals, $i));

		return $tag;
	}

	// internal function: build an nested array representing children
	function getchildren($vals, &$i) { 
		$children = array(  // Contains node data
			'*' => array(), //child index
			'#' => array() //child nodes
		);    

		// Node has CDATA before it's children
		if ($i > -1 && isset($vals[$i]['value']))
		{
			$children['value'] = $vals[$i]['value'];
		}
		$index = array();
		// Loop through children, until hit close tag or run out of tags
		while (++$i < count($vals)) { 
			$type = $vals[$i]['type'];

			// 'cdata':	Node has CDATA after one of it's children
			// 		(Add to cdata found before in this case)
			if ($type === 'cdata')
			{
				$children['value'] .= $vals[$i]['value'];
			}
			// 'complete':	At end of current branch
			// 'open':	Node has children, recurse
			elseif ($type === 'complete' || $type === 'open')
			{
				$tag = $this->buildtag($vals[$i], $vals, $i, $type);
				$tag['tag'] = $vals[$i]['tag'];
				$children['*'][$tag['tag']][] = sizeof($children['#']);
				$children['#'][] = $tag;
			}
			// 'close:	End of node, return collected data
			//		Do not increment $i or nodes disappear!
			elseif ($type === 'close')
			{
				break;
			}
		} 
		return $children;
	} 
}
?>