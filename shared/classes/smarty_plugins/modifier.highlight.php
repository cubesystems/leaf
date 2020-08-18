<?php 

 /*
 * Highlights a text by searching a words in it. 
 */
 
function smarty_modifier_highlight( $text = '', $words = '', $onlyWholeWord = false ) 
{ 
    $words_array = explode( " ", $words );
    
    if( strlen( $text ) > 0 && !empty( $words_array ) )
    {
        foreach( $words_array as $word )
        {
            if( strlen( $word ) > 0 )
            {
                $searchString = '/(' . preg_quote( $word, '/' ) . ')/i';
                
                if( $onlyWholeWord )
                {
                    $searchString = '/\b(' . preg_quote( $word, '/' ) . ')\b/i';
                }

                $text = preg_replace( $searchString, '<span class="highlight">${1}</span>', $text ); 
            }
        }
    }
    
    return $text; 
}
