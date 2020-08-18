<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title>Redirecting&hellip;</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	</head>
	<body onload="javascript:document.redirectForm.submit();">
		<form method="post" action="<?php echo htmlspecialchars($this->getAction(), ENT_QUOTES); ?>" id="redirectForm" name="redirectForm">
			<input type="hidden" name="cmd" value="_s-xclick" />
			<input type="hidden" name="notify_url" value="<?php echo htmlspecialchars($this->getListenerUrl(), ENT_QUOTES); ?>" />
			<input type="hidden" name="encrypted" value="<?php echo htmlspecialchars($data['encryptedData'], ENT_QUOTES); ?>" />
			<noscript>
				<button type="submit">Click if not redirected automaticaly</button>
			</noscript>
		</form>
	</body>
</html>