<?

class banner_image extends banner_type
{

    function getHtml($params = array())
    {
        // automatically add alt text for image_text images
        if (
            ($this->textMode)
            &&
            (!isset($params['alt']))
        )
        {
            $params['alt'] = $this->text;
        }

        $falseArray =  array(0, '0', false, 'no', null);
        $boolArgs = array('altEscape', 'titleEscape', 'linkRelEscape');
        foreach ($boolArgs as $arg)
        {
            if (
                (isset($params[$arg]))
                &&
                (in_array($params[$arg], $falseArray))
            )
            {
                $params[$arg] = false;
            }
            else
            {
                $params[$arg] = true;
            }

        }


        return parent::getHtml($params);
    }
}


?>