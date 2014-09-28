/**
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.resources
 */

(function($){


/**
 * Matrix input class
 */
Craft.MatrixInput = Garnish.Base.extend(
{
	id: null,
	blockTypes: null,
	blockTypesByHandle: null,
	inputNamePrefix: null,
	inputIdPrefix: null,
	maxBlocks: null,

	$container: null,
	$blockContainer: null,
	$addBlockBtnContainer: null,
	$addBlockBtnGroup: null,
	$addBlockBtnGroupBtns: null,

	blockSort: null,
	totalNewBlocks: 0,

	init: function(id, blockTypes, inputNamePrefix, maxBlocks)
	{
		this.id = id
		this.blockTypes = blockTypes;
		this.inputNamePrefix = inputNamePrefix;
		this.inputIdPrefix = Craft.formatInputId(this.inputNamePrefix);
		this.maxBlocks = maxBlocks;

		this.$container = $('#'+this.id);
		this.$blockContainer = this.$container.children('.blocks');
		this.$addBlockBtnContainer = this.$container.children('.buttons');
		this.$addBlockBtnGroup = this.$addBlockBtnContainer.children('.btngroup');
		this.$addBlockBtnGroupBtns = this.$addBlockBtnGroup.children('.btn');
		this.$addBlockMenuBtn = this.$addBlockBtnContainer.children('.menubtn');

		this.setNewBlockBtn();

		this.blockTypesByHandle = {};

		for (var i = 0; i < this.blockTypes.length; i++)
		{
			var blockType = this.blockTypes[i];
			this.blockTypesByHandle[blockType.handle] = blockType;
		}

		var $blocks = this.$blockContainer.children(),
			collapsedBlocks = Craft.MatrixInput.getCollapsedBlockIds();

		this.blockSort = new Garnish.DragSort($blocks, {
			caboose: '<div/>',
			handle: '> .actions > .move',
			axis: 'y',
			helperOpacity: 0.9
		});

		for (var i = 0; i < $blocks.length; i++)
		{
			var $block = $($blocks[i]),
				id = $block.data('id');

			// Is this a new block?
			var newMatch = (typeof id == 'string' && id.match(/new(\d+)/));

			if (newMatch && newMatch[1] > this.totalNewBlocks)
			{
				this.totalNewBlocks = parseInt(newMatch[1]);
			}

			var block = new MatrixBlock(this, $block);

			if (block.id && $.inArray(''+block.id, collapsedBlocks) != -1)
			{
				block.collapse();
			}
		}

		this.addListener(this.$addBlockBtnGroupBtns, 'click', function(ev)
		{
			var type = $(ev.target).data('type');
			this.addBlock(type);
		});

		new Garnish.MenuBtn(this.$addBlockMenuBtn,
		{
			onOptionSelect: $.proxy(function(option)
			{
				var type = $(option).data('type');
				this.addBlock(type);
			}, this)
		});

		this.updateAddBlockBtn();

		this.addListener(this.$container, 'resize', 'setNewBlockBtn');
	},

	setNewBlockBtn: function()
	{
		if (this.$addBlockBtnGroup.removeClass('hidden').width() > this.$addBlockBtnGroup.parent().width())
		{
			this.$addBlockBtnGroup.addClass('hidden');
			this.$addBlockMenuBtn.removeClass('hidden');
		}
		else
		{
			this.$addBlockBtnGroup.removeClass('hidden');
			this.$addBlockMenuBtn.addClass('hidden');
		}
	},

	canAddMoreBlocks: function()
	{
		return (!this.maxBlocks || this.$blockContainer.children().length < this.maxBlocks);
	},

	updateAddBlockBtn: function()
	{
		if (this.canAddMoreBlocks())
		{
			this.$addBlockBtnGroup.removeClass('disabled');
			this.$addBlockMenuBtn.removeClass('disabled');
		}
		else
		{
			this.$addBlockBtnGroup.addClass('disabled');
			this.$addBlockMenuBtn.addClass('disabled');
		}
	},

	addBlock: function(type, $insertBefore)
	{
		if (!this.canAddMoreBlocks())
		{
			return;
		}

		this.totalNewBlocks++;

		var id = 'new'+this.totalNewBlocks;

		var html =
			'<div class="matrixblock" data-id="'+id+'">' +
				'<input type="hidden" name="'+this.inputNamePrefix+'['+id+'][type]" value="'+type+'"/>' +
				'<input type="hidden" name="'+this.inputNamePrefix+'['+id+'][enabled]" value="1"/>' +
				'<div class="actions">' +
					'<div class="status off" title="'+Craft.t('Disabled')+'"></div>' +
					'<a class="settings icon menubtn" title="'+Craft.t('Actions')+'" role="button"></a> ' +
					'<div class="menu" data-align="right">' +
						'<ul class="padded">' +
							'<li><a data-icon="collapse" data-action="collapse">'+Craft.t('Collapse')+'</a></li>' +
							'<li class="hidden"><a data-icon="expand" data-action="expand">'+Craft.t('Expand')+'</a></li>' +
							'<li><a data-icon="disabled" data-action="disable">'+Craft.t('Disable')+'</a></li>' +
							'<li class="hidden"><a data-icon="enabled" data-action="enable">'+Craft.t('Enable')+'</a></li>' +
						'</ul>' +
						'<hr class="padded"/>' +
						'<ul class="padded">';

		for (var i = 0; i < this.blockTypes.length; i++)
		{
			var blockType = this.blockTypes[i];
			html += '<li><a data-icon="+" data-action="add" data-type="'+blockType.handle+'">'+Craft.t('Add {type} above', { type: blockType.name })+'</a></li>';
		}

		html +=
						'</ul>' +
						'<hr class="padded"/>' +
						'<ul class="padded">' +
							'<li><a data-icon="remove" data-action="delete">'+Craft.t('Delete')+'</a></li>' +
						'</ul>' +
					'</div>' +
					'<a class="move icon" title="'+Craft.t('Reorder')+'" role="button"></a> ' +
				'</div>' +
			'</div>';

		var $block = $(html);

		if ($insertBefore)
		{
			$block.insertBefore($insertBefore);
		}
		else
		{
			$block.appendTo(this.$blockContainer);
		}

		var $fieldsContainer = $('<div class="fields"/>').appendTo($block),
			bodyHtml = this.getParsedBlockHtml(this.blockTypesByHandle[type].bodyHtml, id),
			footHtml = this.getParsedBlockHtml(this.blockTypesByHandle[type].footHtml, id);

		$(bodyHtml).appendTo($fieldsContainer);

		$block.css(this.getHiddenBlockCss($block)).velocity({
			opacity: 1,
			'margin-bottom': 10
		}, 'fast', $.proxy(function()
		{
			$block.css('margin-bottom', '');
			$('body').append(footHtml);
			Craft.initUiElements($fieldsContainer);
			new MatrixBlock(this, $block);
			this.blockSort.addItems($block);
			this.updateAddBlockBtn();
		}, this));
	},

	getHiddenBlockCss: function($block)
	{
		var marginBottom = -($block.outerHeight());

		if ($block.is(':only-child'))
		{
			// Without this block, there would only be a 4px gap between the field heading and the Add Block buttons
			marginBottom -= 10;
		}

		return {
			opacity: 0,
			marginBottom: marginBottom
		};
	},

	getParsedBlockHtml: function(html, id)
	{
		if (typeof html == 'string')
		{
			return html.replace(/__BLOCK__/g, id);
		}
		else
		{
			return '';
		}
	}
},
{
	collapsedBlockStorageKey: 'Craft-'+Craft.siteUid+'.MatrixInput.collapsedBlocks',

	getCollapsedBlockIds: function()
	{
		if (typeof localStorage[Craft.MatrixInput.collapsedBlockStorageKey] == 'string')
		{
			return Craft.filterArray(localStorage[Craft.MatrixInput.collapsedBlockStorageKey].split(','));
		}
		else
		{
			return [];
		}
	},

	setCollapsedBlockIds: function(ids)
	{
		localStorage[Craft.MatrixInput.collapsedBlockStorageKey] = ids.join(',');
	},

	rememberCollapsedBlockId: function(id)
	{
		if (typeof Storage !== 'undefined')
		{
			var collapsedBlocks = Craft.MatrixInput.getCollapsedBlockIds();

			if ($.inArray(''+id, collapsedBlocks) == -1)
			{
				collapsedBlocks.push(id);
				Craft.MatrixInput.setCollapsedBlockIds(collapsedBlocks);
			}
		}
	},

	forgetCollapsedBlockId: function(id)
	{
		if (typeof Storage !== 'undefined')
		{
			var collapsedBlocks = Craft.MatrixInput.getCollapsedBlockIds(),
				collapsedBlocksIndex = $.inArray(''+id, collapsedBlocks);

			if (collapsedBlocksIndex != -1)
			{
				collapsedBlocks.splice(collapsedBlocksIndex, 1);
				Craft.MatrixInput.setCollapsedBlockIds(collapsedBlocks);
			}
		}
	}
});


var MatrixBlock = Garnish.Base.extend(
{
	matrix: null,
	$container: null,
	$fieldsContainer: null,
	$previewContainer: null,
	$actionMenu: null,
	$collapsedInput: null,

	isNew: null,
	id: null,

	collapsed: false,

	init: function(matrix, $container)
	{
		this.matrix = matrix;
		this.$container = $container;
		this.$fieldsContainer = $container.children('.fields');

		this.id    = this.$container.data('id');
		this.isNew = (!this.id || (typeof this.id == 'string' && this.id.substr(0, 3) == 'new'));

		var $menuBtn = this.$container.find('> .actions > .settings'),
			menuBtn = new Garnish.MenuBtn($menuBtn);

		this.$actionMenu = menuBtn.menu.$container;

		menuBtn.menu.settings.onOptionSelect = $.proxy(this, 'onMenuOptionSelect');

		// Was this block already collapsed?
		if (Garnish.hasAttr(this.$container, 'data-collapsed'))
		{
			this.collapse();
		}

		this.addListener(this.$container, 'dblclick', function(ev)
		{
			// Was this in the top 30px?
			if (ev.pageY <= this.$container.offset().top + 30)
			{
				ev.preventDefault();
				this.toggle();
			}
		});
	},

	toggle: function()
	{
		if (this.collapsed)
		{
			this.expand();
		}
		else
		{
			this.collapse(true);
		}
	},

	collapse: function(animate)
	{
		if (this.collapsed)
		{
			return;
		}

		if (!this.$previewContainer)
		{
			this.$previewContainer = $('<div class="preview" style="display: none;"/>').appendTo(this.$container);
		}

		var previewHtml = '',
			$fields = this.$fieldsContainer.children();

		for (var i = 0; i < $fields.length; i++)
		{
			var $field = $($fields[i]),
				$inputs = $field.children('.input').find('select,input[type!="hidden"],textarea,.label'),
				inputPreviewText = '';

			for (var j = 0; j < $inputs.length; j++)
			{
				var $input = $($inputs[j]);

				if ($input.hasClass('label'))
				{
					var $maybeLightswitchContainer = $input.parent().parent();

					if ($maybeLightswitchContainer.hasClass('lightswitch') && (
						($maybeLightswitchContainer.hasClass('on') && $input.hasClass('off')) ||
						(!$maybeLightswitchContainer.hasClass('on') && $input.hasClass('on'))
					))
					{
						continue;
					}

					var value = $input.text();
				}
				else
				{
					var value = Craft.getText(Garnish.getInputPostVal($input));
				}

				if (value instanceof Array)
				{
					value = value.join(', ');
				}

				if (value)
				{
					value = Craft.trim(value);

					if (value)
					{
						if (inputPreviewText)
						{
							inputPreviewText += ', ';
						}

						inputPreviewText += value;
					}
				}
			}

			if (inputPreviewText)
			{
				if (previewHtml)
				{
					previewHtml += ' <span>|</span> ';
				}

				previewHtml += '<strong>'+Craft.trim($field.children('.heading').text())+':</strong> '+inputPreviewText;
			}
		}

		this.$previewContainer.html(previewHtml);

		this.$previewContainer.velocity('stop');
		this.$fieldsContainer.velocity('stop');
		this.$container.velocity('stop');

		if (animate)
		{
			this.$previewContainer.velocity('fadeIn', { duration: 'fast' });
			this.$fieldsContainer.velocity('fadeOut', { duration: 'fast' });
			this.$container.velocity({ height: 0 }, 'fast');
		}
		else
		{
			this.$previewContainer.show();
			this.$fieldsContainer.hide();
			this.$container.css({ height: 0 });
		}

		setTimeout($.proxy(function() {
			this.$actionMenu.find('a[data-action=collapse]:first').parent().addClass('hidden');
			this.$actionMenu.find('a[data-action=expand]:first').parent().removeClass('hidden');
		}, this), 200);

		// Remember that?
		if (!this.isNew)
		{
			Craft.MatrixInput.rememberCollapsedBlockId(this.id);
		}
		else
		{
			if (!this.$collapsedInput)
			{
				this.$collapsedInput = $('<input type="hidden" name="'+this.matrix.inputNamePrefix+'['+this.id+'][collapsed]" value="1"/>').appendTo(this.$container);
			}
			else
			{
				this.$collapsedInput.val('1');
			}
		}

		this.collapsed = true;
	},

	expand: function()
	{
		if (!this.collapsed)
		{
			return;
		}

		this.$previewContainer.velocity('stop');
		this.$fieldsContainer.velocity('stop');
		this.$container.velocity('stop');

		var collapsedContainerHeight = this.$container.height();
		this.$container.height('auto');
		this.$fieldsContainer.show();
		var expandedContainerHeight = this.$container.height();
		this.$container.height(collapsedContainerHeight);
		this.$fieldsContainer.hide().velocity('fadeIn', { duration: 'fast' });
		this.$previewContainer.velocity('fadeOut', { duration: 'fast' });
		this.$container.velocity({ height: expandedContainerHeight }, 'fast', $.proxy(function() {
			this.$container.height('auto');
		}, this));

		setTimeout($.proxy(function() {
			this.$actionMenu.find('a[data-action=collapse]:first').parent().removeClass('hidden');
			this.$actionMenu.find('a[data-action=expand]:first').parent().addClass('hidden');
		}, this), 200);

		// Remember that?
		if (!this.isNew && typeof Storage !== 'undefined')
		{
			var collapsedBlocks = Craft.MatrixInput.getCollapsedBlockIds(),
				collapsedBlocksIndex = $.inArray(''+this.id, collapsedBlocks);

			if (collapsedBlocksIndex != -1)
			{
				collapsedBlocks.splice(collapsedBlocksIndex, 1);
				Craft.MatrixInput.setCollapsedBlockIds(collapsedBlocks);
			}
		}

		if (!this.isNew)
		{
			Craft.MatrixInput.forgetCollapsedBlockId(this.id);
		}
		else if (this.$collapsedInput)
		{
			this.$collapsedInput.val('');
		}

		this.collapsed = false;
	},

	disable: function()
	{
		this.$container.children('input[name$="[enabled]"]:first').val('');
		this.$container.addClass('disabled');

		setTimeout($.proxy(function() {
			this.$actionMenu.find('a[data-action=disable]:first').parent().addClass('hidden');
			this.$actionMenu.find('a[data-action=enable]:first').parent().removeClass('hidden');
		}, this), 200);

		this.collapse(true);
	},

	enable: function()
	{
		this.$container.children('input[name$="[enabled]"]:first').val('1');
		this.$container.removeClass('disabled');

		setTimeout($.proxy(function() {
			this.$actionMenu.find('a[data-action=disable]:first').parent().removeClass('hidden');
			this.$actionMenu.find('a[data-action=enable]:first').parent().addClass('hidden');
		}, this), 200);

		this.expand();
	},

	onMenuOptionSelect: function(option)
	{
		var $option = $(option);

		switch ($option.data('action'))
		{
			case 'collapse':
			{
				this.collapse(true);
				break;
			}

			case 'expand':
			{
				this.expand();
				break;
			}

			case 'disable':
			{
				this.disable();
				break;
			}

			case 'enable':
			{
				this.enable();
				break;
			}

			case 'add':
			{
				var type = $option.data('type');
				this.matrix.addBlock(type, this.$container);
				break;
			}

			case 'delete':
			{
				this.selfDestruct();
				break;
			}
		}
	},

	selfDestruct: function()
	{
		this.$container.velocity(this.matrix.getHiddenBlockCss(this.$container), 'fast', $.proxy(function()
		{
			this.$container.remove();
			this.matrix.updateAddBlockBtn();
		}, this));
	}
});


})(jQuery);
