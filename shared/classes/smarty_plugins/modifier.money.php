<?php
function smarty_modifier_money( $number, $decimals = 2 )
{
    $divideBy = pow( 10, $decimals );
    return number_format( $number / $divideBy, $decimals, '.', ' ' );
}
?>
