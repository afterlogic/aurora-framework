<?php
	$aItems = array();
	$aResultItems = array();
	$oManagerApi = \CApi::GetCoreManager('eav', 'db');
	$aTypes = $oManagerApi->getTypes();
/*	
	foreach ($aTypes as $sType)
	{
		$aItems = array_merge($aItems, $oManagerApi->getObjects($sType));
	}
	foreach ($aItems as $oItem)
	{
		var_dump($oItem->getMap());
	}
 * 
 */
	$aItems = array_merge($aItems, $oManagerApi->getObjects('CMailAccount'));
	$aProperties = array_merge(array('IdObject'), array_keys($aItems[0]->getMap()));
	foreach ($aItems as $oItem)
	{
		$aResultItems[] = $oItem->toArray();
	}

	
//	var_dump($aItems);
?>
<div id="objects-screen" class="row">
	<div class="col-sm-6">
		<ul id="object-tabs" class="nav nav-tabs" role="tablist" data-bind="foreach: objectTypes">
			<li role="presentation" class="<?php //echo $iStoredTab === 6 ? 'active' : ''?>"><a href="#ajax" aria-controls="ajax" role="tab" data-toggle="tab" data-bind="text: $data, attr: {'href': '#object-'+$data}"></a></li>
		</ul>
		<div class="tab-content" data-bind="foreach: objectTypes">
			<div role="tabpanel" class="tab-pane <?php echo $iStoredTab === 0 ? 'active' : ''?>" id="" data-bind="attr: {'id': 'object-'+$data}">
				<table class="table table-striped">
					<tr>
						<!-- ko foreach: $parent.propsList -->
						<th  data-bind="text: $data;"></th>
						<!-- /ko -->
					</tr>
					<!-- ko foreach: $parent.objectsList -->
					<tr>
						<!-- ko foreach: $data -->
							<td  data-bind="text: $data;"></td>
					<!-- /ko -->
					</tr>
					<!-- /ko -->
				</table>
			</div>
		</div>
	</div>	<div class="col-sm-6">
		<?php include "forms.php"; ?>
	</div>
</div>
<script>
	staticData['objects'] = <?php echo is_array($aTypes) ? json_encode($aTypes) : '[]'; ?>;
	staticData['objects_list'] = <?php echo is_array($aResultItems) ? json_encode($aResultItems) : '[]'; ?>;
	staticData['objects_props'] = <?php echo is_array($aProperties) ? json_encode($aProperties) : '[]'; ?>;
	console.log($('#object-tabs'));
	$('#object-tabs')
		.click(function (e) {
			e.preventDefault();
			$(this).tab('show');
		})
		//.on('shown.bs.tab', function (e) {
		//	var index = $(this).children().index($(e.target).parent());
		//	document.cookie = "OBJECT_TAB="+index;
		//});
</script>
<script src="objects/objects.js"></script>

