<?php
	$oManagerApi = \CApi::GetModule('Core')->GetManager('users');
	//	var_dump($oManagerApi);
	$aItems = $oManagerApi->getUserList(0, 0);
//	var_dump($aItems);
?>
	
<div id="users-screen" class="row">
	<div class="col-sm-6">
		<table class="table table-striped" data-bind="foreach: usersList">
			<tr data-bind="click: $parent.selectItem.bind($parent)">
				<td data-bind="text: id;"></td>
				<td data-bind="text: name"></td>
				<td data-bind="text: description"></td>
			</tr>
		</table>
	</div>
	<div class="col-sm-6">
		<button data-bind="click: reset">Reset</button>
		<fieldset data-bind="with: selectedItem">
			<label>Edit item</label>
			<form method="POST" action="/adm/">
				<input type="hidden" name="manager" value="users"/>
				<input type="hidden" name="action" value="update"/>

				<input name="id" type="text" data-bind="textInput: id;" class="form-control" />
				<textarea name="name" data-bind="text: name" placeholder="User Name" class="form-control"></textarea>
				<textarea name="description" data-bind="text: description" placeholder="User Description" class="form-control"></textarea>

				<input type="submit" value="Update" />
			</form>
		</fieldset>
		<fieldset data-bind="if: !selectedItem()">
			<label>Create item</label>
			<form method="POST" action="/adm/">
				<input type="hidden" name="manager" value="users"/>
				<input type="hidden" name="action" value="create"/>

				<input name="id" type="text" class="form-control" />
				<textarea name="name" placeholder="User Name" class="form-control"></textarea>
				<textarea name="description" placeholder="User Description" class="form-control"></textarea>

				<input type="submit" value="Create" />
			</form>
		</fieldset>
	</div>
</div>
<script>
window.staticData['users_list'] = <?php echo json_encode($aItems); ?>;
</script>
<script src="users/users.js"></script>