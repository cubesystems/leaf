(function() {
	tinymce.create('tinymce.plugins.leafImagePlugin', 
	{
		init : function( editor, url ) 
		{
			// Register commands
			editor.addCommand( 'mceLeafImage', function() 
			{
				// Internal image object like a flash placeholder
				if( editor.dom.getAttrib( editor.selection.getNode(), 'class').indexOf('mceItem') != -1 )
				{
					return;
				}
				// store selection bookmark
				var bookmark = tinymce.isIE ? editor.selection.getBookmark() : null;
				// open dialog
				if( editor.leafImagePlugin )
				{
					var plugin = new richtextImageDialog( editor );
				}
				else
				{
					var plugin = editor.leafImagePlugin = new richtextImageDialog( editor );
				}
				if( !plugin )
				{
					return;
				}
				// open dialog
				plugin.openDialog( bookmark );
			});
			// Register buttons
			editor.addButton( 'image', 
			{
				title : 'Insert Image',
				cmd : 'mceLeafImage'
			});
		},
		
		getInfo : function() 
		{
			return {
				longname:  'Leaf Image Plugin',
				author:    'Cube-Media',
				authorurl: 'http://www.cube.lv',
				version:   tinymce.majorVersion + '.' + tinymce.minorVersion
			};
		}
	});
	
	// Register plugin
	tinymce.PluginManager.add( 'leafImage', tinymce.plugins.leafImagePlugin );
})();