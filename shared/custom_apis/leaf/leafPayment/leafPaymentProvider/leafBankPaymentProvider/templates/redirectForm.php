<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title>Redirecting&hellip;</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	</head>
	<body onload="javascript:document.redirectForm.submit();">
		<form method="<?php echo htmlspecialchars($this->getMethod(), ENT_QUOTES); ?>" action="<?php echo htmlspecialchars($this->getAction(), ENT_QUOTES); ?>" id="redirectForm" name="redirectForm">
			<?php foreach ($fieldList as $name): if (get($data,$name,false)): ?>
			<input type="hidden" name="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>" value="<?php echo htmlspecialchars($data[$name], ENT_QUOTES); ?>" />
			<?php endif; endforeach; ?>
			<noscript>
				<button type="submit">Click if not redirected automaticaly</button>
			</noscript>
		</form>
	</body>
</html>