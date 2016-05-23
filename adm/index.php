<?php

/* -AFTERLOGIC LICENSE HEADER- */

if (!defined('PSEVEN_APP_ROOT_PATH'))
{
	include "init.php";
	
	$oAppData = \CApi::GetModule('Core')->GetAppData();

	$sToken = $oAppData['Token'];
	$sAuthToken = isset($_COOKIE['AUTH']) ? $_COOKIE['AUTH'] : '';
	$iStoredTab = isset($_COOKIE['TAB']) ? (int)$_COOKIE['TAB'] : 0;
	
	$oApiIntegrator = \CApi::GetCoreManager('integrator');
	$iUserId = $oApiIntegrator->getLogginedUserId($sAuthToken);
	$sBaseUrl = $_SERVER['REQUEST_URI'];
	if (!$iUserId)
	{
		$sAuthToken = '';
		setcookie('AUTH', '');
	}
	
	include "action.php";
} ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    
	
    <!-- Bootstrap -->
    <!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://yastatic.net/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
	
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
	
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://yastatic.net/jquery/2.2.0/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
   
	<!-- Latest compiled and minified JavaScript -->
	<script src="https://yastatic.net/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
	
    <script src="<?php echo $sBaseUrl; ?>../node_modules/underscore/underscore-min.js"></script>
    <script src="<?php echo $sBaseUrl; ?>../node_modules/knockout/build/output/knockout-latest.js"></script>
    <script>
		var staticData = {};
	</script>
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="col-sm-8">
				<div class="page-header">
					<h1>Test panel<small>beta 0.1</small></h1>
				</div>
			</div>
			<div class="col-sm-4">
				<?php include "login_form.php"; ?>
			</div>
		</div>
		<!-- Nav tabs -->
		<ul id="myTabs" class="nav nav-tabs" role="tablist">
			<li role="presentation" class="<?php echo $iStoredTab === 0 ? 'active' : ''?>"><a href="#ajax" aria-controls="ajax" role="tab" data-toggle="tab">Ajax</a></li>
			<li role="presentation" class="<?php echo $iStoredTab === 1 ? 'active' : ''?>"><a href="#channels" aria-controls="channels" role="tab" data-toggle="tab">Channels</a></li>
			<li role="presentation" class="<?php echo $iStoredTab === 2 ? 'active' : ''?>"><a href="#tenants" aria-controls="tenants" role="tab" data-toggle="tab">Tenants</a></li>
			<li role="presentation" class="<?php echo $iStoredTab === 3 ? 'active' : ''?>"><a href="#users" aria-controls="users" role="tab" data-toggle="tab">Users</a></li>
			<li role="presentation" class="<?php echo $iStoredTab === 4 ? 'active' : ''?>"><a href="#accounts" aria-controls="accounts" role="tab" data-toggle="tab">Accounts</a></li>
			<li role="presentation" class="<?php echo $iStoredTab === 6 ? 'active' : ''?>"><a href="#mail" aria-controls="mail" role="tab" data-toggle="tab">Mail</a></li>
		</ul>

		<!-- Tab panes -->
		<div class="tab-content">
			<div role="tabpanel" class="tab-pane <?php echo $iStoredTab === 0 ? 'active' : ''?>" id="ajax">
				<form id="form" onsubmit="return false;">
					
					<input name="Token" type="text" placeholder="Token" value="" class="form-control" />
					<input name="AuthToken" type="text" placeholder="AuthToken" value="" class="form-control" />
					<input name="Method" type="text" placeholder="Method" class="form-control" />
					<input name="Module" type="text" placeholder="Module" class="form-control" />
					
					<input name="login" type="text" class="form-control" />
					<input name="password" type="text" class="form-control" />
					
					<button id="button">Send</button>
				</form>
			</div>
			<div role="tabpanel" class="tab-pane <?php echo $iStoredTab === 1 ? 'active' : ''?>" id="channels">
				<?php include "channels/list.php"; ?>
			</div>
			<div role="tabpanel" class="tab-pane <?php echo $iStoredTab === 2 ? 'active' : ''?>" id="tenants">
				<?php include "tenants/list.php"; ?>
			</div>
			<div role="tabpanel" class="tab-pane <?php echo $iStoredTab === 3 ? 'active' : ''?>" id="users">
				<?php include "users/list.php"; ?>
			</div>
			<div role="tabpanel" class="tab-pane <?php echo $iStoredTab === 4 ? 'active' : ''?>" id="accounts">
				<?php include "accounts/list.php"; ?>
			</div>
			<div role="tabpanel" class="tab-pane <?php echo $iStoredTab === 6 ? 'active' : ''?>" id="mail">
				<?php include "mail/list.php"; ?>
			</div>
		</div>
	</div>
	
	<script>
	$('#myTabs')
		.click(function (e) {
			e.preventDefault();
			$(this).tab('show');
		})
		.on('shown.bs.tab', function (e) {
			var index = $(this).children().index($(e.target).parent());
			document.cookie = "TAB="+index;
		});
	
	$('#button').click(function () {
		$.ajax('/?/Ajax/', {
			'method': 'POST',
			'data': {
				'Token': $('input[name=Token]').val(),
				'AuthToken': $('input[name=AuthToken]').val(),
				'Method': $('input[name=Method]').val(),
				'Module': $('input[name=Module]').val(),
				'Parameters': JSON.stringify({
					'login': $('input[name=login]').val(),
					'password': $('input[name=password]').val()
				})
			}
		})
		.always(function() {
			console.log( "complete", arguments );
		});
 	});
	</script>
</body>
</html>