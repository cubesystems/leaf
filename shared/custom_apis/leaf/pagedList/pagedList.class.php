<?php
/**
 * A class for fetching database rows for (optionally) paged output.
 *
 *
 * Usage examples:
 * <code>
 * <?php
 *
 *     // load second page of the list showing 3 items per page:
 *
 *     // prepare query to fetch all visible children of object 42
 *     $queryParts = array
 *     (
 *          'select'  => 'o.*',
 *          'from'    => 'objects AS o',
 *          'where'   =>  array (
 *              'oa.parent_id = 42',
 *              'o.visible',
 *          ),
 *          'orderBy' => 'o.name'
 *     );
 *
 *     // show 3 results per page
 *     $itemsPerPage = 3;
 *
 *     // set page 2 as active
 *     $pageNo = 2;
 *
 *     // create list (corresponding db rows are automatically loaded)
 *     $list = new pagedList( $queryParts, $itemsPerPage, $pageNo );
 *
 *     // iterate through fetched rows, print object names
 *     foreach ($list as $item)
 *     {
 *         echo $item['name'] . "\n";
 *     }
 *
 *     // output total number of pages
 *     echo $list->pages;
 *
 *     // output total number of results (in all pages)
 *     echo $list->total;
 *
 *
 *
 *
 *     // same example without automatic loading of results:
 *
 *     // create list (nothing is fetched from db at this point)
 *     $list = new pagedList( $queryParts, $itemsPerPage );
 *
 *     // load page manually
 *     $list->loadPage(2);
 *
 *     // load some other page
 *     $list->loadPage(3);
 *
 *     // or change items per page
 *     $list->setItemsPerPage(4);
 *     $list->loadPage(2);
 *
 *
 *
 *
 *     // example with full, unpaged list:
 *
 *     // create list, pass only the query parts (nothing is fetched from db at this point)
 *     $list = new pagedList( $queryParts );
 *
 *     // load all result rows unpaged (as page 1)
 *     $list->loadPage();
 *
 *
 *
 * ?>
 * </code>
 *
 *
 *
 *
 */

class pagedList extends ArrayIterator
{
    /**
     * total number of items in full result set
     * @var int
     */
	protected $total = null;

    /**
     * number of available pages
     * @var int
     */
	protected $pages = null;

    /**
     * sequence number of currently loaded page (1-based)
     * @var int
     */
	protected $page = null;

    /**
     * previous page number
     * @var int
     */
	protected $previousPageNo = null;

    /**
     * next page number
     * @var int
     */
	protected $nextPageNo = null;


	/**
	 * query parts array
	 *
	 * @var array
	 */
	protected $queryParts = null;


    /**
     * number of items per page
     * @var int
     */
	protected $itemsPerPage = null;

    /**
     * objects list offset
     * @var int
     */
	protected $offset = 0;

	/**
	 * indicates whether the list is currently set up as paged
	 * @var bool
	 */
    protected $splitIntoPages = false;

    /**
     * indicates whether a result set is currently loaded
     *
     * @var bool
     */
    protected $listLoaded = false;


    /**
     * list of protected properties which are accessible from outside in read-only mode
     *
     * @var array
     */
	protected $allowGet = array('total', 'pages', 'page', 'itemsPerPage', 'previousPageNo', 'nextPageNo');
	
	/**
	 * db link to use
	 *
	 * @var string
	 */
	protected $dbLink = NULL;

	/**
	 * getter function that allows public read-only access to some protected properties
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get( $name )
	{
	    if (in_array($name, $this->allowGet))
	    {
	        return $this->$name;
	    }

		trigger_error('Undefined property: ' .  $name, E_USER_ERROR);
	}


    /**
     * construct an instance, set list params and optionally load page
     *
     * @param array $queryParts
     * @param int $itemsPerPage
     * @param int $pageNo
     * @param boolean $forceAutoLoad
     * @return void
     */
	 public function __construct($queryParts = null, $itemsPerPage = null, $pageNo = null, $forceAutoLoad = true)
	 {
        if (!$this->loadQueryParts( $queryParts ))
        {
            return;
        }

	    if (!$this->setItemsPerPage( $itemsPerPage ))
	    {
	        return;
	    }

	    if (
			(
			   ($this->splitIntoPages) // paged mode (valid $itemsPerPage > 0 given)
			   &&
			   ($pageNo) // page number given
		  	)
			||
			$forceAutoLoad // auto load
	    )
	    {
	        // load page automatically
	        $this->loadPage( $pageNo );
	    }
	}


	/**
	 * load query parts array
	 *
	 * @param array $queryParts
	 * @return bool indicates whether the operation succeeded
	 */
	public function loadQueryParts( $queryParts )
	{
        if (!is_array($queryParts))
	    {
            return false;
	    }

	    $this->queryParts = $queryParts;
	    return true;
	}

