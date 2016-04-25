<?php
	$oManagerApi = \CApi::GetModule('Mail')->GetManager('accounts');
	$aItems = $oManagerApi->getAccountList(0, 0);
?>
<div id="mail-screen" class="row">
	<div class="col-sm-6">
		<table class="table table-striped">
			<tr>
				<th>id</th>
				<th>email</th>
				<th>password</th>
				<th>server</th>
				<th>user id</th>
			</tr>
			<!-- ko foreach: mailAccountsList -->
			<tr data-bind="click: $parent.selectItem.bind($parent), css: {'success': active}">
				<td data-bind="text: id;"></td>
				<td data-bind="text: email"></td>
				<td data-bind="text: password"></td>
				<td data-bind="text: server"></td>
				<td data-bind="text: user_id"></td>
			</tr>
			<!-- /ko -->
		</table>
	</div>
	<div class="col-sm-6">
		<?php include "forms.php"; ?>
	</div>
</div>

<script>
	staticData['mail_accounts_list'] = <?php echo is_array($aItems) ? json_encode($aItems) : '[]'; ?>;
</script>
<script src="mail/accounts.js"></script>
