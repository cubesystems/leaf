// constructor
function RequestUrl( params )
{
	if( params === undefined )
	{
		params = {};
	}
	var keepCurrentQuery = true;
	if( params === false || params.baseUrl !== undefined )
	{
		keepCurrentQuery = false;
	}
	if( typeof params == 'string' )
	{
		params = { baseUrl: params };
	}
	// setup members
	this.path = '';
	this.query = {};
	// get url
	var baseUrl = params.baseUrl || location.href;
	// remove anchor
	baseUrl = baseUrl.split( '#' ).shift();
	// split url
	var urlParts = baseUrl.split( '?' );
	this.path = urlParts.shift();
	if( keepCurrentQuery && urlParts.length > 0 )
	{
		var queryParts = urlParts.shift().split( '&' );
		for( var i = 0; i < queryParts.length; i++ )
		{
			var variable = queryParts[ i ].split( '=' );
			var name = variable.shift();
			
			if( variable.length > 0 )
			{
				var value = decodeURIComponent( variable.shift() );
			}
			else
			{
				var value = '';
			}
			
			if( unescape( name ).substr( unescape( name ).length - 2, 2 ) == '[]' )
			{
				name = unescape( name );
			}
			
			if( name.substr( name.length - 2, 2 ) == '[]' )
			{
				name = name.substr( 0, name.length - 2 );
				if( this.query[ name ] === undefined || !(this.query[ name ] instanceof Array) )
				{
					this.query[ name ] = [];
				}
				this.query[ name ].push( value );
			}
			else
			{
				this.query[ name ] = value;
			}
		}
	}
	if( params.keep !== undefined && params.keep instanceof Array )
	{
		var filteredQuery = {};
		for( var i = 0; i < params.keep.length; i++ )
		{
			if( this.query[ params.keep[i] ] !== undefined )
			{
				filteredQuery[ params.keep[i] ] = this.query[ params.keep[i] ];
			}
		}
		this.query = filteredQuery;
	}
}

RequestUrl.prototype.add = function( params, value )
{
	if( params instanceof Array )
	{
		for( var i = 0; i < params.length; i++ )
		{
			if( params[ i ].name !== undefined && params[ i ].value !== undefined )
			{
				this.query[ params[ i ].name ] = params[ i ].value;
			}
		}
	}
	else if( params instanceof Object )
	{
		for( var i in params )
		{
			this.query[ i ] = params[ i ];
		}
	}
	else if( typeof params == 'string' )
	{
		if( value === undefined )
		{
			var temp = new RequestUrl( '?' + params );
			for( var i in temp.query )
			{
				this.query[ i ] = temp.query[i];
			}
		}
		else
		{
			this.query[ params ] = value;
		}
	}
	return this;
}

RequestUrl.prototype.removeAll = function( preserveParams )
{
    for( var i in this.query )
    {
        if( preserveParams === undefined || jQuery.inArray(i, preserveParams) == -1 )
        {
            this.remove(i);
        }
    }
	return this;
}

RequestUrl.prototype.remove = function( name )
{
	delete this.query[ name ];
	return this;
}

RequestUrl.prototype.get = function( name )
{
	if( this.query[ name ] !== undefined )
	{
		return this.query[ name ];
	}
	return null;
}

RequestUrl.prototype.getUrl = function()
{
	var query = '';
	var isFirst = true;
	for( var i in this.query )
	{
		if( !isFirst )
		{
			query += '&';
		}
		else
		{
			isFirst = false;
		}
		if( this.query[ i ] instanceof Array )
		{
            for (var j=0; j < this.query[ i ].length; j++)
            {
                this.query[ i ][j] = encodeURIComponent( this.query[ i ][ j ] );
            }
			query += i + '[]=' + this.query[ i ].join( '&' + i + '[]=' );
		}
		else
		{
			query += i + '=' + encodeURIComponent( this.query[ i ] ) ;
		}
	}
	
	return this.path + '?' + query;
}
