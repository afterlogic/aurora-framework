<table class="wm_contacts_view">
	<tr>
		<td class="wm_field_title">
			<span id="selMessagesPerPage_label">
				<?php echo CApi::I18N('ADMIN_PANEL/DOMAINS_WEBMAIL_MESSAGES');?>
			</span>
		</td>
		<td class="wm_field_value">
			<select name="selMessagesPerPage" id="selMessagesPerPage" class="wm_select override">
				<?php $this->Data->PrintValue('selMessagesPerPageOptions'); ?>
			</select>
		</td>
	</tr>
	<tr>
		<td class="wm_field_title">
			<span id="selAutocheckMail_label">
				<?php echo CApi::I18N('ADMIN_PANEL/DOMAINS_WEBMAIL_AUTO');?>
			</span>
		</td>
		<td class="wm_field_value">
			<select name="selAutocheckMail" id="selAutocheckMail" class="wm_select override">
				<?php $this->Data->PrintValue('selAutocheckMailOptions'); ?>
			</select>
		</td>
	</tr>
</table>