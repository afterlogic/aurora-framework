<?php
//	$oManagerApi = \CApi::GetModule('Conacts')->GetManager('main');
//	$aItems = $oManagerApi->getAccountList(0, 0);
//	
//	$oManagerApi = \CApi::GetModule('Conacts')->GetManager('main');
//	$aItems = $oManagerApi->getAccountList(0, 0);
	
//	\CApi::ExecuteMethod('Contacts::GetGroups', array(
//		'Token' => $sToken,
//		'AuthToken' => $sAuthToken,
//		'IdUser' => $oHttp->GetPost('user_id', ''),
//		'Login' => $oHttp->GetPost('login', ''),
//		'Password' => $oHttp->GetPost('password', '')
//	));
?>
<div id="contacts-screen" class="row">
	<div class="col-sm-6">
		<table class="table table-striped">
			<tr>
				<th>id</th>
				<th>login</th>
				<th>password</th>
				<th>user id</td>
			</tr>
			<!-- ko foreach: usersList -->
			<tr data-bind="click: $parent.selectItem.bind($parent), css: {'success': active}">
				<td data-bind="text: id;"></td>
				<td data-bind="text: login"></td>
				<td data-bind="text: password"></td>
				<td data-bind="text: user_id"></td>
			</tr>
			<!-- /ko -->
		</table>
	</div>
	<div class="col-sm-6">
		<button data-bind="click: reset" class="btn btn-default">Reset</button>
		<fieldset data-bind="with: selectedItem">
			<label>Edit item</label>
			<form method="POST" action="/adm/">
				<input type="hidden" name="manager" value="accounts"/>
				<input type="hidden" name="action" value="update"/>
				
				<div class="form-group">
					<label>Id</label>
					<input name="id" readonly="true" type="text" data-bind="textInput: id;" class="form-control" />
				</div>
				<div class="form-group">
					<label>Login</label>
					<input name="login" data-bind="textInput: login" placeholder="Account Login" class="form-control" />
				</div>
				<div class="form-group">
					<label>Password</label>
					<input name="password" data-bind="textInput: password" placeholder="Account Password" class="form-control" />
				</div>

				<input type="submit" value="Update" class="btn btn-primary" />
			</form>
			<form method="POST" action="/adm/">
				<input type="hidden" name="manager" value="accounts"/>
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
			<form method="POST" action="/adm/">
				<input type="hidden" name="manager" value="accounts"/>
				<input type="hidden" name="action" value="create"/>

				<div class="form-group">
					<label>User id</label>
					<input name="user_id" placeholder="User Id" class="form-control" />
				</div>
				<div class="form-group">
					<label>Login</label>
					<input name="login" placeholder="Account Login" class="form-control" />
				</div>
				<div class="form-group">
					<label>Password</label>
					<input name="password" placeholder="Account Password" class="form-control" />
				</div>

				<input type="submit" value="Create" class="btn btn-primary" />
			</form>
		</fieldset>
	</div>
</div>
<script>
	staticData['contacts_list'] = <?php echo is_array($aItems) ? json_encode($aItems) : '[]'; ?>;
	staticData['contacts_groups'] = <?php echo is_array($aContactGrouns) ? json_encode($aContactGrouns) : '[]'; ?>;
</script>
<script src="accounts/accounts.js"></script>

