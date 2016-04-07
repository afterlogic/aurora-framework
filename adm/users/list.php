<?php
	$oManagerApi = \CApi::GetModule('Core')->GetManager('users');
	//	var_dump($oManagerApi);
	$aItems = $oManagerApi->getUserList(0, 0);
//	var_dump($aItems);
?>
	
<div id="users-screen" class="row">
	<div class="col-sm-6">
		<table class="table table-striped">
			<tr>
				<th>id</th>
				<th>name</th>
				<th>description</th>
			</tr>
			<!-- ko foreach: usersList -->
			<tr data-bind="click: $parent.selectItem.bind($parent), css: {'success': active}">
				<td data-bind="text: id;"></td>
				<td data-bind="text: name"></td>
				<td data-bind="text: description"></td>
			</tr>
			<!-- /ko -->
		</table>
	</div>
	<div class="col-sm-6">
		<button data-bind="click: reset" class="btn btn-default">Reset</button>
		<fieldset data-bind="with: selectedItem">
			<label>Edit item</label>
			<form method="POST" action="/adm/">
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
			<form method="POST" action="/adm/">
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
			<form method="POST" action="/adm/">
				<input type="hidden" name="manager" value="users"/>
				<input type="hidden" name="action" value="create"/>

				<div class="form-group">
					<label>Id</label>
					<input name="id" type="text" class="form-control" />
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
	</div>
</div>
<script>
window.staticData['users_list'] = <?php echo json_encode($aItems); ?>;
</script>
<script src="users/users.js"></script>