	/**
	 * set max number of items to display in one page
	 *
	 * @param int $itemsPerPage
	 * @return bool indicates whether the operation succeeded
	 */
	public function setItemsPerPage( $itemsPerPage )
	{
	    if (
	       (!isPositiveInt($itemsPerPage))
	       &&
	       (!is_null($itemsPerPage))
        )
        {
            return false;
        }
        $this->itemsPerPage = $itemsPerPage;
        $this->splitIntoPages = ($this->itemsPerPage > 0);

        return true;
	}

    /**
     * fetch rows from db according to set page params
     *
     * @param int $pageNo
     * @return array
     */
	protected function getRowsQuery( $pageNo )
    {
        $queryParts = $this->queryParts;
        if (!$queryParts)
        {
            return null;
        }

        $this->offset = $this->getRowOffset( $pageNo );
        if (isPositiveInt($this->itemsPerPage))
        {
    		$queryParts['limit']      = $this->itemsPerPage;
            $queryParts['limitStart'] = $this->offset;
		}
		$q = dbBuildQuery($queryParts);
		return $q;
	}

    /**
     * fetch rows from db according to set page params
     *
     * @param int $pageNo
     * @return array
     */
	protected function getRows( $pageNo )
    {
		$q = $this->getRowsQuery($pageNo);
		if(!$q)
		{
			return null;
		}

		$rows = array();
		$index = $this->offset + 1;

		$r = dbQuery( $q, $this->dbLink );
		while($item = $r->fetch())
		{
			$rows[$index] = $item;
			$index++;
		}

    	return $rows;
    }

    /**
     * get db row offset (first number to use in LIMIT clause) according to set page params
     *
     * @param int $pageNo
     * @return int
     */
    protected function getRowOffset($pageNo)
    {
        if (!isPositiveInt($this->itemsPerPage))
        {
            return 0;
        }
        $offset = ($this->itemsPerPage * ($pageNo - 1));
        return $offset;
    }

    /**
     * load a result page
     *
     * @param int $pageNo
     * @return bool
     */
	public function loadPage ( $pageNo )
	{
        if (!isPositiveInt($pageNo))
        {
            $pageNo = 1;
        }

        $list = $this->getPage( $pageNo );
        $this->page = $pageNo;
        
        return $this->loadItems( $list );
	}
    
    public function loadItems( $items )
    {
        parent::__construct( $items );
        $this->listLoaded = true;
        $this->loadTotals();
        return true;        
    }

	/**
	 * get a result page from db according to set page params
	 *
	 * @param int $pageNo
	 * @return array
	 */
	protected function getPage( $pageNo )
	{
        $page = $this->getRows( $pageNo );
        return $page;
	}

    /**
     * calculate and set the total number of pages and results for the currently loaded page
     *
     * @return bool
     */
	protected function loadTotals()
	{
	    // if no page is loaded, return false
	    if (!$this->listLoaded)
	    {
	        return false;
	    }
	    // if the list is not paged
	    elseif (!$this->splitIntoPages)
		{
		    // the total number of results is the number of currently loaded items
		    $totalItems = $this->count();
		    $totalPages = 1;
		}
		// if the list is paged, perform a COUNT query to get the total
		else
        {
            if(isset($this->queryParts['union']))
            {
                $queryPartsList = $this->queryParts['union'];
            }
            else
            {
                $queryPartsList = array($this->queryParts);
            }

            $totalItems = 0;
            foreach($queryPartsList as $queryParts)
            {
                // if $queryParts contain a given count_select argument, use it inside the sql COUNT()
                if(!empty($queryParts['count_select']))
                {
                    $countColumn = $queryParts['count_select'];
                }
                // if not, use * as default
                else
                {
                    $countColumn = '*';
                }

                // replace the select part of the query with the SELECT COUNT
                $queryParts['select'] = 'COUNT(' . $countColumn . ') as cnt';

                // remove order by from query parts - this avoids errors when order by column does not exist in table
                // and is created in select parts via AS keyword
                unset( $queryParts['orderBy'] );

                $q = dbBuildQuery($queryParts);
                if (!empty($queryParts['groupBy']))
                {
                    $q = 'SELECT SUM(cnt) as cnt FROM ( ' . $q . ') as tempTotal';
                }
                $totalItems += dbGetOne( $q, false, $this->dbLink );
            }

			$totalPages = ceil($totalItems / $this->itemsPerPage);

			//get next page number according to current page
			if($this->page < $totalPages)
			{
				$this->nextPageNo = $this->page + 1;
			}

			//get previous page number according to current page
			if($this->page > 1)
			{
				$this->previousPageNo = $this->page - 1;
			}
		}

        $this->total = $totalItems;
        $this->pages = $totalPages;

        return true;
	}

    /**
     * return first object, if array is not empty
     *
     * @return object
     */
	public function first()
	{
		$this->rewind();
		$obj = $this->current();
		if($obj)
		{
			return $obj;
		}
	}

	public function getAsArray()
	{
        reset ($this);
        $array = array();

        foreach ($this as $key => $item)
        {
            $array[$key] = $item;
        }

        return $array;
	}

}
?>
