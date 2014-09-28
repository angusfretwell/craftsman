/*
 Copyright (c) 2014, Pixel & Tonic, Inc.
 @license   http://buildwithcraft.com/license Craft License Agreement
 @see       http://buildwithcraft.com
 @package   craft.app.resources
*/
(function(a){Craft.AccountSettingsForm=Garnish.Base.extend({isCurrent:null,$lockBtns:null,$currentPasswordInput:null,$spinner:null,modal:null,init:function(c){this.isCurrent=c;this.$lockBtns=a(".btn.lock");this.addListener(this.$lockBtns,"click","showCurrentPasswordForm")},showCurrentPasswordForm:function(){if(this.modal)this.modal.show();else{var c=a('<form id="verifypasswordmodal" class="modal fitted"/>').appendTo(Garnish.$bod),b=a('<div class="body"><p>'+Craft.t(this.isCurrent?"Please enter your current password.":
"Please enter your password.")+"</p></div>").appendTo(c),f=a('<div class="passwordwrapper"/>').appendTo(b),b=a('<div class="buttons right"/>').appendTo(b),d=a('<div class="btn">'+Craft.t("Cancel")+"</div>").appendTo(b);a('<input type="submit" class="btn submit" value="'+Craft.t("Continue")+'" />').appendTo(b);this.$currentPasswordInput=a('<input type="password" class="text password fullwidth"/>').appendTo(f);this.$spinner=a('<div class="spinner hidden"/>').appendTo(b);this.modal=new Garnish.Modal(c);
new Craft.PasswordInput(this.$currentPasswordInput,{onToggleInput:a.proxy(function(a){this.$currentPasswordInput=a},this)});this.addListener(d,"click",function(){this.modal.hide()});this.addListener(c,"submit","submitCurrentPassword")}Garnish.isMobileBrowser(!0)||setTimeout(a.proxy(function(){this.$currentPasswordInput.focus()},this),100)},submitCurrentPassword:function(c){c.preventDefault();var b=this.$currentPasswordInput.val();b&&(this.$spinner.removeClass("hidden"),Craft.postActionRequest("users/verifyPassword",
{password:b},a.proxy(function(c,d){this.$spinner.addClass("hidden");if("success"==d)if(c.success){a('<input type="hidden" name="password" value="'+b+'"/>').appendTo("#userform");var e=a("#newPassword");a("#email").add(e).removeClass("disabled").removeAttr("disabled");this.$lockBtns.remove();new Craft.PasswordInput(e);this.modal.hide()}else Garnish.shake(this.modal.$container)},this)))}})})(jQuery);

//# sourceMappingURL=AccountSettingsForm.min.map
