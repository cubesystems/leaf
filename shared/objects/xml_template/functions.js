Array.prototype.in_array = function(what) {
    for (var a = 0; a < this.length; a++) {
        if (this[a] == what) {
            return a;
        } else if (this[a] instanceof Array) {
            return this[a].in_array(what);
        }
    }
    return false;
}


function add_array_item(array_name) {
    var arField = document.getElementById(array_name);
    var arrayContainer = $('#' + array_name);

    var maxNr = 0;
    arrayContainer.children()
        .each(function() {
        var t = $(this);
        var classes = t.attr('class')
            .split(' ');

        for (var i = 0, itemClass; itemClass = classes[i]; ++i) {
            if (itemClass.substr(0, 6) == 'itemNr') {
                var itemNr = parseInt(itemClass.substring(6));
                if (itemNr > maxNr) {
                    maxNr = itemNr;
                }
            }
        }
    });

    ++maxNr;

    var location = document.location.href.split('#')
        .shift();
    var url = location + '&get_array_node=1&nextNr=' + maxNr + '&item_name=' + array_name + '&template=' + document.getElementById('template')
        .value;
    var params = Array(array_name, url);
    loadXmlHttp(url, loadArrayNode, params);
}

function loadArrayNode(xmlhttp, params) {
    if (xmlhttp.readyState == 4) {
        var arField = jQuery('#' + params[0]);
        var el = jQuery(xmlhttp.responseText)
            .clone();
        jQuery(arField)
            .append(el);


        initGoogleMapFields();

        if (typeof tinyMCEConfig != "undefined") {
            var textareas = el.find('textarea.' + tinyMCEConfig.editor_selector);

            var elList = new Array();
            for (var s = 0; s < textareas.length; s++) {
                elList.push(textareas[s].id);
            }
            var elements = elList.join(',');
            if (elements) {
                tinyMCEConfig.mode = 'exact';
                tinyMCEConfig.elements = elements;
                tinyMCE.init(tinyMCEConfig);
            }
        }

        // trigger change event
        el.parents('.array:first')
            .trigger('change');
    }
}

function launchJavascriptFromXML(responseXML) {
    var scripts = responseXML.getElementsByTagName('script');
    var js = '';
    for (var s = 0; s < scripts.length; s++) {
        if (scripts[s].childNodes[0].nodeValue == null) continue;
        js += scripts[s].childNodes[0].nodeValue;
    }
    eval(js);
}

function duplicate_nodes(node) {
    // get our node type name and list of children
    // loop through all the nodes and recreate them in our document
    //alert('calling duplicate_nodes: ' + node.nodeName + ' type: ' + node.nodeType);
    var newnode;
    if (node.nodeType == 1) {
        //alert('element mode');
        newnode = document.createElement(node.nodeName);
        //alert('node added');
        newnode.nodeValue = node.nodeValue
        //test for attributes
        var attr = node.attributes;
        var n_attr = attr.length
        for (i = 0; i < n_attr; i++) {
            newnode.setAttribute(attr.item(i)
                .name, attr.item(i)
                .nodeValue);
        }

    } else if (node.nodeType == 3 || node.nodeType == 4) {
        //alert('text mode');
        try {
            newnode = document.createTextNode(node.data);
            //alert('node added');
        } catch (e) {
            alert('failed adding node');
        }
    } else if (node.nodeType == 8) // comment node
    {
        newnode = node.cloneNode(true);
    }

    while (node.firstChild) {
        if (newnode) {
            //alert('node has children');
            var childNode = duplicate_nodes(node.firstChild);
            //alert ('back from recursive call with:' + childNode.nodeName);
            newnode.appendChild(childNode);
            node.removeChild(node.firstChild);
        }
    }
    return newnode;
}

function change_template(target) {
    location.href = query_string + 'template=' + target.value;
}

function loadSnapshot(target) {
    location.href = query_string.replace(/snapshot=\d+&?/gi, '') + 'snapshot=' + target.value;
}

