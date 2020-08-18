<?php
class contentNodeGroup extends leafBaseObject
{
	const tableName = 'contentNodeGroups';
	
	protected $template, $add_date;
	
	// properties needed to trigger custom save method
	// ...
	
	protected $fieldsDefinition = array
	(
		'template' => array( 'not_empty' => true ),
		
		// relations, this information is stored in other tables
		// ...
	);
	
	public static function _autoload( $className )
    {
		parent::_autoload( $className );
        
		$_tableDefsStr = array
		(
	        self::tableName => array
	        (
	            'fields' =>
	            '
	                id             int auto_increment
					template       varchar(255)
					add_date       datetime
	            '
	            ,
	            'indexes' => 'primary id'
	        ),
	    );
	   
	   dbRegisterRawTableDefs( $_tableDefsStr );
    }
	

	// modes
	/* protected $currentMode = 'default';
	protected $modes = array
	(
		'default' => array
		(
			'campaignId', 'siteOwnerId', 'status', 'calculatedPrice', 'amountDiscount', 'companyDiscount', 'extraDiscount', 
			'priceAfterDiscounts', 'finalOffer',  
		),
	); */
	
	// relations
	/* protected $campaign, $siteOwner;
	protected $objectRelations = array
	(
		'campaign'  => array( 'key' => 'campaignId',  'object' => 'jeCampaign' ),
		'siteOwner' => array( 'key' => 'siteOwnerId', 'object' => 'jeCompany'  ),
	); */
	
	/********************* get methods *********************/
	
	/* public function getSelectedItems()
	{
		if( $this->hasInCache( 'mediaPlanItems' ) == false )
		{
			$this->storeInCache('mediaPlanItems', jeMediaPlanItem::getCollection( array( 'mediaPlanId' => $this->id ) ) );
		}
		return $this->cache['mediaPlanItems'];
	} */
	
	/********************* boolean methods *********************/
	
	/********************* internal updating methods *********************/
	
	/* public function variablesSave( $variables, $fieldsDefinition = NULL, $mode = false )
	{
		// pre-process
		// ...
		//-- pre-process
		
		$result = parent::variablesSave( $variables, $fieldsDefinition, $mode );
		
		// post-process
		// ...
		//-- post-process
		
		return $result;
	} */
	
	public function delete()
	{
		dbQuery( 'DELETE FROM ' . contentNodeRelation::tableName . ' WHERE groupId="' . $this->id . '"' );
		return parent::delete();
	}
	
	/********************* static methods *********************/
	
	
	/********************* collection related methods *********************/
	
	public static function getCollection( $params = array(), $itemsPerPage = NULL, $pageNo = NULL )
	{
		$queryParts['select'][]   = 't.*';
		$queryParts['from'][]     =  '`' . self::getClassTable( __CLASS__ ) . '` AS `t`';
		$queryParts['orderBy'][]  = 't.id ASC';

	    if( is_array( $params ) )
	    {
			
	    }

		return new pagedObjectCollection( __CLASS__, $queryParts, $itemsPerPage, $pageNo );
	}
}
?>