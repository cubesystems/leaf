<?

class pagedNavigation
{
    protected $pageCount  = 1;
    protected $activePage = 1;
    protected $maxPages   = null;

    protected $minSkip = 2;


    protected $loaded = false;

    protected $pages = null;

    protected $previous = null;
    protected $next = null;


    protected $allowGet = array('pageCount', 'pages', 'activePage', 'previous', 'next');

	public function __get( $name )
	{
	    if (
            ($this->loaded)
            &&
            (in_array($name, $this->allowGet))
        )
	    {
	        return $this->$name;
	    }
        return null;
	}

    public static function getFromList( $pagedList, $maxPages  = null)
    {
        if (
            (!$pagedList instanceof pagedList)
            ||
            (
                (!isPositiveInt($maxPages)) && (!is_null($maxPages))
            )
        )
        {
            return null;
        }
        $pageCount = $pagedList->pages;
        $activePage = $pagedList->page;

        $instance = new pagedNavigation( $pageCount, $activePage, $maxPages );

        if (!$instance->isLoaded())
        {
            return null;
        }

        return $instance;

    }

    function __construct( $pageCount, $activePage, $maxPages = null)
    {
        if (
            (!isPositiveInt($pageCount))
            ||
            (!isPositiveInt($activePage))
            ||
            (
                (!isPositiveInt($maxPages))
                &&
                (!is_null($maxPages))
            )
        )
        {
            $this->loaded = false;
            return null;
        }

        $this->pageCount  = $pageCount;
        $this->activePage = $activePage;
        $this->maxPages = $maxPages;

        if ($this->loadPages())
        {
            $this->loaded = true;
        }
    }


    public function isLoaded()
    {
        return (bool) $this->loaded;
    }

    protected function loadPages()
    {
        $pages = $this->getPages();
        if (!$pages)
        {
            return false;
        }
        $this->pages = $pages;

        $this->previous = ($this->activePage > 1) ? $this->activePage - 1 : null;
        $this->next     = ($this->activePage < $this->pageCount) ? $this->activePage + 1 : null;

        return true;
    }

    protected function getPages()
    {
	    // VOODOO!

        $pageCount  = $this->pageCount;
        $maxPages   = $this->maxPages;
        $minSkip    = $this->minSkip;
        $activePage = $this->activePage;

        if (is_null($maxPages))
        {
            $maxPages = $pageCount;
        }

        if (!$pageCount || !$maxPages || !$activePage)
        {
            return null;
        }


	    $maxPagesInMiddle = $maxPages - ( ($maxPages + 1) % 2 ) - 2; // always odd

	    $onMiddleSides = ($maxPagesInMiddle - 1) / 2;

        $middleStart = 1;
        $middleEnd = $pageCount;

        if ($pageCount > $maxPages)
        {
            $middleStart = $activePage - $onMiddleSides;
            $middleEnd = $activePage + $onMiddleSides;
            if ($middleStart - $minSkip <= 1)
            {
                $middleStart = 1;
            }
            if ($middleEnd + $minSkip >= $pageCount)
            {
                $middleEnd = $pageCount;
            }
        }

        $skipInBeginning = ($middleStart != 1);
        $skipInEnd = ($middleEnd != $pageCount);

        if ($skipInBeginning && !$skipInEnd)
        {
            // active page is in the end section with no skip, extend end to max possible
            $middleStart = $pageCount - $maxPagesInMiddle - 1;

            // but preserve minskip rule
            if ($middleStart - $minSkip <= 1)
            {
                $middleStart = 1 + $minSkip;
            }
        }

        if ($skipInEnd && !$skipInBeginning)
        {
            // active page is in the beginning section with no skip, extend end to max possible
            $middleEnd = $maxPagesInMiddle + 2;

            // but preserve minskip rule
            if ($middleEnd + $minSkip >= $pageCount)
            {
                $middleEnd = $pageCount - $minSkip - 1;
            }
        }

        $pages = array();
        if ($skipInBeginning)
        {
            $pages[] = array('number' => 1);
            $pages[] = array('skipped' => true);
        }
        for ($i = $middleStart; $i <= $middleEnd; $i++)
        {
            $page = array('number' => $i);
            if ($i == $activePage)
            {
                $page['active'] = true;
            }
            $pages[] = $page;
        }
        if ($skipInEnd)
        {
            $pages[] = array('skipped' => true);
            $pages[] = array('number' => $pageCount);
        }

        // debug ($pageNavigation);

        return $pages;
    }


}



?>