/**
 * Craft by Pixel & Tonic
 *
 * @package   Craft
 * @author    Pixel & Tonic, Inc.
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 */

(function($) {

	var ImageUpload = null;

	var settings = {
		postParameters: {userId: $('.user-photo').attr('data-user')},

		modalClass: "profile-image-modal",
		uploadAction: 'users/uploadUserPhoto',

		deleteMessage: Craft.t('Are you sure you want to delete this photo?'),
		deleteAction: 'users/deleteUserPhoto',

		cropAction: 'users/cropUserPhoto',

		areaToolOptions:
		{
			aspectRatio: "1:1",
			initialRectangle: {
				mode: "auto"
			}
		},

		onImageSave: function(response)
		{
			refreshImage(response);
		},

		onImageDelete: function(response)
		{
		   refreshImage(response);
		}
	};

	function refreshImage(response) {
		if (typeof response.html != "undefined") {
			$('.user-photo').replaceWith(response.html);
			var newImage = $('.user-photo>.current-photo').css('background-image').replace(/^url\(/, '').replace(/\)$/, '');

			$('#account-menu').find('img').attr('src', newImage);
			initImageUpload();
		}

	}

	function initImageUpload()
	{
		// These change dynamically after each HTML overwrite, so we can't have them in the initial settings array.
		settings.uploadButton = $('.user-photo-controls .upload-photo');
		settings.deleteButton = $('.user-photo-controls .delete-photo');
		ImageUpload = new Craft.ImageUpload(settings);
	}

	// Only init for existing users.
	if ($('input[name=userId]').val())
	{
		initImageUpload();
	}

})(jQuery);
