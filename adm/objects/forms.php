<button data-bind="click: reset" class="btn btn-default">Reset</button>
<fieldset data-bind="with: selectedItem">
	<label>Edit item</label>
	<form method="POST" action="<?php echo $sBaseUrl; ?>">
		<input type="hidden" name="manager" value="accounts"/>
		<input type="hidden" name="action" value="delete"/>
		<div class="form-group">
			<label>Object id</label>
			<input name="id" type="text" data-bind="textInput: id;" class="form-control" />
		</div>

		<input type="submit" value="Delete" class="btn btn-danger" />
	</form>
</fieldset>
