/**
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.resources
 */

(function($) {


Craft.AccountSettingsForm = Garnish.Base.extend(
{
	isCurrent: null,

	$lockBtns: null,
	$currentPasswordInput: null,
	$spinner: null,

	modal: null,

	init: function(isCurrent)
	{
		this.isCurrent = isCurrent;
		this.$lockBtns = $('.btn.lock');
		this.addListener(this.$lockBtns, 'click', 'showCurrentPasswordForm');
	},

	showCurrentPasswordForm: function()
	{
		if (!this.modal)
		{
			var $form = $('<form id="verifypasswordmodal" class="modal fitted"/>').appendTo(Garnish.$bod),
				$body = $('<div class="body"><p>'+Craft.t(this.isCurrent ? 'Please enter your current password.' : 'Please enter your password.')+'</p></div>').appendTo($form),
				$passwordWrapper = $('<div class="passwordwrapper"/>').appendTo($body),
				$buttons = $('<div class="buttons right"/>').appendTo($body),
				$cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo($buttons),
				$submitBtn = $('<input type="submit" class="btn submit" value="'+Craft.t('Continue')+'" />').appendTo($buttons);

			this.$currentPasswordInput = $('<input type="password" class="text password fullwidth"/>').appendTo($passwordWrapper);
			this.$spinner = $('<div class="spinner hidden"/>').appendTo($buttons);
			this.modal = new Garnish.Modal($form);

			new Craft.PasswordInput(this.$currentPasswordInput, {
				onToggleInput: $.proxy(function($newPasswordInput) {
					this.$currentPasswordInput = $newPasswordInput;
				}, this)
			});

			this.addListener($cancelBtn, 'click', function() {
				this.modal.hide();
			});

			this.addListener($form, 'submit', 'submitCurrentPassword');
		}
		else
		{
			this.modal.show();
		}

		// Auto-focus the password input
		if (!Garnish.isMobileBrowser(true))
		{
			setTimeout($.proxy(function() {
				this.$currentPasswordInput.focus();
			}, this), 100);
		}
	},

	submitCurrentPassword: function(ev)
	{
		ev.preventDefault();

		var password = this.$currentPasswordInput.val();

		if (password)
		{
			this.$spinner.removeClass('hidden');

			var data = {
				password: password
			};

			Craft.postActionRequest('users/verifyPassword', data, $.proxy(function(response, textStatus)
			{
				this.$spinner.addClass('hidden');

				if (textStatus == 'success')
				{
					if (response.success)
					{
						$('<input type="hidden" name="password" value="'+password+'"/>').appendTo('#userform');
						var $newPasswordInput = $('#newPassword');
						$('#email').add($newPasswordInput).removeClass('disabled').removeAttr('disabled');
						this.$lockBtns.remove();

						new Craft.PasswordInput($newPasswordInput);

						this.modal.hide();
					}
					else
					{
						Garnish.shake(this.modal.$container);
					}
				}

			}, this));
		}
	}

});


})(jQuery)
