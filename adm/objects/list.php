<?php
	$oManagerApi = \CApi::GetCoreManager('eav', 'db');
	$aTypes = $oManagerApi->getTypes();
?>
<div id="objects-screen" class="row">
	<div class="col-sm-12">
		<br />
		<br />
		<ul id="object-tabs" class="nav nav-tabs" role="tablist" data-bind="foreach: objectTypes">
			<li role="presentation" class="<?php //echo $iStoredTab === 6 ? 'active' : ''?>"><a href="#ajax" aria-controls="ajax" role="tab" data-toggle="tab" data-bind="text: $data, attr: {'href': '#object-'+$data}, click: $parent.switchTab"></a></li>
		</ul>
		<div class="tab-content" data-bind="foreach: objectTypes">
			<div role="tabpanel" class="table-responsive tab-pane <?php echo $iStoredTab === 0 ? 'active' : ''?>" id="" data-bind="attr: {'id': 'object-'+$data}">
				<table class="table table-striped">
					<tr>
						<!-- ko foreach: $parent.propsList -->
						<th  data-bind="text: $data;"></th>
						<!-- /ko -->
					</tr>
					<!-- ko foreach: $parent.objectsList -->
					<tr data-bind="click: $parents[1].selectItem, css: {'success': $parents[1].selectedItem() == $data}">
						<!-- ko foreach: $data -->
							<td  data-bind="text: $data;"></td>
					<!-- /ko -->
					</tr>
					<!-- /ko -->
				</table>
			</div>
		</div>
	</div>
	<div class="col-sm-12">
		<?php include "forms.php"; ?>
	</div>
</div>
<script>
	staticData['objects'] = <?php echo is_array($aTypes) ? json_encode($aTypes) : '[]'; ?>;
	$('#object-tabs')
		.click(function (e) {
			e.preventDefault();
			$(this).tab('show');
		});
		//.on('shown.bs.tab', function (e) {
		//	var index = $(this).children().index($(e.target).parent());
		//	document.cookie = "OBJECT_TAB="+index;
		//});
</script>
<script src="objects/objects.js"></script>

