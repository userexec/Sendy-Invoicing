<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="">
		<meta name="author" content="">
		<meta name="robots" content="noindex, nofollow">
		<link rel="Shortcut Icon" type="image/ico" href="<?php echo get_app_info('path');?>/img/favicon.png">
		<link rel="Shortcut Icon" type="image/ico" href="<?php echo get_app_info('path');?>/img/favicon.png">
		<link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" />
		<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
		<link rel="apple-touch-icon-precomposed" href="<?php echo get_app_info('path');?>/img/sendy-icon.png" />
		<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
	    <!--[if lt IE 9]>
	      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	    <![endif]-->
		<script type="text/javascript" src="<?php echo get_app_info('path');?>/js/jquery-1.9.1.min.js"></script>
		<script type="text/javascript" src="<?php echo get_app_info('path');?>/js/jquery-migrate-1.1.0.min.js"></script>
		<script type="text/javascript" src="<?php echo get_app_info('path');?>/js/jquery-ui-1.8.21.custom.min.js"></script>
		<script type="text/javascript" src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
		<link href='https://fonts.googleapis.com/css?family=Roboto:400,400italic,700,700italic' rel='stylesheet' type='text/css'>
		<title>Sendy - Invoice</title>
	</head>
	<body>
		<style>
			.navbar {
				top: 0;
				position: fixed;
				right: 0;
				left: 0;
				z-index: 1030;
				margin-bottom: 0;
				color: #999;
				overflow: visible;
				font-family: 'Roboto', "Helvetica Neue", Helvetica, Arial, sans-serif;
			}

			.separator {
				clear: both;
				height: 5px;
				background: url("<?php echo get_app_info('path');?>/img/top-pattern.gif") repeat-x 0 0;
				background-size: 46px;
			}

			.navbar-inner {
				padding-left: 0px;
				padding-right: 0px;
				min-height: 40px;
				background-color: #21252b;
				box-shadow: 0 1px 3px rgba(0,0,0,.25), inset 0 -1px 0 rgba(0,0,0,.1);
			}
		</style>
		<div class="navbar navbar-fixed-top">
		  <div class="separator"></div>
	      <div class="navbar-inner">
	        <div class="container-fluid">
	          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
	            <span class="icon-bar"></span>
	            <span class="icon-bar"></span>
	            <span class="icon-bar"></span>
	          </a>  
	        </div>
	      </div>
	    </div>