/**
 * @author Cube-Media, Jânis Grigaïuns
 * @copyright Copyright © 2008, Cube-Media, All rights reserved.
 */

(function() {
	tinymce.create('tinymce.plugins.LeafLinkPlugin', {
		init : function(ed, url) {
			this.editor = ed;

			// Register commands
			ed.addCommand('mceLeafLink', function() {
				var se = ed.selection;

				// No selection and not in link
				if (se.isCollapsed() && !ed.dom.getParent(se.getNode(), 'A'))
					return;

				ed.windowManager.open({
					file : url + '/link.htm',
					width : 650 + parseInt(ed.getLang('advlink.delta_width', 0)),
					height : 400 + parseInt(ed.getLang('advlink.delta_height', 0)),
					inline : 1
				}, {
					plugin_url : url
				});
			});

			// Register buttons
			ed.addButton('link', {
				title : 'advlink.link_desc',
				cmd : 'mceLeafLink'
			});

			ed.addShortcut('ctrl+k', 'advlink.advlink_desc', 'mceLeafLink');

			ed.onNodeChange.add(function(ed, cm, n, co) {
				cm.setDisabled('link', co && n.nodeName != 'A');
				cm.setActive('link', n.nodeName == 'A' && !n.name);
			});
		},

		getInfo : function() {
			return {
				longname : 'Leaf link',
				author : 'Cube-Media, Jânis Grigaïuns',
				authorurl : 'http://www.cube.lv',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('leaflink', tinymce.plugins.LeafLinkPlugin);
})();