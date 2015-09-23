<?php
header('Content-Type: text/html; charset=UTF-8', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">

	<meta http-equiv="refresh" content="600">
	<title>TerraSens</title>

	<link rel="apple-touch-icon-precomposed" sizes="57x57" href="icons/apple-icon-57x57.png">
	<link rel="apple-touch-icon-precomposed" sizes="60x60" href="icons/apple-icon-60x60.png">
	<link rel="apple-touch-icon-precomposed" sizes="72x72" href="icons/apple-icon-72x72.png">
	<link rel="apple-touch-icon-precomposed" sizes="76x76" href="icons/apple-icon-76x76.png">
	<link rel="apple-touch-icon-precomposed" sizes="114x114" href="icons/apple-icon-114x114.png">
	<link rel="apple-touch-icon-precomposed" sizes="120x120" href="icons/apple-icon-120x120.png">
	<link rel="apple-touch-icon-precomposed" sizes="144x144" href="icons/apple-icon-144x144.png">
	<link rel="apple-touch-icon-precomposed" sizes="152x152" href="icons/apple-icon-152x152.png">
	<link rel="apple-touch-icon-precomposed" sizes="180x180" href="icons/apple-icon-180x180.png">
	<link rel="apple-touch-startup-image" 					 href="icons/startup.png">
	<link rel="icon" type="image/png" sizes="192x192"  href="icons/android-icon-192x192.png">
	<link rel="icon" type="image/png" sizes="32x32" href="icons/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="96x96" href="icons/favicon-96x96.png">
	<link rel="icon" type="image/png" sizes="16x16" href="icons/favicon-16x16.png">

	<link rel="manifest" href="manifest.json">
	<meta name="msapplication-TileColor" content="#ffffff">
	<meta name="msapplication-TileImage" content="icons/ms-icon-144x144.png">
	<meta name="theme-color" content="#ffffff">



	<!-- Bootstrap -->
	<link rel="stylesheet" href="./css/bootstrap.min.css">
	<link rel="stylesheet" href="./css/bootstrap-theme.min.css">
	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
	<style type="text/css">
		body {background:black;}
		h1, h2, a#navbar-brand {color:white;text-shadow: 1px 1px 0 red;font-weight:bold;}
		a#navbar-brand {font-size:2em;padding:15px 10px 15px 15px;}
		h1 button.btn {position:relative; top:-5px;}

		#updBtn {padding:5px;font-weight:bold;}

		#info {font-size:1.2em;color:white;text-shadow: 1px 1px 0 red;font-weight:bold;}
		#info span {white-space:nowrap;margin-right:.2em;position:relative;top:-10px;padding:2px 7px;border-radius:5px;box-shadow:1px 1px 1px #444;background-image:linear-gradient(to bottom,#3c3c3c 0,#222 100%);background-repeat:repeat-x;}
		#info span.on {background-image:linear-gradient(to bottom,#FF0000 0,#660000 100%);background-repeat:repeat-x;}
		#info span:before {text-shadow:none;font-weight:normal;}
		#info span.temp:before {content:"tAvg: ";}
		#info span.hmdt:before {content:"hAvg: ";}
		#info span.heat:before {content:"Heater";}
		#info span.lamp:before {content:"Lamp";}
		#info span.wet:before  {content:"Humidifier ";}
		#info span.next:before {content:"Next: ";}

		.sep {height:1.5em;border-top:1px dotted #444;}

		.progress {height:28px;text-shadow: 1px 1px 1px black, -1px -1px 1px black;font-family:Consolas, 'Lucida Console', 'DejaVu Sans Mono', monospace;}
		.progress-bar {font-size:26px;line-height:30px;text-align:left;padding-left:.5em;background-color:#AAA;white-space:nowrap;}
		.progress-bar-success {background-color:green;}
		.progress-bar-info {background-color:blue;}
		.progress-bar-warning {background-color:yellow;}
		.progress-bar-danger {background-color:red;}

		 .highcharts-tooltip {font-family:Consolas, 'Lucida Console', 'DejaVu Sans Mono', monospace;}
	</style>
</head>
<body>


<nav class="navbar navbar-inverse navbar-static-top">
	<div class="container-fluid">
		<!-- Brand and toggle get grouped for better mobile display -->
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbarcollapse" aria-expanded="false">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<a id="navbar-brand" class="navbar-brand" href="//pi.denev.info/terrasens">TerraSens</a>
			<button id="updBtn" class="btn btn-sm btn-default navbar-btn navbar-left" onclick="reData();">
				<span id="ts"></span>
				<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
			</button>
		</div>


		<!-- Collect the nav links, forms, and other content for toggling -->
		<div class="collapse navbar-collapse" id="navbarcollapse">
			<ul class="nav navbar-nav navbar-right">
				<li><a href="charts.php">Charts</a></li>
				<li><a href="https://beebotte.com/dash/54e55cf0-5933-11e5-b705-cb32fe72af39" target="_blank">BeeBotte</a></li>
			</ul>
		</div><!-- /.navbar-collapse -->
	</div><!-- /.container-fluid -->
</nav>
