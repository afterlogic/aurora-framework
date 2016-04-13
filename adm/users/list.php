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
		<?php include "forms.php"; ?>
	</div>
</div>
<script>
window.staticData['users_list'] = <?php echo json_encode($aItems); ?>;
</script>
<script src="users/users.js"></script>