<?php

	function dumpException($e)
	{
		ob_end_clean();
		
		header('Content-type: text/html; charset=utf-8', true);
		header("HTTP/1.0 500 Internal Server Error", true, 500);
		
		$dev = defined('DEVELOPMENT_ENVIRONMENT') ? DEVELOPMENT_ENVIRONMENT : false;

		$title = $dev ? $e->getMessage() : "Error found" ;
		$msg = $dev ? (
					"Exception: " . get_class($e) . "\n" .
					"Error code: " . $e->getCode() . "\n" .
					"File: " . $e->getFile() . "\n" .
					"Line: " . $e->getLine() .
					"\n\n===============[Trace]===============\n\n" .
					$e->getTraceAsString() )
					: "Please contact the system administrator" ;
		
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Error Report</title>
<style type="text/css">
.title {
	font-family: Verdana;
	padding-bottom:10px;
	border-bottom: 1px dashed #000;
	font-weight: bold;
}
#box {
	width: 700px;
	min-height: 100px;
	border:	1px solid #000;
	margin-left: auto ;
	margin-right: auto ;
	margin-top: 100px;
	padding: 10px;
	background-color: #FF9966
}
pre {
	white-space: pre-wrap;
}

</style>
</head>
<body>
<div id="box">
<div class="title"><?=$title?></div>
<div>
<pre>
<?=$msg?>
</pre>
</div>
</body>
</html>



<?php }