function item_delete_href(node) {
    var contentNode = node.parentNode.parentNode;
    if (typeof(tinyMCEConfig) != 'undefined') {
        //remove
        var textareas = jQuery(contentNode)
            .find('textarea.' + tinyMCEConfig.editor_selector);
        for (var s = 0; s < textareas.length; s++) {
            tinyMCE.execCommand('mceRemoveControl', false, textareas[s].id);
        }
    }
    var parent = jQuery(node)
        .parents('.array:first')
    contentNode.parentNode.removeChild(contentNode);
    // trigger change event
    parent.trigger('change');
}

function check() {
    var form = document.getElementById('editForm');
    var name = document.getElementById('name');
    if (!name.value) {
        alert('Please input text name!');
        name.focus();
        return false;
    } else {
        return true;
    }
}

function correctObjectLink(target, resetField) {
    if (resetField == undefined) {
        resetField = true;
    }
    if (target.value == '' || parseInt(target.value) == target.value) {
        return false;
    }
    var pattern = /(object_id=)(\d*)/;
    var result = target.value.match(pattern);
    if (result && result.length == 3) {
        target.value = result[2];
        updateObjectFieldPreview(target);
    } else if (resetField) {
        var pattern = /(\d*)/;
        var result = target.value.match(pattern);
        if (result && result.length == 2) {
            target.value = result[1];
        } else {
            target.value = '';
        }
    }
    //
}


/* array item move functions */
divswaper = new groupPropertyBlock;

function moveit(target, down) {
    divswaper.element = target.parentNode;
    divswaper.moveProperty(down);
    return false;
}

function groupPropertyBlock() {}

groupPropertyBlock.prototype.moveProperty = function(down) {
    var node2 = this.findAdjacentProperty(down);
    if (node2 == null) {
        return; // can't move
    }

    var node1 = this.element;
    var parent = node1.parentNode;

    if (down) {
        parent.insertBefore(node2, node1);
    } else {
        parent.insertBefore(node1, node2);
    }

}

groupPropertyBlock.prototype.findAdjacentProperty = function(down) {
    var node = null;
    var sourceNode = this.element;
    do {
        if (down) {
            node = sourceNode.nextSibling;
        } else {
            node = sourceNode.previousSibling;
        }
        sourceNode = node;
    }
    while (
    (node != null) && (node.className != 'arrayItem'));
    return node;
}

function updateLinkedFieldsSwitch(fileField, fieldId, valueFieldId) {
    var switchId = fieldId.concat('_update_linked_switch');
    var switchBox = document.getElementById(switchId);
    if (!switchBox) {
        return;
    }
    var switchFieldId = fieldId.concat('_update_linked');
    var switchField = document.getElementById(switchFieldId)
    if (!switchField) {
        return;
    }

    var valueField = document.getElementById(valueFieldId);
    if (!valueField) {
        return;
    }

    // show update switch only if the file field is not empty
    var showSwitch = !! fileField.value;
    var switchChecked = false;
    if (showSwitch) {
        // auto-check the update field if the value field is currently empty
        var switchChecked = ((!valueField.value) || (valueField.value == '0')) ? true : false;

    }

    switchField.checked = (switchChecked) ? true : false;
    switchBox.style.display = (showSwitch) ? 'inline' : 'none';

}

function updateObjectFieldPreview(input) {
    var value = input.value;

    var previewBox = findObjectPreviewBox(input);
    if (!previewBox) {
        return;
    }

    var previewContent = '';
    if (
    (value.match(/^[0-9]+/)) && (value > 0)) {
        previewContent = getObjectFieldPreviewHtml(value);
    }

    previewBox.innerHTML = previewContent;
}

function getObjectFieldPreviewHtml(value) {
    var l = document.location;
    var queryString = '?module=content&do=getObjectPreviewField&object_id=' + value;
    var hostname = l.hostname;
    if (l.port) {
        hostname += ':' + l.port;
    }
    var url = l.protocol.concat('//', hostname, l.pathname, queryString);
    var response = openXmlHttpGet(url, true);
    return response;

}

