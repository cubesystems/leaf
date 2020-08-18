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
function smartPopup (target,W,H, wtype){
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
	open(target.href, wtype, 'scrollbars=yes,width='+W+',height='+H+',top='+yOffset+',left='+xOffset);
	return false;
}

