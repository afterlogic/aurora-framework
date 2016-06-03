<?php
	$oManagerApi = \CApi::GetModule('Core')->GetManager('tenants');
	$aItems = $oManagerApi->getTenantList(0, 0);
?>
<div id="tenants-screen" class="row">
	<div class="col-sm-6">
		<table class="table table-striped">
			<tr>
				<th>id</th>
				<th>name</th>
				<th>login</th>
				<th>description</th>
				<th>channel id</td>
			</tr>
			<!-- ko foreach: usersList -->
			<tr data-bind="click: $parent.selectItem.bind($parent), css: {'success': active}">
				<td data-bind="text: id;"></td>
				<td data-bind="text: name"></td>
				<td data-bind="text: login"></td>
				<td data-bind="text: description"></td>
				<td data-bind="text: channel_id"></td>
			</tr>
			<!-- /ko -->
		</table>
	</div>
	<div class="col-sm-6">
		<?php include "forms.php"; ?>
	</div>
</div>
<script>
	staticData['tenants_list'] = <?php echo is_array($aItems) ? json_encode($aItems) : '[]'; ?>;
</script>
<script src="tenants/tenants.js"></script>

