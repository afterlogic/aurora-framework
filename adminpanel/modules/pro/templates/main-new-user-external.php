<table class="wm_contacts_view">
	<tr>
		<td class="wm_field_title" style="width: 160px">
			<?php echo CApi::I18N('ADMIN_PANEL/USERS_EMAIL'); ?> *
		</td>
		<td class="wm_field_value">
			<input name="hiddenDomainId" type="hidden" id="hiddenDomainId" value="<?php $this->Data->PrintInputValue('hiddenDomainId') ?>" />
			<input name="txtNewEmail" type="text" id="txtNewEmail" class="wm_input"	maxlength="100" value="" />
		</td>
	</tr>
	<tr>
		<td class="wm_field_title">
			<?php echo CApi::I18N('ADMIN_PANEL/USERS_PASSWORD'); ?> *
		</td>
		<td class="wm_field_value">
			<input name="txtNewPassword" type="password" id="txtNewPassword" class="wm_input" maxlength="100" value="" />
		</td>
	</tr>
	<tr data-bind="visible: allowWebMail">
		<td align="left"></td>
		<td align="left">
			<input type="checkbox" class="wm_checkbox" name="chAllowMail" id="chAllowMail" value="1"
				data-bind="checked: chAllowMail"/>
			<label id="txtOutgoingMailLogin_label" for="chAllowMail">
				<?php echo 'Enable mail'; ?>
			</label>
		</td>
	</tr>
</table>
