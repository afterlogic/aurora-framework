<?php
	$oManagerApi = \CApi::GetModule('Mail')->GetManager('accounts');
//		var_dump($oManagerApi);
	$aItems = $oManagerApi->getAccountList(0, 0);
//	var_dump($aItems);
?>
<div id="mail-screen" class="row">
	<div class="col-sm-6">
		<table class="table table-striped">
			<tr>
				<th>id</th>
				<th>login</th>
				<th>password</th>
				<th>user id</td>
			</tr>
			<tr>
				<td>123</td>
				<td>qwe</td>
				<td>asd</td>
				<td>234</td>
			</tr>
		</table>
	</div>
</div>


