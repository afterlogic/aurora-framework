<table class="wm_contacts_view">
	<tr>
		<td class="wm_field_title">
		<?php echo CApi::I18N('ADMIN_PANEL/USERS_EMAIL'); ?> *
		</td>
		<td class="wm_field_value">
			<input name="hiddenDomainId" type="hidden" id="hiddenDomainId" value="<?php $this->Data->PrintInputValue('hiddenDomainId') ?>" />
			<input name="txtInviteLogin" type="text" id="txtNewLogin" class="wm_input"
				style="width: 150px" maxlength="100" value="" />
		</td>
	</tr>
</table>