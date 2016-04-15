<?php
	$oManagerApi = \CApi::GetModule('Core')->GetManager('channels');
	$aItems = $oManagerApi->getChannelList(0, 0);
?>
<div id="channels-screen" class="row">
	<div class="col-sm-6">
		<table class="table table-striped">
			<tr>
				<th>id</th>
				<th>login</th>
				<th>description</th>
			</tr>
			<!-- ko foreach: usersList -->
			<tr data-bind="click: $parent.selectItem.bind($parent), css: {'success': active}">
				<td data-bind="text: id;"></td>
				<td data-bind="text: login"></td>
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
	staticData['channels_list'] = <?php echo is_array($aItems) ? json_encode($aItems) : '[]'; ?>;
</script>
<script src="channels/channels.js"></script>

