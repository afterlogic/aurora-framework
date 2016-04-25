<button data-bind="click: reset" class="btn btn-default">Reset</button>
<fieldset data-bind="with: selectedItem">
	<label>Edit item</label>
	<form method="POST" action="<?php echo $sBaseUrl; ?>">
		<input type="hidden" name="manager" value="mail"/>
		<input type="hidden" name="action" value="update"/>

		<div class="form-group">
			<label>Id</label>
			<input name="id" readonly="true" type="text" data-bind="textInput: id;" class="form-control" />
		</div>
		<div class="form-group">
			<label>Email</label>
			<input name="email" data-bind="textInput: email" placeholder="Account Email" class="form-control" />
		</div>
		<div class="form-group">
			<label>Password</label>
			<input name="password" data-bind="textInput: password" placeholder="Account Password" class="form-control" />
		</div>
		<div class="form-group">
			<label>Server</label>
			<input name="server" data-bind="textInput: server" placeholder="IMAP server" class="form-control" />
		</div>

		<input type="submit" value="Update" class="btn btn-primary" />
	</form>
	<form method="POST" action="<?php echo $sBaseUrl; ?>">
		<input type="hidden" name="manager" value="mail"/>
		<input type="hidden" name="action" value="delete"/>
		<div class="form-group">
			<label>Account id</label>
			<input name="id" readonly="true" type="text" data-bind="textInput: id;" class="form-control" />
		</div>

		<input type="submit" value="Delete" class="btn btn-danger" />
	</form>
</fieldset>
<fieldset data-bind="if: !selectedItem()">
	<label>Create item</label>
	<form method="POST" action="<?php echo $sBaseUrl; ?>">
		<input type="hidden" name="manager" value="mail"/>
		<input type="hidden" name="action" value="create"/>

		<div class="form-group">
			<label>User id</label>
			<input name="user_id" placeholder="User Id" class="form-control" />
		</div>
		<div class="form-group">
			<label>Email</label>
			<input name="email" placeholder="Account Email" class="form-control" />
		</div>
		<div class="form-group">
			<label>Password</label>
			<input name="password" placeholder="Account Password" class="form-control" />
		</div>
		<div class="form-group">
			<label>Server</label>
			<input name="server" placeholder="IMAP server" class="form-control" />
		</div>

		<input type="submit" value="Create" class="btn btn-primary" />
	</form>
</fieldset>
