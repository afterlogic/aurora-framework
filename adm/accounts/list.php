<?php
	$oManagerApi = \CApi::GetModule('Auth')->GetManager('accounts');
	$aItems = $oManagerApi->getAccountList(0, 0);
?>
<div id="accounts-screen" class="row">
	<div class="col-sm-6">
		<table class="table table-striped" data-bind="foreach: usersList">
			<tr data-bind="click: $parent.selectItem.bind($parent)">
				<td data-bind="text: id;"></td>
				<td data-bind="text: login"></td>
				<td data-bind="text: password"></td>
			</tr>
		</table>
	</div>
	<div class="col-sm-6">
		<button data-bind="click: reset">Reset</button>
		<fieldset data-bind="with: selectedItem">
			<label>Edit item</label>
			<form method="POST" action="/adm/">
				<input type="hidden" name="manager" value="accounts"/>
				<input type="hidden" name="action" value="update"/>

				<input name="id" type="text" data-bind="textInput: id;" class="form-control" />
				<input name="login" data-bind="textInput: login" placeholder="User Login" class="form-control" />
				<input name="password" data-bind="textInput: password" placeholder="User Password" class="form-control" />

				<input type="submit" value="Update" />
			</form>
			<form method="POST" action="/adm/">
				<input type="hidden" name="manager" value="accounts"/>
				<input type="hidden" name="action" value="delete"/>
				<input name="id" type="text" data-bind="textInput: id;" class="form-control" />
				<input type="submit" value="Delete" />
			</form>
		</fieldset>
		<fieldset data-bind="if: !selectedItem()">
			<label>Create item</label>
			<form method="POST" action="/adm/">
				<input type="hidden" name="manager" value="accounts"/>
				<input type="hidden" name="action" value="create"/>

				<input name="id" type="text" class="form-control" />
				<input name="login" placeholder="User Login" class="form-control" />
				<input name="password" placeholder="User Password" class="form-control" />

				<input type="submit" value="Create" />
			</form>
		</fieldset>
	</div>
</div>
<script>
	staticData['accounts_list'] = <?php echo is_array($aItems) ? json_encode($aItems) : '[]'; ?>;
</script>
<script src="accounts/accounts.js"></script>

