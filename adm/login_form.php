<?php if ($sAuthToken) { ?>
	<div>CSRF TOKEN: <?php echo $sToken; ?></div>
	<div>AUTH TOKEN: <?php echo $sAuthToken; ?></div>
	<div>USER ID: <?php echo $iUserId; ?></div>
	<form method="POST" action="<?php echo $sBaseUrl; ?>">
		<input name="manager" type="hidden" value="auth" class="form-control" />
		<input name="action" type="hidden" value="logout" class="form-control" />

		<input type="submit" value="Logout" />
	</form>
<?php } else { ?>
<fieldset>
	<label>Login</label>
	<form method="POST" action="<?php echo $sBaseUrl; ?>">
		<input name="manager" type="hidden" value="auth" class="form-control" />
		<!--<input name="Method" type="text" value="Login2" class="form-control" />-->
		<input name="action" type="hidden" value="login" class="form-control" />

		<input name="login" type="text" class="form-control" />
		<input name="password" type="text" class="form-control" />

		<input type="submit" value="Login" />
	</form>
</fieldset>
<?php } ?>
