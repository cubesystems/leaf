function checkObj(el){
	var checkbox = el.childNodes[0];
	if(checkbox.checked)
	{
		el.className = 'objectCheck';
		checkbox.checked = false;
	}
	else
	{
		el.className = 'objectCheck objectCheckOn';
		checkbox.checked = true;
	}
}

function get_childs(xmlhttp, params){
	if (xmlhttp.readyState == 4)
	{
		loading.style.display = "none";
		params[1].className = 'groupBox groupBoxClose';
		document.getElementById('objectli' + params[0]).innerHTML += xmlhttp.responseText;
	}
}

function request_childs(hrefObj, dialog, objId){
//	var image = document.getElementById('objectimg' + object_id);
	var target;
	if(target = document.getElementById('childs' + objId))
	{
		hrefObj.className = 'groupBox groupBoxOpen';
		openXmlHttpGet('?module=content&do=close_childs&group_id=' + objId, false);
		target.parentNode.removeChild(target);
	}
	else
	{
		loading.style.top = (findPosY(hrefObj) - 8) + "px";
		loading.style.left = (findPosX(hrefObj) - 8) + "px";
		loading.style.display = "block";
		loadXmlHttp('?module=content&do=get_childs&group_id=' + objId + '&dialog=' + dialog, get_childs, Array(objId, hrefObj));
	}
	return false;
}

var panel;
var loading;
var active_object = false;
var click_outside_skip = false;

function contentModule(){
	panel = document.getElementById('objectPanel');
	loading = document.getElementById('loading');
	document.body.onclick = clickOutside;
}


function clickOutside(){
	//alert('2');
	if(!click_outside_skip && panel)
	{
		panel.style.display = 'none';
		active_object = false;
	}
	click_outside_skip = false;
}


$(document).ready(function()
{
   contentModule();
   initRewriteVerification();
});

function initRewriteVerification()
{
    var field = jQuery('input#rewrite_name');
    if (!field.size())
    {
        return;
    }
    field[0].onfocus = field[0].onchange = verifyRewriteName;
    verifyRewriteName();
}

function verifyRewriteName()
{
    var field = jQuery('input#rewrite_name');
    if (!field.size())
    {
        return;
    }
    var wrap = field.parents('.rewriteNameWrap:first');

    var rewriteName = field[0].value;
    var url = document.location.href + '&is_rewrite_name_unique=' + encodeURIComponent(rewriteName);
	var response = openXmlHttpGet(url, true);
    if (response == '1')
    {
        wrap.removeClass('duplicateRewriteName');
    }
    else
    {
        wrap.addClass('duplicateRewriteName');
    }
    updateUrlPart();
    return;
}

var startPanelTimeoutHandle;
function start_panel( target, object_id ){
	click_outside_skip = true;

	if( active_object !== object_id )
	{
		panel.style.display = 'none';
		active_object = object_id;

		var y_pos = findPosY(target.childNodes[0]);
		var x_pos = findPosX(target.childNodes[0]);

		panel.style.top = (y_pos - 8) + "px";
		panel.style.left = (x_pos + 30) + "px";

		loading.style.top = (y_pos - 8) + "px";
		loading.style.left = (x_pos - 8) + "px";

		// adjust for scrolling
		var scrollingOffset = jQuery( '.secondaryPanel .objectsTreeWrap' )[0].scrollTop;
		jQuery( panel ).css( 'top', ( y_pos - 8 - scrollingOffset ) + 'px' );
		jQuery( loading ).css( 'top', ( y_pos - 8 - scrollingOffset ) + 'px' );

		startPanelTimeoutHandle = setTimeout(function()
		{
			loading.style.display = "block";
		}, 200);

		loadXmlHttp(target.href, open_panel);
	}
	else
	{
		active_object = false;
		var len = panel.childNodes.length;
		while (panel.hasChildNodes())
		{
			panel.removeChild(panel.firstChild);
		}
		panel.style.display = 'none';
	}
	return false;
}

