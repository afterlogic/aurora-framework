<button data-bind="click: reset" class="btn btn-default">Reset</button>
<!-- ko if: !selectedItem() -->
<form class="form-inline" style="display: inline-block;" method="POST" action="<?php echo $sBaseUrl; ?>">
	<input type="hidden" name="manager" value="tenants"/>
	<input type="hidden" name="action" value="build"/>
	<input type="submit" value="Build common CSS" class="btn btn-danger" />
</form>
<!-- /ko -->
<!-- ko with: selectedItem -->
<form class="form-inline" style="display: inline-block;" method="POST" action="<?php echo $sBaseUrl; ?>">
	<input type="hidden" name="manager" value="tenants"/>
	<input type="hidden" name="action" value="build"/>
	<input name="name" readonly="true" type="hidden" data-bind="textInput: name;" class="form-control" />

	<input type="submit" value="Build CSS" class="btn btn-danger" data-bind="value: 'Build CSS for: ' + name;" />
</form>
<!-- /ko -->
<fieldset data-bind="with: selectedItem">
	<form method="POST" action="<?php echo $sBaseUrl; ?>">
		<input type="hidden" name="manager" value="tenants"/>
		<input type="hidden" name="action" value="update"/>

		<h3>Edit item</h3>
		<div class="form-group">
			<label>Id</label>
			<input name="id" readonly="true" type="text" data-bind="textInput: id;" class="form-control" />
		</div>
		<div class="form-group">
			<label>Name</label>
			<input name="name" data-bind="textInput: name" class="form-control" />
		</div>
		<div class="form-group">
			<label>Login</label>
			<input name="login" readonly data-bind="textInput: login" class="form-control" />
		</div>
		<div class="form-group">
			<label>Description</label>
			<input name="description" data-bind="textInput: description" class="form-control" />
		</div>
		<div class="form-group">
			<label>Channel Id</label>
			<input name="channel_id" data-bind="textInput: channel_id" class="form-control" />
		</div>

		<input type="submit" value="Update" class="btn btn-primary" />
	</form>
	<form class="form-inline" method="POST" action="<?php echo $sBaseUrl; ?>">
		
		<h3>Delete item</h3>
		<input type="hidden" name="manager" value="tenants"/>
		<input type="hidden" name="action" value="delete"/>
		<div class="form-group">
			<input name="id" readonly="true" type="text" data-bind="textInput: id;" class="form-control" />
		</div>

		<input type="submit" value="Delete" class="btn btn-danger" />
	</form>
</fieldset>
<fieldset data-bind="if: !selectedItem()">
	<form method="POST" action="<?php echo $sBaseUrl; ?>">
		<input type="hidden" name="manager" value="tenants"/>
		<input type="hidden" name="action" value="create"/>

		<h3>Create item</h3>
		<div class="form-group">
			<label>Channel id</label>
			<input name="channel_id" class="form-control" />
		</div>
		<div class="form-group">
			<label>Name</label>
			<input name="name" class="form-control" />
		</div>
		<div class="form-group">
			<label>Description</label>
			<input name="description" class="form-control" />
		</div>
		<div class="form-group">
			<label>Password</label>
			<input name="password" class="form-control" />
		</div>

		<input type="submit" value="Create" class="btn btn-primary" />
	</form>
</fieldset>