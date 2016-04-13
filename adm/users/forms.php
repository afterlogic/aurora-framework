<button data-bind="click: reset" class="btn btn-default">Reset</button>
<fieldset data-bind="with: selectedItem">
	<label>Edit item</label>
	<form method="POST" action="<?php echo $sBaseUrl; ?>">
		<input type="hidden" name="manager" value="users"/>
		<input type="hidden" name="action" value="update"/>

		<div class="form-group">
			<label>Id</label>
			<input name="id" readonly="true" type="text" data-bind="textInput: id" class="form-control" />
		</div>
		<div class="form-group">
			<label>Name</label>
			<textarea name="name" data-bind="text: name" placeholder="User Name" class="form-control"></textarea>
		</div>
		<div class="form-group">
			<label>Description</label>
			<textarea name="description" data-bind="text: description" placeholder="User Description" class="form-control"></textarea>
		</div>

		<input type="submit" value="Update" class="btn btn-primary" />
	</form>
	<form method="POST" action="<?php echo $sBaseUrl; ?>">
		<input type="hidden" name="manager" value="users"/>
		<input type="hidden" name="action" value="delete"/>
		<div class="form-group">
			<label>id</label>
			<input name="id" readonly="true" type="text" data-bind="textInput: id" class="form-control" />
		</div>

		<input type="submit" value="Delete" class="btn btn-danger" />
	</form>
</fieldset>
<fieldset data-bind="if: !selectedItem()">
	<label>Create item</label>
	<form method="POST" action="<?php echo $sBaseUrl; ?>">
		<input type="hidden" name="manager" value="users"/>
		<input type="hidden" name="action" value="create"/>

		<div class="form-group">
			<label>Tenant Id</label>
			<input name="tenant_id" type="text" class="form-control" />
		</div>
		<div class="form-group">
			<label>Name</label>
			<textarea name="name" placeholder="User Name" class="form-control"></textarea>
		</div>
		<div class="form-group">
			<label>Description</label>
			<textarea name="description" placeholder="User Description" class="form-control"></textarea>
		</div>

		<input type="submit" value="Create" class="btn btn-primary" />
	</form>
</fieldset>