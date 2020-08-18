function setGoogleMapPoint( fieldId, lat, lng )
{
    var field = document.getElementById(fieldId);
    if (!field)
    {
        return false;
    }
    
    field.value = ''.concat(lat, ';', lng);
    updateGeoPointPreview( fieldId, lat, lng);
}

function updateGeoPointPreview( fieldId, lat, lng )
{
    if (!lat) { lat = '-'; }
    if (!lng) { lng = '-'; }
    var previewStr = ''.concat(lat, ' / ', lng);
    var previewField = document.getElementById('geoPointCoordsPreview_'.concat(fieldId));
    if (!previewField)
    {
        return;
    }
    previewField.innerHTML = previewStr;
}


function initGeoPoint( geoPointBoxNode )
{
    if (typeof google == 'undefined')
    {
        return;
    }
    
	if (typeof geoPointBoxNode == 'undefined')
	{
		geoPointBoxNode = '.geoPointBox:not(.no-auto-init)';
	}
    
	jQuery( geoPointBoxNode ).each(function()
	{
        var point = 
        {
            box    : jQuery(this)
        };
        point.box.data('geoPoint', point);
        
        point.mapBox     = point.box.find('.geoPointMapBox').first();
        point.previewBox = point.box.find('.preview');
        
        // collect fields
        point.fields   = point.box.find('.geoPointValue');
        point.latField = point.fields.filter('.gePointLat').first();
        point.lngField = point.fields.filter('.gePointLng').first();
        

        // collect map options
        var centerLat   = point.box.attr('data-centerLat');
        centerLat = (centerLat) ? parseFloat( centerLat ) : null; 

        var centerLng   = point.box.attr('data-centerLng');
        centerLng = (centerLng) ? parseFloat( centerLng ) : null; 
        
        var defaultZoom = point.box.attr('data-defaultZoom');
        defaultZoom = (defaultZoom) ? defaultZoom * 1 : 13; 
        
        var mapOptions = 
        {
            center:    new google.maps.LatLng(centerLat, centerLng),
            zoom:      defaultZoom,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };        
        
        point.map = new google.maps.Map(point.mapBox[0], mapOptions);

        point.useSearch = (point.box.attr('data-useSearch') == '1');

        point.marker = null;

        point.centerMap = function( lat, lng, zoom )
        {
            point.map.setCenter( new google.maps.LatLng( lat, lng )  );
            if (zoom)
            {
                point.map.setZoom( zoom );
            }
        }

        point.hasValue = function()
        {
            return (point.latField.val() && point.lngField.val());
        }

        point.setValue = function( lat, lng )
        {
            point.latField.val(lat).triggerHandler('change');
            point.lngField.val(lng).triggerHandler('change');                        
        }

        point.clearValue = function()
        {
            point.setValue('', '');
        }

        point.updateValuePreview = function()
        {
            var lat = point.latField.val();
            var lng = point.lngField.val();
            if (!lat) { lat = '-'; }
            if (!lng) { lng = '-'; }
            var previewStr = ''.concat(lat, ' / ', lng);
            point.previewBox.text( previewStr );
        }
        
        point.updateMapFromValue = function()
        {
            var lat = parseFloat( point.latField.val() );
            var lng = parseFloat( point.lngField.val() );

            if (lat && lng)
            {
                // create/move marker
                var coords = new google.maps.LatLng( lat, lng );                            
                if (!point.marker)
                {
                    point.marker = new google.maps.Marker(
                    {
                        position: coords,
                        map:      point.map,
                        title:    'Drag to change position',
                        draggable: true
                    });
                    
                    point.box.addClass('hasMarker');

                    google.maps.event.addListener(point.marker, 'dragend', function(e)
                    {
                        point.latField.val(e.latLng.lat()).trigger('change', { noRedraw : true } );
                        point.lngField.val(e.latLng.lng()).trigger('change', { noRedraw : true } );

                    });
                }
                else
                {
                    point.marker.setPosition( coords );
                }  

                point.centerMap( point.marker.getPosition().lat(), point.marker.getPosition().lng() );
            }
            else
            {
                // clear/remove marker
                if (point.marker)
                {        
                    point.marker.setMap( null );
                }
                jQuery(point.box).removeClass('hasMarker');
                point.marker = null;
            }

            return;
        };

        point.fields.bind('change', function(e, options)
        {
            if (!options)
            {
                options = {};
            }
            
            point.updateValuePreview();
            if (!options.noRedraw)
            {
                point.updateMapFromValue(); 
            }
        });

        point.box.find('.setPoint').click(function()
        {
            var mapCenter = point.map.getCenter();
            point.setValue( mapCenter.lat(), mapCenter.lng() );

        });

        point.box.find('.clearPoint').click(function()
        {
            point.clearValue();                        
        });                 
        
        if (point.useSearch)
        {
            point.searchInput = point.box.find('.searchBox .search');
            
            point.searchInput.on('keypress', function(e)
            {
                if (e.which == 13)
                {
                    e.preventDefault();
                }
            });
            point.searchBox = new google.maps.places.SearchBox( point.searchInput[0] );

            google.maps.event.addListener(point.searchBox, 'places_changed', function() 
            {
                var places = point.searchBox.getPlaces();
                if (!places || !places[0])
                {
                    return;
                }
                point.setValue( places[0].geometry.location.lat(), places[0].geometry.location.lng() );
            });
            
        
            point.fields.bind('error', function(e, options)
            {
                point.searchInput.focus();
            });            
        }        

        point.updateMapFromValue(); 

        point.box.addClass('initialized');
    });
      
      
    return;


}

jQuery( document ).ready( function()
{
    // initialize old flash fields from global array
    if (typeof geoPointFields != 'undefined')
    {
        for (var i=0; i<geoPointFields.length; i++)
        {        
            var point = geoPointFields[i];
            var version = point.version;            
            if (version != 'flash')
            {
                continue;
            }
            var so = new SWFObject
            (
               point.args.swfUrl,
               point.args.id,
               point.args.width,
               point.args.height,
               point.args.version,
               point.args.bgColor
            );
            for (key in point.params)
            {
               so.addParam(key, point.params[key]);
            }
            for (key in point.vars)
            {
               so.addVariable(key, point.vars[key]);
            }

            so.write(point.target);             
        }
    }


    initGeoPoint();
});