<?php
/*
    escapes plaintext for output in html context
    automatically creates links from things that look like URLs or email addresses

    allows invalid adresses (does not validate URLS / email addresses)
*/

function smarty_modifier_escape_with_links($string, $targetBlank = true, $noFollow = false)
{
    $targetBlankCode = ($targetBlank) ? ' target="_blank"' : '';
    $noFollowCode    = ($noFollow)    ? ' rel="nofollow"' : '';

    // encode <>"&' special chars
    $string = htmlspecialchars($string);

    // all entered plaintext urls are escaped now
    // allow escaped urls to contain anything except " ' > < & \ and spaces ( but &amp; is allowed )
    $urlCharsPattern = '(([^\"\>\<\s\\\\\'\&]|\&amp\;)+)';

    // for simplicity use the same character rules for email address parts
    $emailLocalPartCharsPattern = $urlCharsPattern;
    $emailDomainCharsPattern    = $urlCharsPattern;


    // matches strings that look like email addresses
    // replaces with mailto links
    $string = preg_replace
    (
        '/(^|\s)(' . $emailLocalPartCharsPattern . '@' . $emailDomainCharsPattern . '\\.\\w+)($|\s)/mu',
        '\\1<a href="mailto:\\2">\\2</a>\\7',
        $string
    );

    // matches strings starting on a word boundary with http(s) or www. then followed by allowed url characters in any order
    // replaces with links (adds http if necessary)

    $string = preg_replace
    (
        '/\b(http(s?)\:\/\/)??((http(s?)\:\/\/)|(((http(s?)\:\/\/)?(www\.))))' . $urlCharsPattern . '/u',
        '<a href="http\\5://\\10\\11"' . $targetBlankCode . $noFollowCode . '>\\3\\11</a>',
        $string
    );


    return $string;
}

