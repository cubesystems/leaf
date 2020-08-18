(function() {
	tinymce.create('tinymce.plugins.leafEmbedPlugin', 
	{
		init : function( editor, url ) 
		{
			// Register commands
			editor.addCommand( 'mceLeafEmbed', function() 
			{
				// store selection bookmark
				var bookmark = tinymce.isIE ? editor.selection.getBookmark() : null;
				// open dialog
				if( editor.leafEmbedPlugin )
				{
					var plugin = editor.leafEmbedPlugin;
				}
				else
				{
					var plugin = editor.leafEmbedPlugin = new richtextEmbedDialog( editor );
				}
				if( !plugin )
				{
					return;
				}
				// open dialog
				plugin.openDialog( bookmark );
			});
			// Register buttons
			editor.addButton( 'embed', 
			{
				title : 'Embed Media',
				cmd : 'mceLeafEmbed'
			});
			editor.onNodeChange.add(function( ed, cm, node ) 
			{
				cm.setActive( 'embed', node.nodeName == 'IMG' && jQuery( node ).hasClass( 'embedObject' ) );
			});
			editor.onInit.add(function()
			{
				editor.dom.loadCSS( url + "/leafEmbed.css" );
			});
		},
		
		getInfo : function() 
		{
			return {
				longname:  'Leaf Embed Plugin',
				author:    'Cube-Media',
				authorurl: 'http://www.cube.lv',
				version:   tinymce.majorVersion + '.' + tinymce.minorVersion
			};
		}
	});
	
	// Register plugin
	tinymce.PluginManager.add( 'leafEmbed', tinymce.plugins.leafEmbedPlugin );
})();