function findObjectPreviewBox(input) {
    var parent = input.parentNode;
    var elements = parent.getElementsByTagName('span');

    for (var i = 0; i < elements.length; i++) {
        if (elements[i].className == 'objectPreview') {
            return elements[i];
        }
    }
    return null;

}

function setGoogleMapPoint(fieldId, lat, lng) {
    var field = document.getElementById(fieldId);
    if (!field) {
        return false;
    }
    field.value = "".concat(lat, ';', lng);

    var previewStr = "".concat(lat, ' / ', lng);
    var previewField = document.getElementById('googleMapCoordsPreview_'.concat(fieldId));
    if (!previewField) {
        return;
    }
    previewField.innerHTML = previewStr;
}

function initGoogleMapFields() {
    if (
    (typeof googleMapPointFields == 'undefined') || (googleMapPointFields.length < 1)) {
        return;
    }

    for (var i = 0; i < googleMapPointFields.length; i++) {
        var f = googleMapPointFields[i];
        newMapInstance(f);
    }

    googleMapPointFields = []; // reset array to empty
}

function newMapInstance(f) {
    var lat = f.vars.lat != undefined ? f.vars.lat : f.vars.centerLat;
    var lng = f.vars.lng != undefined ? f.vars.lng : f.vars.centerLng;

    var latlng = new google.maps.LatLng(lat, lng);
    var mapOptions = {
        zoom: f.vars.defaultZoom,
        center: latlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        disableDefaultUI: true,
        zoomControl: true
    }

    var map = new google.maps.Map(document.getElementById('mapCanvas_' + f.mapCanvas), mapOptions);

    var marker = new google.maps.Marker({
        map: map,
        draggable: true,
        cursor: 'pointer'
    });
    
    var geocoder = new google.maps.Geocoder();

    if (f.vars.lat != undefined && f.vars.lng != undefined) {
        marker.setPosition(latlng);
    }

    google.maps.event.addListener(marker, 'dragend', function(e) {
        var position = e.latLng;
        marker.setPosition(position);
        setGoogleMapPoint(f.mapCanvas, position.lat(), position.lng());
    });

    google.maps.event.addListener(map, 'click', function(e) {
        var position = e.latLng;
        marker.setMap(map);
        marker.setPosition(position);

        setGoogleMapPoint(f.mapCanvas, position.lat(), position.lng());
    });

    var clearButton = document.getElementById('googleMapClearButton_'.concat(f.mapCanvas));
    var searchInput = document.getElementById('googleMapSearch_'.concat(f.mapCanvas));
    
    $(searchInput).keypress(function(e){
        if(e.keyCode == 13)
        {
            var address = $(this).val();
            if(address){
                geocoder.geocode( { 'address': address}, function(results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        var position = results[0].geometry.location;
                        map.setCenter(position);
                        marker.setMap(map);
                        marker.setPosition(position);
                        setGoogleMapPoint(f.mapCanvas, position.lat(), position.lng());
                    } else {
                        alert('Not found. Reason: ' + status);
                    }
                });
            }        
            return false;
        }
    });     
    
    if (!clearButton.addEventListener) {
        clearButton.attachEvent('onclick', function() {
            clearMap(f.mapCanvas, marker)
        });
    } else {
        clearButton.addEventListener('click', function() {
            clearMap(f.mapCanvas, marker)
        }, false);
    }
}

function clearMap(fieldId, marker) {
    marker.setMap(null);
    var field = document.getElementById(fieldId);
    if (!field) {
        return;
    }
    field.value = "";

    var previewField = document.getElementById('googleMapCoordsPreview_'.concat(fieldId));
    if (!previewField) {
        return;
    }
    previewField.innerHTML = "- / -";
}
jQuery(document)
    .ready(function() {
    initGoogleMapFields();
});