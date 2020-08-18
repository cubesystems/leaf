<?
class pngGammaRemover extends leafComponent {

	public function processInput($fileName, $params = array())
	{
        $gammaRemovedOk = pngEdit::removeGammaFromFile( $fileName );
        return $gammaRemovedOk;
	}
}
?>