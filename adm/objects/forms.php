<!--<button data-bind="click: reset" class="btn btn-default">Reset</button>-->
<div data-bind="with: selectedItem">
	<label>Edit item</label>
	<form method="POST" action="<?php echo $sBaseUrl; ?>">
		<input type="hidden" name="ObjectName" data-bind="value: $parent.selectedObjectName"/>
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="manager" value="objects" />
		<input type="hidden" name="action" value="edit"/>
		
		<div class="table-responsive">
			<table class="table table-striped">
				<tr>
					<!-- ko foreach: $parent.propsList -->
					<th  data-bind="text: $data;"></th>
					<!-- /ko -->
				</tr>
				<tr class="form-group">
					<!-- ko foreach: $data -->
					<td>
						<input type="text" data-bind="value: $data, attr: {'name': $parents[1].propsList()[$index()]}" class="form-control" style="min-width: 100px;" />
						<!--<input type="text" data-bind="value: $data, attr: console.log($index())" class="form-control" />-->
					</td>
					<!-- /ko -->
				</tr>
			</table>
		</div>
		<input type="submit" value="Update" class="btn btn-danger" />
	</form>
</div>
<div data-bind="with: selectedItem">
	<label>Delete item</label>
	<form method="POST" action="<?php echo $sBaseUrl; ?>">
		<input type="hidden" name="manager" value="objects"/>
		<input type="hidden" name="action" value="delete"/>
		<div class="form-group">
			<label>Object id</label>
			<input name="iObjectId" type="text" data-bind="textInput: $data[0];" class="form-control" />
		</div>

		<input type="submit" value="Delete" class="btn btn-danger" />
	</form>
</div>
