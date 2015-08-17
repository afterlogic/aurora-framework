<tr>
	<td align="left" valign="top">
		<span><?php echo CApi::I18N('ADMIN_PANEL/TENANTS_EMAIL_FOR_INVITE_NOTIFICATIONS'); ?></span>
	</td>
	<td align="left">
		<input class="wm_input" name="txtTenantInviteEmail" id="txtTenantInviteEmail" value="<?php $this->Data->PrintInputValue('txtTenantInviteEmailInput') ?>" />
		<div class="wm_information_com">
			<?php echo CApi::I18N('ADMIN_PANEL/TENANTS_EMAIL_FOR_INVITE_NOTIFICATIONS_DESC'); ?>
		</div>
	</td>
</tr>

<input type="hidden" name="txtToken" value="<?php $this->Data->PrintInputValue('txtToken') ?>" />