function open_panel(xmlhttp){
	if (xmlhttp.readyState == 4)
	{
		if (document.importNode)
		{
			var arItem = xmlhttp.responseXML.childNodes[0];
			if (arItem && arItem.nodeType == 7) // processing instruction node, use next node (ie9)
			{
                arItem = xmlhttp.responseXML.childNodes[1];
			}
			var el = duplicate_nodes(arItem);
		}
		else
		{
			var el = document.createElement("div");
			el.innerHTML = xmlhttp.responseXML.xml;
			el = el.firstChild;
		}
		panel.innerHTML = '';
		panel.appendChild(el);
		
		clearTimeout( startPanelTimeoutHandle );
		loading.style.display = 'none';
		panel.style.display = 'block';
	}
	return false;
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
         newnode.setAttribute(attr.item(i).name, attr.item(i).nodeValue);
    }

  } else if (node.nodeType == 3 || node.nodeType == 4) {
    //alert('text mode');
    try {
      newnode = document.createTextNode(node.data);
      //alert('node added');
    } catch(e) {
       alert('failed adding node');
    }
  }

  while (node.firstChild){
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

function select_objects(){
	var selector=document.getElementById("selector");
	var all_obj=document.getElementsByTagName("input");
	for(var i=0;i<all_obj.length;i++){
		if(all_obj[i].id.substring(0,10)=='object_id_'){
			if(!selector.checked)
				all_obj[i].checked=false
			else
				all_obj[i].checked=true;
		}
	}
}

function check_selected(){
	var all_obj=document.getElementsByTagName("input");
	for(var i=0;i<all_obj.length;i++){
		if((all_obj[i].name=='objects[]' && all_obj[i].checked==true)){
			return true;
		}
	}
	return false;
}

function move_confirm(){
	if(!check_selected()){
		alert('There is no object selected!');
	}
	else{
	//else if (confirm('Are you sure to move?')){
		popup("?module=content&do=move_dialog&single_module=true",300,400, 'popup');
	}
}

function copy_confirm(){
	if(!check_selected()){
		alert('There is no object selected!');
	}
	else{
	//else if (confirm('Are you sure to move?')){
		popup("?module=content&do=copy_dialog&single_module=true",300,400, 'popup');
	}
}

function delete_confirm(obj){

    var objectIds = getSelectedObjectIds();
	if (objectIds.length < 1)
	{
		alert('There is no object selected!');
	}
	else
	{
	    var objectIdsStr = objectIds.join('|');
	    var t = new Date();
	    $url = '?module=content&do=canDeleteObjects&ids=' + objectIdsStr + '&time=' + t.getTime();
        jQuery.get($url, function(response) {
            if (response != "1")
            {
                alert('Cannot delete protected objects.');
                return;
            }

            if (confirm('Do you really want to delete?'))
        	{
                var form = document.getElementById('objectForm');
                form.action += 'delete_objects';
                form.submit();
            }
        });
	}
}

function getSelectedObjectIds()
{
    var ids = [];
    jQuery('label.objectCheck input[type=checkbox]').each(function(){
       if (this.checked)
       {
           ids[ids.length] = this.value;
       }

    });
    return ids;


}

function popup (theURL,W,H, wtype){
	if (document.all){
		var xMax = screen.width, yMax = screen.height;
	}
	else{
		if (document.layers){
			var xMax = window.outerWidth, yMax = window.outerHeight;
		}
		else{
			var xMax = 640, yMax=480;
		}
	}
	var xOffset = (xMax - W)/2, yOffset = (yMax - H)/2;
	open(theURL, wtype, 'scrollbars=yes,width='+W+',height='+H+',top='+yOffset+',left='+xOffset);
}

function move(target_id){
	var form = opener.document.getElementById('objectForm');
	var selected=false;
	var all_obj=form.getElementsByTagName("input");
	for(var i=0;i<all_obj.length;i++){
		if(all_obj[i].id.substring(0,10)=='object_id_' && all_obj[i].checked==true && all_obj[i].value==target_id){
			alert('Target error');
			return false;
		}
	}
	form.action+='move_objects&group_id='+target_id;
	form.submit();
	window.close();
	return false;
}

function copy(target_id){
	var form = opener.document.getElementById('objectForm');
	var selected=false;
	var all_obj=form.getElementsByTagName("input");
	for(var i=0;i<all_obj.length;i++){
		if(all_obj[i].id.substring(0,10)=='object_id_' && all_obj[i].checked==true && all_obj[i].value==target_id){
			alert('Target error');
			return false;
		}
	}
	form.action+='copy_objects&group_id='+target_id;
	form.submit();
	window.close();
	return false;
}

function findPosX(obj)
{
	var curleft = 0;
	if (obj.offsetParent)
	{
		while (obj.offsetParent)
		{
			curleft += obj.offsetLeft
			obj = obj.offsetParent;
		}
	}
	else if (obj.x)
		curleft += obj.x;
	return curleft;
}

function findPosY(obj)
{
	var curtop = 0;
	if (obj.offsetParent)
	{
		while (obj.offsetParent)
		{
			curtop += obj.offsetTop
			obj = obj.offsetParent;
		}
	}
	else if (obj.y)
		curtop += obj.y;
	return curtop;
}


// some nu cool code :) //

jQuery(function()
{
	// some global variables //

	var module = jQuery( '.module-content' );
	var primaryPanel = jQuery( '.module-content .primaryPanel' );
	var secondaryPanel = jQuery( '.module-content .secondaryPanel' );
	
	// scroll open tree object into view //
	
	var openTreeNode = secondaryPanel.find( '.activeNodeWrap' );
	if( openTreeNode.length && 'scrollIntoView' in openTreeNode[0] )
	{
		openTreeNode[0].scrollIntoView();
	}
	
	// toggle open tree nodes //

	jQuery( 'body' ).click(function( event )
	{
		var target = jQuery( event.target );

		if( target.hasClass( 'toggleChildren' ) )
		{
			var id = target.attr( 'data-id' );

			target.toggleClass( 'groupBoxOpen' );
			target.toggleClass( 'groupBoxClose' );

			if( target.hasClass( 'groupBoxClose' ) )
			{
				target.addClass( 'loading' );

				var url = '?module=content&do=get_childs&group_id=' + id;
				if( target.attr( 'data-dialog' ) )
				{
					url += '&dialog=' + target.attr( 'data-dialog' );
				}

				jQuery.ajax
				({
					url: url,
					success: function( html )
					{
						target.removeClass( 'loading' );
						jQuery( '#objectli' + id ).append( html );
					}
				});
			}
			else
			{
				jQuery( '#childs' + id ).remove();
				jQuery.get( '?module=content&do=close_childs&group_id=' + id );
			}

			target.blur();
		}
	});

	// related nodes //

	var contentWrap = jQuery( '.module-content .primaryPanel .content' ).parent();
	var toggleButton = contentWrap.find( '.toggleRelationPanelButton' );
	var relationsPanel = contentWrap.find( '.relationsPanel' );
	var relationsPanelBody = relationsPanel.children( '.body' );
	var catchButtonClasses = [ 'createNewGroup' ];

	var loaderHtml = '<img src="images/loader.gif" class="loader" alt="" />';
	var openObjectId = new RequestUrl().query.object_id;

	var reloadPanel = function()
	{
		jQuery.ajax
		({
			url: new RequestUrl().add({ 'do': 'relationsPanel', ajax: 1 }).getUrl(),
			success: function( html )
			{
				relationsPanelBody.html( html );
				relationsPanel.addClass( 'loaded' );
			}
		});
	}

	if( contentWrap.hasClass( 'showRelationPanel' ) )
	{
		reloadPanel();
	}

	toggleButton.click(function()
	{
		contentWrap.toggleClass( 'showRelationPanel' );
		toggleButton.blur();

		jQuery( window ).resize();

		if( contentWrap.hasClass( 'showRelationPanel' ) )
		{
			jQuery.cookie( 'showContentNodeRelationPanel', 1, { expires: 365 * 3 } );
		}
		else
		{
			jQuery.cookie( 'showContentNodeRelationPanel', null );
		}

		if( relationsPanel.hasClass( 'loaded' ) == false )
		{
			reloadPanel();
		}
	});

	relationsPanel.click(function( event )
	{
		var target = jQuery( event.target );

		for( var i = 0; i < catchButtonClasses.length; i++ )
		{
			if( target.hasClass( catchButtonClasses[ i ] ) )
			{
				switch( catchButtonClasses[ i ] )
				{
					case 'createNewGroup':
						relationsPanelBody.html( loaderHtml );

						jQuery.ajax
						({
							url: new RequestUrl().add({ 'do': 'createRelationsGroup', ajax: 1 }).getUrl(),
							type: 'post',
							data: { id: openObjectId },
							success: function( html )
							{
								relationsPanelBody.html( html );
							}
						});
					break;
				}
			}
		}
	});

	// object copying

	jQuery( '.relationsPanel .copy' ).live( 'click', function()
	{
		var button = jQuery( this );
		if( confirm( relationsPanel.attr( 'data-confirmCopy' ) ) )
		{
			button.parents( '.links:first' ).html( '<em>loading...</em>' );

			var url = new RequestUrl().add({ 'do': 'createRelatedFromCopy', ajax: 1, json: 1 });

			var post = { sourceNodeId: url.query.object_id, targetNodeId: button.attr( 'data-targetNodeId' ) };

			url.remove( 'object_id' );

			jQuery.ajax
			({
				url: url.getUrl(),
				type: 'post',
				dataType: 'json',
				data: post,
				success: function( json )
				{
					if( json.result == 'ok' )
					{
						location.href = new RequestUrl().add({ object_id: json.newNodeId }).getUrl();
					}
				}
			});
		}
	});

	var linkUpDialogRequest;
	jQuery( '.relationsPanel .linkUp' ).live( 'click', function()
	{
		var button = jQuery( this );
		// remove any left-over dialogs from previous times
		jQuery( '.linkUpDialog' ).remove();
		if( linkUpDialogRequest instanceof XMLHttpRequest )
		{
			linkUpDialogRequest.abort();
		}
		// create dialog
		var dialog = jQuery( '<div class="linkUpDialog loading"></div>' ).appendTo( 'body' );
		dialog.html( jQuery( '.linkUpDialogTemplate' ).html() );
		var offset = button.offset();
		dialog.css( 'left', offset.left - dialog.width() + 'px' );
		dialog.css( 'top', offset.top + button.height() + 'px' );

		var linkUpDialogRequest = jQuery.ajax
		({
			url: new RequestUrl().add({ 'do': 'getPossibleRelations', 'languageRootId': button.attr( 'data-languageRootId' ), ajax: 1 }).getUrl(),
			success: function( html )
			{
				dialog.find( '.dialogContent' ).html( html );
				dialog.removeClass( 'loading' );
			}
		});

		// add dialog events
		dialog.click(function( event )
		{
			var target = jQuery( event.target );

			if( target.hasClass( 'closeButton' ) )
			{
				dialog.remove();
				linkUpDialogRequest.abort();
			}



			if( target.hasClass( 'node' ) || target.parents( '.node' ).length > 0 )
			{
				var node = target;
				if( node.hasClass( 'node' ) == false )
				{
					node = target.parents( '.node' );
				}

				dialog.find( '.nodes' ).hide();
				dialog.find( '.creatingRelationMessage' ).show();

				jQuery.ajax
				({
					url: new RequestUrl().add({ 'do': 'linkUpExisting' }).getUrl(),
					type: 'post',
					data: { 'nuggetNodeId': node.attr( 'data-id' ) },
					success: function( response )
					{
						if( response == 'ok' )
						{
							dialog.remove();
							reloadPanel();
						}
					}
				});
			}

		});
	});

	// manage rewrite names's length
	var rewriteNameNode = jQuery( '.module-content .rewriteNameWrap a' );
	var ancesor = rewriteNameNode.find( '.ancestor' );
	var hidden = rewriteNameNode.find( '.hidden' );

	var recalculateRewriteUrlDisplay = function()
	{
		ancesor.show();
		hidden.hide();
		if( rewriteNameNode.width() + parseInt( rewriteNameNode.css( 'margin-left' ) ) > rewriteNameNode.parents( '.globalFieldContainer' ).width() )
		{
			ancesor.hide();
			hidden.show();
		}
	}
	recalculateRewriteUrlDisplay();

	jQuery( window ).resize( recalculateRewriteUrlDisplay );


	// section collapsing //
	jQuery( '.collapsableSection .toggleSection' ).click(function()
	{
		jQuery( this ).parents( '.collapsableSection:first' ).toggleClass( 'collapsed' );

		// trigger resize event
		jQuery( window ).resize();

		if( jQuery( this ).parents( '.collapsableSection:first' ).hasClass( 'collapsed' ) )
		{
			jQuery.cookie( 'colapseSection_globalFieldContainer', 1, { expires: 365 * 3 } );
		}
		else
		{
			jQuery.cookie( 'colapseSection_globalFieldContainer', null );
		}
	});

	// auto-suggest rewrite for new objects
	if( new RequestUrl().query.object_id == 0 )
	{
		var nameInput = jQuery( '.module-content input[name=name]' );
		var rewriteNameInput = jQuery( '.module-content input[name=rewrite_name]' );
		nameInput.change(function()
		{
			if( rewriteNameInput.hasClass( 'hasBeenChanged' ) == false )
			{
				suggest_rewrite();
			}
		});

		rewriteNameInput.change(function()
		{
			rewriteNameInput.addClass( 'hasBeenChanged' );
		});
	}

	// update image on id change
	// TODO: a proper, any type file field reload - not only images
	var updateImage = function( input, imageData )
	{
		var wrap = input.parents( '.templateField:first' );
		if( imageData && imageData.name && imageData.file )
		{
			wrap.find( '.filename' ).show();
			wrap.find( '.imageBox' ).show();
			wrap.find( '.filename' ).html( imageData.name );
			wrap.find( '.filename' ).attr( 'href', imageData.file );
			wrap.find( '.imageBox a' ).attr( 'href', imageData.file );
			wrap.find( '.imageBox img' ).attr( 'src', imageData.file );
		}
		else
		{
			wrap.find( '.filename' ).hide();
			wrap.find( '.imageBox' ).hide();
		}
	}
	module.change(function( event, imageData )
	{
		var target = jQuery( event.target );
		if( target.hasClass( 'fileobject' ) && target.prop( 'tagName' ).toLowerCase() == 'input' )
		{
			if( imageData && imageData.name && imageData.file )
			{
				updateImage( target, imageData );
			}
			else
			{
				jQuery.ajax
				({
					url: '?module=content&do=getObjectData&id=' + target.val(),
					dataType: 'json',
					success: function( json )
					{
						updateImage( target, json );
					}
				});
			}
		}
	});
});











