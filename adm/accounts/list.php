<?php
	$oManagerApi = \CApi::GetModule('BasicAuth')->GetManager('accounts');
	$aItems = $oManagerApi->getAccountList(0, 0);

//TODO: fix password encoder
foreach ($aItems as &$oItem) {
	$oItem[1] = htmlspecialchars($oItem[1]);
}

?>
<div id="accounts-screen" class="row">
	<div class="col-sm-6">
		<table class="table table-striped">
			<tr>
				<th>id</th>
				<th>login</th>
				<th>password</th>
				<th>user id</th>
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
		<?php include "forms.php"; ?>
	</div>
</div>

<script>
	staticData['accounts_list'] = <?php echo is_array($aItems) ? json_encode($aItems) : '[]'; ?>;
</script>
<script src="accounts/accounts.js"></script>

