/*** some old inline ... ***/

    function removeEntry(target){

		var tableEl = target.parentNode.parentNode;
		var parent = target.parentNode.parentNode.parentNode;
		parent.removeChild(tableEl);
	}
	function new_variable()
	{
		//jQuery('table.hidden').removeClass('hidden');
		var clone = jQuery('#cloneme').clone(true);

		clone.find('input').each(function(e)
		{
		    jQuery(this).attr('name', jQuery(this).attr('name').substring(1).concat('[]'));
		});
		clone.removeAttr('id');
		jQuery('table.aliasesTable').append(clone);
		jQuery(clone).find('td.translation_name input[type=text]').focus();
	}

	function importTranslations(button)
	{
		var fileInput = document.getElementById('translations_file');
		if (
			(!fileInput)
			||
			(!fileInput.value)
		)
		{
			return false;
		}
		return true;
	}

var toggleAliasesCookie = function(cookieName, on, onValue)
{
    if (on)
    {
        var value = (typeof onValue == 'undefined') ? 1 : onValue;

        jQuery.cookie( cookieName, value, { path: '/', expires: 365 } );
    }
    else
    {
        jQuery.cookie( cookieName, null, { path: '/' } );
    }
}


jQuery(function( $ )
{
    /*** variables ***/

	var module  = jQuery( '.module-aliases' );
	var content = module.find( '.content' );

    /*** deleting ***/

	var deleteForm = module.find( '.deleteForm' );
	deleteForm.submit(function()
	{
		return confirm( deleteForm.attr( 'data-confirmation' ) );
	});

    /*** importing ***/

	jQuery('.file').change(function()
	{
      var form = jQuery( this ).parent( 'form' );
      var translationsForm = jQuery( '#editForm' );
      
      form.submit(function(){
        form.append( '<div class="editFormContainer" style="display:none"></div>' );
        var editFormContainer = jQuery( '.editFormContainer' );
        
        translationsForm.find( 'input' ).each(function(){
          var input = jQuery( this );
          editFormContainer.append( '<input type="' + input.attr( 'type' ) + '" name="' + input.attr( 'name' ) + '" value="' + input.val() + '" />' );
        });
        
        editFormContainer.find('input[name=action]').remove();
      });
      
      form.submit();
  });

	jQuery( '.autofocus' ).focus();

	// drag and drop importing

	var dropContainer = module.find( '.primaryPanel .content' );

	if( dropContainer.length > 0 )
	{
		dropContainer = dropContainer[0];
        
        if (typeof dropContainer.addEventListener != "undefined")        
        {
            
            dropContainer.addEventListener("dragenter", function(event)
            {
                event.stopPropagation();
                event.preventDefault();
                jQuery( dropContainer ).addClass( 'dragInProgress' );
            }, false);
            dropContainer.addEventListener("dragend", function(event)
            {
                jQuery( dropContainer ).removeClass( 'dragInProgress' );
            }, false);
            dropContainer.addEventListener("dragover", function(event){event.stopPropagation();event.preventDefault();}, false);
            dropContainer.addEventListener( 'drop', function( event )
            {
                event.stopPropagation();
                event.preventDefault();

                jQuery( dropContainer ).removeClass( 'dragInProgress' );

                var transfer = event.dataTransfer;
                var files = transfer.files;

                for( var i = 0; i < files.length; i++ )
                {
                    var file      = files[i];
                    var reader    = new FileReader();
                    var formdata  = new FormData();

                    reader.readAsDataURL( file );  

                    if( formdata ) 
                    {  
                        formdata.append( "translations_file", file );  


                        var translationsForm = jQuery( '#editForm' );

                        translationsForm.find( 'input' ).each(function()
                        {
                            var input = jQuery( this );

                            if( input.attr( 'name' ) != 'action' )
                            {
                                formdata.append( input.attr( 'name' ), input.val() );  
                            }
                        });

                        jQuery.ajax(
                        {
                            url         : new RequestUrl().add({ 'do': 'import', ajax: 1 }).getUrl(),
                            type        : "POST",
                            data        : formdata,
                            processData : false,
                            contentType : false,
                            success     : function( html )
                            {
                                var variablesContainer = jQuery('#variables') || null;
                                var itable = jQuery( html ).find('#variables').html();
                                if ( itable.length > 0 )
                                {
                                    variablesContainer.html( itable );
                                }
                            }
                        });
                    }
                }
            }, false );
        }
		
	}



	// search results highlight

	var hash = jQuery(location).attr('hash');
    jQuery(hash).addClass('aliasSearchAutoSeleted');

	/*** filtering ***/

	var filter 			   = module.find( '.filter' );
	var items 			   = module.find( '.groupList > li' );
	var menuContainer 	   = module.find( '.menuContainer' );
	var groupContainer 	   = module.find( '.groupContainer' );
	var groupShowAll       = groupContainer.find( '.showAll' );
	var aliasContainer 	   = module.find( '.aliasContainer' );
    var searchForm  = jQuery( 'form.searchForm' );
	var searchXhr;
	var searchTimeout;
	var previousSearch;
	var groupLimitNo = 5;
	var aliasLimitNo = 10;


    var getVisibleCategories = function( asString )
    {
        var visibleCategories = [];
        jQuery('.categoryFilterBox input.categoryFilter:checked').each(function(){
            visibleCategories[visibleCategories.length] = jQuery(this).val();
        });

        if (asString)
        {
            visibleCategories = visibleCategories.join('|');
        }

        return visibleCategories;
    }

    var getIncompleteValue = function()
    {
        var incompleteValue = null;

        if (!jQuery('#onlyIncomplete').attr('checked'))
        {
            return null;
        }

        var checkedLanguageCodes = [];
        var allChecked = true;
        var noneChecked = true;
        jQuery('input.incompleteLanguage').each(function(){
            var ch = jQuery(this);
            if (ch.attr('checked'))
            {
                noneChecked = false;
                checkedLanguageCodes[checkedLanguageCodes.length] = ch.val();
            }
            else
            {
                allChecked = false;
            }
        });

        if (allChecked)
        {
            // show groups with any incomplete languages
            incompleteValue = 'any';
        }
        else if (noneChecked)
        {
            // show no groups
            incompleteValue = 'none';
        }
        else
        {
            // show selected groups
            incompleteValue = checkedLanguageCodes.join('|');
        }

        return incompleteValue;
    }


    jQuery('#onlyIncomplete').click(function()
    {
        var isChecked = jQuery(this).attr('checked');

        toggleAliasesCookie( 'incompleteAliases', isChecked, getIncompleteValue());

        jQuery( '.incompleteLanguages' ).toggleClass('disabled', !isChecked);
        jQuery( '.incompleteLanguages' ).find('input').attr('disabled', !isChecked);

        filterGroups(filter.val(), filter.val() != '');
    });

    jQuery('.incompleteFilterButton').click(function()
    {
        var incompleteValue = getIncompleteValue();
        if (!incompleteValue)
        {
            jQuery('#onlyIncomplete').attr('checked', true);
            // jQuery('.incompleteFilterButtonWrap').removeClass('incompleteInactive');
            filterGroups(filter.val(), filter.val() != '');
        }

        jQuery('.incompleteLanguages').toggle();
        jQuery( this ).blur();

    });


    jQuery('input.incompleteLanguage').click(function()
    {
        filterGroups(filter.val(), filter.val() != '');

        var cookieName = 'ignoreIncomleteLanguageId-' + jQuery(this).val();

        toggleAliasesCookie( cookieName, !jQuery(this).attr('checked'));

        toggleAliasesCookie( 'incompleteAliases', jQuery('#onlyIncomplete').attr('checked'), getIncompleteValue() );

        updateGroupFilterMessage();
    });

    jQuery('input.categoryFilter').click(function()
    {
        filterGroups(filter.val(), filter.val() != '');
        var cookieName = 'visibleAliasGroupCategories';
        jQuery.cookie( cookieName, getVisibleCategories(true),  { path: '/', expires: 365 } );
    });

    function updateGroupFilterMessage()
    {
        var checkedLanguageCodes = [];
        var allChecked = true;
        var noneChecked = true;
        jQuery('input.incompleteLanguage').each(function(){
            var ch = jQuery(this);
            if (ch.attr('checked'))
            {
                noneChecked = false;
                checkedLanguageCodes[checkedLanguageCodes.length] = ch.val().toUpperCase();
            }
            else
            {
                allChecked = false;
            }
        });
        var text = '';
        if (allChecked)
        {
            text = jQuery('#incompleteLanguageAnyLabel').val();
        }
        else if (noneChecked)
        {
            text = '-';
        }
        else
        {
            text = checkedLanguageCodes.join(', ');
        }

        jQuery('.incompleteFilterButtonLabel').text(text);
    }

	var filterGroups = function( value, limit )
	{
		var expression = new RegExp( value, 'i' );
		var visible = 0;
		var filterValue = encodeURIComponent(filter.val());

		var visibleCategories = getVisibleCategories(false);

        var incompleteValue = getIncompleteValue();
        var incompleteValueParts = [];
        if (incompleteValue)
        {
            incompleteValueParts = incompleteValue.split('|');
        }

        groupContainer.find('a').each(function()
		{
		    var addUrlParams =
		    {
                'filter' : filterValue
		    };

		    if (
                (jQuery(this).is('#groupList a'))
                &&
                (!jQuery(this).is('#groupList .showAll a'))
            )
		    {
		        addUrlParams.incomplete = (incompleteValue) ? incompleteValue : '';
		    }

		    if (visibleCategories.length != jQuery('input.categoryFilter').size())
		    {
		        // not all categories selected
		        addUrlParams.categories = getVisibleCategories(true);
		    }

            var url = new RequestUrl ( jQuery(this).attr('href') ).remove('categories').add(addUrlParams);
            jQuery(this).attr('href', url.getUrl());
		});


		for( var i = 0; i < items.length; i++ )
		{

			var item = jQuery( items[i] );

			if( !item.is( '.showAll' ) )
			{
				var name    = item.find( '.name' ).html() || '';
				var context = item.find( '.context' ).html() || '';

				var matchesFilter = (
				    (name.search( expression ) !== -1)
				    ||
				    (context.search( expression ) !== -1)
                );

                var matchesCategoryFilter = false;
                for (var j=0; j < visibleCategories.length; j++)
                {
                    if (item.hasClass('category-'.concat(visibleCategories[j])))
                    {
                        matchesCategoryFilter = true;
                    }
                }

                var matchesIncompleteRules = true;
                if (incompleteValue)
                {
                    if (incompleteValue == 'any')
                    {
                        matchesIncompleteRules = item.hasClass('incomplete');
                    }
                    else if (incompleteValue == 'none')
                    {
                        matchesIncompleteRules = false;
                    }
                    else
                    {
                        matchesIncompleteRules = false;
                        for (var j=0; j < incompleteValueParts.length; j++)
                        {
                            if (item.hasClass('incomplete-'.concat(incompleteValueParts[j])))
                            {
                                matchesIncompleteRules = true;
                            }
                        }

                    }
                }

                if ((matchesFilter) && (matchesCategoryFilter) && (matchesIncompleteRules))
                {
                    if ((visible < groupLimitNo) || !limit)
                    {
                        item.show();
                    }
                    else
                    {
                        item.hide();
                    }
                    visible++;
                }
                else
                {
                    item.hide();
                }
			}
		}
		items.filter( '.selected.active:not(:visible)' ).removeClass( 'active' ).removeClass( 'selected' );

		if( visible > groupLimitNo && limit )
		{
			groupShowAll.show();
			groupShowAll.find( '.a' ).html( groupShowAll.attr( 'data-alias' ).replace( /\{no\}/, visible - groupLimitNo ) );
		}
		else
		{
			groupShowAll.hide();
		}

		groupContainer.show();
	}

	var searchAliases = function( value, limit, highlight )
	{
		clearTimeout( searchTimeout );
		if( searchXhr )
		{
			searchXhr.abort();
		}
		var filterValue = encodeURIComponent( value );
        var incompleteValue = getIncompleteValue();

		searchTimeout = setTimeout(function()
		{
			var url = new RequestUrl().add({ 'do': 'searchAliases', filter: filter.val(), ajax: 1 });
			if( limit )
			{
				url.add({ limit: aliasLimitNo });
			}
			if( highlight )
			{
				url.add({ highlight: aliasLimitNo + 1 });
			}
            searchForm.addClass( 'loading' );
			searchXhr = jQuery.ajax
			({
				url: url.getUrl(),
				success: function( html )
				{
                    searchForm.removeClass( 'loading' );
					if( searchXhr.readyState !== 4 )
					{
						return;
					}
					aliasContainer.html( html );


					// add filter param to all translation links
        			aliasContainer.find('a').each(function()
        			{
        			    var urlParts = jQuery(this).attr('href').split('#');
        			    var fragmentPart = (urlParts.length > 1) ? '#'.concat(urlParts[1]) : '';


            		    var addUrlParams =
            		    {
                            'filter' : filterValue,
                            'incomplete' : (incompleteValue) ? incompleteValue : ''
            		    };

                        var url = new RequestUrl( urlParts[0]  ).add(addUrlParams);
                        jQuery(this).attr('href', url.getUrl().concat(fragmentPart) );
        			});


        			// highlight active translation in search results
                    var hash = jQuery(location).attr('hash');
                    if (hash && hash.match(/^\#translation\_\d+$/))
                    {
                        var resultId = hash.replace(/\_/g, '_search_result_');
                        jQuery(resultId).addClass('active');
                    }
				}
			});
		}, 100);
	}

	groupShowAll.click(function()
	{
		var current = items.filter( '.selected:visible' ).prevAll( ':visible:first' );
		filterGroups( filter.val(), false );
		var next = current.nextAll( ':visible:first' );
		current.removeClass( 'active selected' );
		next.addClass( 'active selected' );
	});

	jQuery( '.module-aliases .menuContainer .aliasContainer .showAll' ).live( 'click', function()
	{
		var items = menuContainer.find( 'li:visible' );
		var current = items.filter( '.selected:visible' ).prevAll( ':visible:first' );
		searchAliases( filter.val(), false, current.length > 0 );
	});

	var updateFilter = function()
	{
		if( filter.val() != previousSearch )
		{
			previousSearch = filter.val();

			setTimeout(function()
			{
				// groups
				filterGroups( filter.val(), filter.val() != '' );
			},0);

			// aliases
      searchAliases( filter.val(), true );
		}
	}

	filter.keyup(function( event )
	{
        updateFilter();
	});

	updateFilter();

	filter.keydown(function( event )
    {
		var items = menuContainer.find( 'li:visible' );
		switch( event.keyCode )
		{
			case 38: // arrow up
				var current = items.filter( '.selected:visible' );
				var prev;
				if( current.length == 0 )
				{
					prev = items.filter( ':visible:first' );
				}
				else
				{
					prev = current.prevAll( ':visible:first' );
				}
				if( prev.length == 0 && current.parents( '.aliasContainer' ).length > 0 )
				{
					prev = current.parents( '.aliasContainer' ).prev().find( 'li:visible:last' );
				}
				current.removeClass( 'selected' );
				current.removeClass( 'active' );
				prev.addClass( 'selected' );
				prev.addClass( 'active' );
			break;
			case 40: // arrow down
				var current = items.filter( '.selected:visible' );
				var next;
				if( current.length == 0 )
				{
					next = items.filter( ':visible:first' );
				}
				else
				{
					next = current.nextAll( ':visible:first' );
				}
				if( next.length == 0 && current.parents( '.groupContainer' ).length > 0 )
				{
					next = current.parents( '.groupContainer' ).next().find( 'li:visible:first' );
				}
				current.removeClass( 'selected' );
				current.removeClass( 'active' );
				next.addClass( 'selected' );
				next.addClass( 'active' );
			break;
			case 13: // enter
				var selected = items.filter( '.selected:visible' );
				if( selected.length > 0 )
				{
					event.preventDefault();
					if( selected.find('a').length )
					{
						location.href = selected.find('a').attr( 'href' );
					}
					selected.find('.a').click();
				}
			break;
		}
	});

    /*** adjust alias table head cell width ***/

	var headCells = jQuery( '.module-aliases .aliasesTableHead .th' );
	//console.log( headCells );
	var adjustHeadCellWidth = function()
	{
        var row = jQuery( '.module-aliases .aliasesTable thead tr:first th' );

		for( var i = 0; i < headCells.length; i++ )
		{
			jQuery( headCells[i] ).width( jQuery( row[i] ).outerWidth() - 2 );
		}
	};
	adjustHeadCellWidth();
	jQuery( window ).resize( adjustHeadCellWidth );

	/*** visible languages ***/

	jQuery( '.visibleLanguagesSwitch button' ).click(function()
	{
		jQuery( this ).siblings( 'ul' ).toggle();
		jQuery( this ).blur();
	});

	jQuery( 'body' ).click(function( event )
	{
		var target = jQuery( event.target );
		if( target.parents( '.visibleLanguagesSwitch' ).length == 0 )
		{
			jQuery( '.visibleLanguagesSwitch ul' ).hide();
		}
	});

	jQuery( '.visibleLanguagesSwitch ul input' ).change(function()
	{
		var checkbox = jQuery( this );
		if( checkbox.attr( 'checked' ) )
		{
			jQuery( '.primaryPanel .languageId-' + checkbox.val() ).show();
		}
		else
		{
			jQuery( '.primaryPanel .languageId-' + checkbox.val() ).hide();
		}
		toggleAliasesCookie( 'hideLanguageId-' + checkbox.val() , !checkbox.attr( 'checked' ) );

		adjustHeadCellWidth();
	});

	/*** keyboard shortcuts ***/

	jQuery( 'html' ).keyup(function( event )
	{
		// alt + n or alt + [arrow down]
		if( event.altKey && ( event.keyCode === 78 || event.keyCode === 40 ) )
		{
			new_variable();

			event.preventDefault();
			event.stopPropagation();
		}
	});


	/* machine translation */
    var machineTranslation = {};

    if (typeof googleTranslateApiKey != 'undefined' && googleTranslateApiKey)
    {
        // general translation code for google translate

        window.googleTranslate = new function()
        {
            var script = this;
            this.urlLengthLimit = 1984; // must be less than 2K characters
            this.sessions = {};
            this.baseUrl = 'https://www.googleapis.com/language/translate/v2?key='.concat(googleTranslateApiKey);


            this.getFreeSessionKey = function()
            {
                var safety = 100;
                do
                {
                    var key = 's_'.concat((new Date()).valueOf().toString().concat('_',Math.ceil(Math.random()*1000)));
                    safety--;
                }
                while (
                    (!(typeof this.sessions[key] == 'undefined'))
                    &&
                    (safety > 0)
                );
                return key;
            }

            var googleTranslateSession = function(key, sourceLang, targetLang, data, callback)
            {
                this.key = key;
                this.sourceLang = sourceLang;
                this.targetLang = targetLang;
                this.requests   = {};
                this.numberOfRequests = 0;

                // session base url includes languages
                this.baseUrl = script.baseUrl.concat('&source=', encodeURIComponent(sourceLang), '&target=', encodeURIComponent(targetLang));

                this.log = function()
                {
                    if (
                        (typeof window.console == 'undefined')
                        ||
                        (typeof window.console.log == 'undefined')
                    )
                    {
                        return null;
                    }
                    return window.console.log.apply(this, arguments);
                }

                this.callback = function( dataItems )
                {
                    // call external callback function, passing back out to it the original dataItems array with translated texts added
                    callback( dataItems, this.sourceLang, this.targetLang );
                }

                this.getNumberOfRequests = function()
                {
                    var i = 0;
                    for (var key in this.requests)
                    {
                        i++;
                    }
                    return i;
                }

                this.getFreeRequestKey = function()
                {
                    var safety = 100;
                    do
                    {
                        var key = 'r_'.concat((new Date()).valueOf().toString().concat('_',Math.ceil(Math.random()*1000)));
                        safety--;
                    }
                    while (
                        (!(typeof this.requests[key] == 'undefined'))
                        &&
                        (safety > 0)
                    );
                    return key;
                }


                // split session into multiple requests if the url gets too long for one request
                var requestsData = [];

                var requestUrl = this.baseUrl;
                var requestDataItems = [];
                var numberOfTextsInRequest = 0;

                // callback url parts are something like this:
                // &callback=googleTranslate.sessions.s_1300384221046_678.requests.r_1300384221047_762.callback
                var callbackUrlPartLength = 100; // assume sufficient length

                for (var i = 0; i < data.length; i++)
                {
                    var dataItem = data[i];
                    if (typeof dataItem.text == 'undefined')
                    {
                        continue;
                    }

                    var urlPart = '&q='.concat(encodeURIComponent(dataItem.text));

                    if (requestUrl.length + urlPart.length + callbackUrlPartLength > script.urlLengthLimit)
                    {
                        // cannot add urlPart, makes url too long
                        // add already created url to url array, create new url

                        if (numberOfTextsInRequest < 1)
                        {
                            // no texts have yet been added to this url,
                            // but the first one already makes it too long.
                            // skip this dataItem (ignore)
                            this.log('googleTranslate: Skipping data item', dataItem);
                            continue;
                        }

                        requestsData[requestsData.length] =
                        {
                            'url'  : requestUrl,
                            'data' : requestDataItems
                        };

                        requestUrl = this.baseUrl;
                        requestDataItems = [];
                        numberOfTextsInRequest = 0;

                        if (requestUrl.length + urlPart.length + callbackUrlPartLength > script.urlLengthLimit)
                        {
                            // still too long
                            // skip this dataItem
                            this.log('googleTranslate: Skipping data item', dataItem);
                            continue;
                        }
                    }

                    requestUrl = requestUrl.concat(urlPart);
                    requestDataItems[requestDataItems.length] = dataItem;
                    numberOfTextsInRequest++;
                }

                // add last url
                if (numberOfTextsInRequest > 0)
                {
                    requestsData[requestsData.length] =
                    {
                        'url'  : requestUrl,
                        'data' : requestDataItems
                    };
                }


                // create request objects
                var googleTranslateRequest = function(session, key, url, dataItems)
                {
                    this.session = session;
                    this.key = key;
                    this.url = url;
                    this.dataItems = dataItems;
                    this.script = null;
                    this.response = null;

                    this.callback = function( response )
                    {
                        // this gets called by google for each request, passing their json object as an argument
                        this.response = response;
                        if (
                            (typeof response == 'undefined')
                            ||
                            (typeof response.data == 'undefined')
                            ||
                            (typeof response.data.translations == 'undefined')
                            ||
                            (typeof response.data.translations.length == 'undefined')
                        )
                        {
                            this.session.log( 'googleTranslate: google returned error', this );
                        }
                        else
                        {
                            for (var i = 0; i < response.data.translations.length; i++)
                            {
                                // add each returned translation to its matching data item
                                if (
                                    (typeof this.dataItems[i] != 'undefined')
                                    &&
                                    (typeof response.data.translations[i].translatedText != 'undefined')
                                )
                                {
                                    this.dataItems[i].translatedText = response.data.translations[i].translatedText;
                                }
                            }

                            // pass dataItems back to the external callback
                            this.session.callback( dataItems );
                        }

                        this.remove();
                    }

                    this.send = function()
                    {
                        var callbackFunction = 'googleTranslate.sessions.'.concat(this.session.key, '.requests.', this.key, '.callback');
                        var scriptUrl = this.url.concat('&callback=',encodeURIComponent(callbackFunction));

                        this.script = document.createElement('script');
                        this.script.type = 'text/javascript';
                        this.script.src = scriptUrl;

                        // add script to head, it gets loaded and executed
                        document.getElementsByTagName('head')[0].appendChild( this.script );
                    }


                    this.remove = function()
                    {
                        // destroys own script node and removes request from session
                        this.script.parentNode.removeChild(this.script);
                        return this.session.removeRequest(this.key);
                    }


                }

                this.initRequest = function(url, dataItems )
                {
                    var key = this.getFreeRequestKey();
                    if (!key)
                    {
                        return false;
                    }
                    this.requests[key] = new googleTranslateRequest(this, key, url, dataItems);

                    return this.requests[key];
                }

                for (var i = 0; i < requestsData.length; i++ )
                {
                    var req = this.initRequest(requestsData[i].url, requestsData[i].data );
                }

                // send all requests
                for (requestKey in this.requests)
                {
                    this.requests[requestKey].send();
                }

                this.removeRequest = function( requestKey )
                {
                    delete this.requests[requestKey];
                    // if this is the last request in session, destroys the session also
                    if (this.getNumberOfRequests() == 0)
                    {
                        this.remove();
                    }
                    return;
                }

                this.remove = function()
                {
                    delete googleTranslate.sessions[this.key];
                }

                return;

            }

            this.translate = function( sourceLang, targetLang, data, callback )
            {
                return this.initSession( sourceLang, targetLang, data, callback );
            }

            this.initSession = function( sourceLang, targetLang, data, callback )
            {
                var key = this.getFreeSessionKey();
                if (!key)
                {
                    return false;
                }

                this.sessions[key] = new googleTranslateSession(key, sourceLang, targetLang, data, callback);

                return this.sessions[key];
            }

            this.getNumberOfSessions = function()
            {
                var i = 0;
                for (var key in this.sessions)
                {
                    i++;
                }
                return i;
            }
        }




        // specific alias module code for machine translation
        machineTranslation.sourceSelectorOpen = false;

        // source selector functions (when google button is clicked and there are multiple possible source languages available)
        machineTranslation.openSourceSelector = function(possibleSourceLanguages, targetInput)
        {
            var selector = jQuery('#machineTranslationSelector');

            // hide all language options
            selector.find('.sourceLanguage').hide();

            // show possible language options
            for (var i=0; i < possibleSourceLanguages.length; i++)
            {
                var possibleSource = selector.find('.sourceLanguage-'.concat(possibleSourceLanguages[i]));
                possibleSource.find('button').unbind('click');
                possibleSource.find('button').click(function()
                {
                    // when language is clicked in source selector, find the input for that language in same row and call translation
                    var languageCode = jQuery(this).val();
                    var sourceInput = targetInput.parents('tr:first').find('td.languageCode-'.concat(languageCode,' input.translationText:first'));
                    machineTranslation.translateInput( sourceInput, targetInput);
                });
                possibleSource.show();
            }

            machineTranslation.sourceSelectorOpen = true;
            selector.show();
        }

        machineTranslation.closeSourceSelector = function()
        {
            machineTranslation.sourceSelectorOpen = false;
            jQuery('#machineTranslationSelector').hide();
        }

    	jQuery( 'body' ).click(function( event )
    	{
            // close source selector when clicked outside
    		var target = jQuery( event.target );
    		if (
                (!target.is('#machineTranslationButtonBox'))
                &&
                (target.parents( '#machineTranslationButtonBox' ).length == 0)
            )
    		{
    			machineTranslation.closeSourceSelector();
    		}
    	});

        machineTranslation.translateInput = function( sourceInput, targetInput )
        {
            var sourceLanguage = sourceInput.parents('td:first').attr('data-languageCode');
            var targetLanguage = targetInput.parents('td:first').attr('data-languageCode');

            if ((!sourceLanguage) || (!targetLanguage))
            {
                return;
            }

            var translationData =
            [
                {
                    'text'        : sourceInput.val(),
                    'targetInput' : targetInput
                }
            ];

            googleTranslate.translate(sourceLanguage, targetLanguage, translationData, machineTranslation.translateCallback );
            return;
        }


        machineTranslation.getTranslationNodes = function ( event )
        {
            // locates corresponding translation cell, input and wrapper from event
            var source = jQuery(event.target);
            var tag = event.target.tagName.toLowerCase();
            var input;
            var cell;

            if (tag == 'input')
            {
                input = source;
            }
            else if (tag == 'td')
            {
                cell = source;
            }

            if (!cell)
            {
                cell = source.parents('td:first');
            }

            if (!input)
            {
                input = cell.find('input:first');
            }

            var result =
            {
                'cell'  : cell,
                'input' : input,
                'wrap'  : jQuery(input[0].parentNode)
            };

            return result;
        }

    	// translation button stuff (the google button inside the translation cells)
        machineTranslation.getTranslationButtonBox = function()
        {
            var buttonBox = jQuery('#machineTranslationButtonBox');
            if (buttonBox.size() == 0)
            {
                return null;
            }
            if (!buttonBox.data('initialized'))
            {
                machineTranslation.initTranslationButtonBox( buttonBox );
            }
            return buttonBox;
        }

    	machineTranslation.initTranslationButtonBox = function( buttonBox )
    	{
    	    var button = buttonBox.find('#machineTranslationButton');
            var box = buttonBox;


            button.click(function()
            {
                // if source selector is open, close it on second click
                if (machineTranslation.sourceSelectorOpen)
                {
                    machineTranslation.closeSourceSelector();
                    return;
                }

                //
                var targetInput = box.data('targetInput');
                if (!targetInput)
                {
                    return;
                }

                // possible source inputs (non-empty inputs in other cells in same row)
                var nonEmptySiblingInputs = box.data('targetInput').parents('td:first').siblings('.translationCell:visible').find('input.translationText[value!=""]');
                var numberOfUsableSources = nonEmptySiblingInputs.size();
                if (numberOfUsableSources < 1)
                {
                    return; // not available
                }
                else if (numberOfUsableSources == 1)
                {
                    // only one source input, proceed to translation
                    var sourceInput = nonEmptySiblingInputs.first();
                    machineTranslation.translateInput ( sourceInput, targetInput );
                    // blur button
                    jQuery(this).blur();
                }
                else if (numberOfUsableSources > 1)
                {
                    // more than one source input, show source language selector
                    var possibleSourceLanguages = [];
                    nonEmptySiblingInputs.each(function()
                    {
                        possibleSourceLanguages[possibleSourceLanguages.length] = jQuery(this).parents('td:first').attr('data-languageCode');
                    });

                    machineTranslation.openSourceSelector( possibleSourceLanguages, targetInput);
                }
            });

            box.data('initialized', true);
    	}
		
		var headerButtons = {};
		
		jQuery( '.module-aliases .machineColumnTranslationButtonBox' ).live( 'init', function( event )
		{
			var box = jQuery( this );
			var th = box.parents( '.th:first' );
			headerButtons[ th.attr( 'data-languageCode' ) ] = box;
		});
		
		var updateHeaderButtons = function( languageCode )
		{
			for( var i in headerButtons )
			{
				if( i !== languageCode )
				{
					headerButtons[ i ].css( 'display', '' );
				}
			}
			if( languageCode && headerButtons[ languageCode ] )
			{
				headerButtons[ languageCode ].show();
			}
		}
		
		machineTranslation.showTranslationButtonTimeout = null;
		machineTranslation.hideColumnButtonTimeout = null;
		
        machineTranslation.showTranslationButton = function( event )
        {
			// show button when hovering the cell

			if (machineTranslation.sourceSelectorOpen)
			{
				// ignore mouseover calls to show button in other cells while the source selector is open
				return;
			}

			var nodes = machineTranslation.getTranslationNodes( event );
			if (nodes.cell.find('#machineTranslationButtonBox').size() > 0)
			{
				// button is already there
				return;
			}
			
			clearTimeout( machineTranslation.showTranslationButtonTimeout );
			clearTimeout( machineTranslation.hideColumnButtonTimeout );

			var nonEmptySiblingInputs = nodes.cell.siblings('.translationCell:visible').find('input.translationText[value!=""]');
			var usableSourceSiblingsExist = (nonEmptySiblingInputs.size() > 0);

			var buttonBox = machineTranslation.getTranslationButtonBox();
			if (!buttonBox)
			{
				return;
			}

			var iconClass = (usableSourceSiblingsExist) ? 'normal' : 'notAvailable';
			var title = buttonBox.find('button img.'.concat(iconClass)).attr('title');
			buttonBox.find('button').attr('title', title);
			buttonBox.toggleClass('machineTranslationNotAvailable', !usableSourceSiblingsExist );
			buttonBox.data('targetInput', nodes.input);
			buttonBox.css( 'opacity', '0' );
			buttonBox.appendTo(nodes.wrap);
			buttonBox.show();
			
			machineTranslation.showTranslationButtonTimeout = setTimeout(function()
			{
				buttonBox.css( 'opacity', '1' );
				// show column button
				var languageCode = buttonBox.parents( 'td:first' ).attr( 'data-languageCode' );
				updateHeaderButtons( languageCode );
			}, 100);
			
        }

        machineTranslation.hideTranslationButton = function()
        {

            // hides google button
            // gets called when mouse leaves the cell or the translation is complete

            if (machineTranslation.sourceSelectorOpen)
            {
                // ignore hide requests while the source selector is open (do not hide the button)
                return;
            }

            var buttonBox = machineTranslation.getTranslationButtonBox();
            if (!buttonBox)
            {
                return;
            }
			
			clearTimeout( machineTranslation.showTranslationButtonTimeout );
			
            buttonBox.hide().appendTo('.content:first');
			
			machineTranslation.hideColumnButtonTimeout = setTimeout(function()
			{
				updateHeaderButtons();
			},0);
        }

        machineTranslation.translateCallback = function( dataItems, sourceLanguage, targetLanguage )
        {
            // this will get called from googleTranslateSession after receiving translations from google
            for ( var i = 0; i < dataItems.length; i++ )
            {
                jQuery(dataItems[i].targetInput).val( dataItems[i].translatedText);
                machineTranslation.markMachineTranslation( dataItems[i].targetInput, true );
            }

            machineTranslation.closeSourceSelector();
            machineTranslation.hideTranslationButton();

            machineTranslation.closeColumnSourceSelectors();
        }


        machineTranslation.init = function( selector )
        {
            jQuery(selector).mouseover(  machineTranslation.showTranslationButton );
            jQuery(selector).mouseleave( machineTranslation.hideTranslationButton );
        }

        machineTranslation.init ( '.aliasesTable tbody .translationCell' );




        // full column translation
        machineTranslation.columnSourceSelectorOpen = false;

        // source selector functions (when google button is clicked and there are multiple possible source languages available)
        machineTranslation.openColumnSourceSelector = function(possibleSourceLanguages, targetHeaderCell)
        {
            var selector = targetHeaderCell.find('.machineColumnTranslationSelector');

            // hide all language options
            selector.find('.sourceLanguage').hide();

            // show possible language options
            for (var i=0; i < possibleSourceLanguages.length; i++)
            {
                var possibleSource = selector.find('.sourceLanguage-'.concat(possibleSourceLanguages[i]));
                possibleSource.find('button').click(function()
                {
                    // when language is clicked in source selector,
                    // find the header cell for that language
                    var languageCode = jQuery(this).val();

                    var sourceHeaderCell = targetHeaderCell.parents('.aliasesTableHead:first').find('.languageHeader.languageCode-'.concat(languageCode));
                    machineTranslation.translateColumn( sourceHeaderCell, targetHeaderCell);

                });
                possibleSource.show();
            }

            machineTranslation.columnSourceSelectorOpen = true;
            selector.show();

        }

        machineTranslation.closeColumnSourceSelectors = function()
        {
            machineTranslation.columnSourceSelectorOpen = false;
            jQuery('.machineColumnTranslationSelector').hide();
        }

    	jQuery( 'body' ).click(function( event )
    	{
            // close source selector when clicked outside
    		var target = jQuery( event.target );
    		if (
                (!target.is('.machineColumnTranslationButtonBox'))
                &&
                (target.parents('.machineColumnTranslationButtonBox').size() == 0)
            )
    		{
    			machineTranslation.closeColumnSourceSelectors();
    		}
    	});


        machineTranslation.translateColumn = function( sourceHeaderCell, targetHeaderCell )
        {
            var sourceLanguage = sourceHeaderCell.attr('data-languageCode');
            var targetLanguage = targetHeaderCell.attr('data-languageCode');

            if ((!sourceLanguage) || (!targetLanguage))
            {
                return;
            }

            var translationData = [];

            // collect translation data for all empty inputs in given column
            jQuery('.aliasesTable tbody .translationCell.languageCode-'.concat(targetLanguage)).each(function(){

                var targetInput = jQuery(this).find('input.translationText');
                if (targetInput.val() != '')
                {
                    return;
                }

                // find source language input in same row
                sourceInput = targetInput.parents('tr:first').find('td.translationCell.languageCode-'.concat(sourceLanguage, ' input.translationText'));

                if (sourceInput.size() != 1)
                {
                    return;
                }

                var text = sourceInput.val();
                if (text == '')
                {
                    return;
                }


                var translationItem =
                {
                    'text'        : text,
                    'targetInput' : targetInput
                };

                translationData[translationData.length] = translationItem;

            });


            if (translationData.length < 1)
            {
                machineTranslation.closeColumnSourceSelectors();
                return;
            }

            googleTranslate.translate(sourceLanguage, targetLanguage, translationData, machineTranslation.translateCallback );
            return;

        }

        machineTranslation.initColumnTranslation = function()
        {
            var template = jQuery('.machineColumnTranslationButtonBoxTemplate:first');

            // setup column translation button
            template.find('button').click(function()
            {
                // if source selector is open, close it on second click
                if (machineTranslation.columnSourceSelectorOpen)
                {
                    machineTranslation.closeColumnSourceSelectors();
                    return;
                }

                var headerCell = jQuery(this).parents('.languageHeader:first');
                if (!headerCell)
                {
                    return;
                }


                // possible source languages (other columns)
                var possibleSourceHeaders = headerCell.siblings('.languageHeader:visible');
                var numberOfUsableSources = possibleSourceHeaders.size();

                if (numberOfUsableSources < 1)
                {
                    return; // not available
                }
                else if (numberOfUsableSources == 1)
                {
                    // only one other language column available, proceed to mass translation
                    machineTranslation.translateColumn( possibleSourceHeaders.first() , headerCell );
                    // blur button
                    jQuery(this).blur();
                }
                else if (numberOfUsableSources > 1)
                {
                    // more than one column available, show source selector
                    var possibleSourceLanguages = [];
                    possibleSourceHeaders.each(function()
                    {
                        possibleSourceLanguages[possibleSourceLanguages.length] = jQuery(this).attr('data-languageCode');
                    });

                    machineTranslation.openColumnSourceSelector( possibleSourceLanguages, headerCell);
                }
            });

            jQuery('.aliasesTableHead .languageHeader').each(function()
            {
                var cell = jQuery(this);
                var buttonBox = template.clone(true).removeClass('machineColumnTranslationButtonBoxTemplate').appendTo(cell);
				buttonBox.trigger( 'init' );
			});
        }



        machineTranslation.initColumnTranslation();
    }

    // this needs to be done even if google api key is not set

    // field marking
    machineTranslation.markMachineTranslation = function( node, machine, updateColumnStatus)
    {
        var textInput, cell, machineInput;
        if (node.is('input.translationText'))
        {
            textInput = node;
            cell = node.parents('td:first');
        }
        else if (node.is('td.translationCell'))
        {
            cell = node;
            textInput = cell.find('input.translationText');
        }
        if (!cell || !textInput)
        {
            return;
        }
        machineInput = cell.find('input.machineTranslation');
        machineInput.val( (machine) ? '1' : '0');
        cell.toggleClass('machineTranslated', machine);

        if (
            (typeof updateColumnStatus == 'undefined')
            ||
            (updateColumnStatus)
        )
        {
            machineTranslation.updateColumnStatus(cell);
        }

        return;
    }

    jQuery('input.translationText').change(function(){
        machineTranslation.markMachineTranslation( jQuery(this), false );
    });

    machineTranslation.updateColumnStatus = function( cell )
    {
        // passed cell can be header cell or data cell

        // locate header cell by language code
        var langCode = cell.attr('data-languageCode');
        var headerCell = jQuery('.aliasesTableHead').find('.languageHeader.languageCode-'.concat(langCode));

        // check whether the language column has any unapproved machine translated entries
        var hasMachineTranslations = (jQuery('td.translationCell.languageCode-'.concat(langCode)).has('input.machineTranslation[value="1"]').size() > 0);
        headerCell.toggleClass( 'hasMachineTranslations', hasMachineTranslations);

    }

    jQuery('button.approveColumn').click(function()
    {
        var headerCell = jQuery(this).parents('.languageHeader:first');
        var langCode = headerCell.attr('data-languageCode');

        // iterate through all machine translated inputs in given language
        jQuery('td.translationCell.languageCode-'.concat(langCode)).has('input.machineTranslation[value="1"]').each(function()
        {
            machineTranslation.markMachineTranslation( jQuery(this), false, false );
        });

        machineTranslation.updateColumnStatus( headerCell );
    });

    machineTranslation.updateAllColumnStatuses = function()
    {

        jQuery('.aliasesTableHead .languageHeader').each(function(){
            machineTranslation.updateColumnStatus( jQuery(this) );
        });
    }



});


jQuery('#translations').load(function()
{
    var itable = jQuery( this ).contents().find('#variables').html();
    
	if( 
		(itable)
		&&
		(itable.length > 0 )
	)
    {
      var contentContainer = jQuery('#editForm').find('#variables');
      contentContainer.html( itable );
    }
    
	  jQuery( '#translations_file' ).val('');
	  jQuery('.editFormContainer').remove();
});

jQuery('.groupFilterButton').click(function(){

    jQuery(this).blur();

    var filter = jQuery('.groupFilter');

    // move filter out of footer
    if (filter.is('.footer .groupFilter'))
    {
        filter.appendTo('#objectsSidebar');
    }

    filter.toggleClass('groupFilterOpen');
    jQuery(this).toggleClass('active');


    if (filter.hasClass('groupFilterOpen'))
    {
        var menuBottom = filter.outerHeight() + parseInt(filter.css('bottom'));
    }
    else
    {
        var menuBottom = jQuery('.footer').outerHeight();
    }
    toggleAliasesCookie( 'aliasGroupFilterOpen', filter.hasClass('groupFilterOpen'));

    jQuery('.secondaryPanel .menuContainer').css('bottom', menuBottom);

});



jQuery(document).ready(function(){

    if (jQuery.cookie('aliasGroupFilterOpen'))
    {
        jQuery('.groupFilterButton').click();
    }

});


