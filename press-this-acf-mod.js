// Custom Press This ACF Mod JS for handling custom hierarchical taxonomy selection and form submission
(function($){
	function prepareCustomTaxonomiesFormData() {
		var $form = $('#pressthis-form');
		// For each custom hierarchical taxonomy block
		$('ul[class^="custom-taxonomy-select-"]').each(function(){
			var $ul = $(this);
			var taxonomyMatch = $ul.attr('class').match(/^custom-taxonomy-select-(.+)$/);

			if (!taxonomyMatch) return;
			var taxonomy = taxonomyMatch[1];
			$ul.find('.category.selected').each(function(){
				var termId = $(this).attr('data-term-id') || '';
				var $input = $('<input>', {
					type: 'hidden',
					name: 'tax_input['+taxonomy+'][]',
					value: termId
				});
				$form.append($input);
			});
		});
	}

	function toggleCustomTaxItem($element) {
	if ($element.hasClass('selected')) {
		$element.removeClass('selected').attr('aria-checked', 'false').removeAttr('checked');
	} else {
		$element.addClass('selected').attr('aria-checked', 'true').attr('checked', 'checked');
	}
}

function monitorCustomTaxList() {
	$('ul[class^="custom-taxonomy-select-"]').on('click.press-this keydown.press-this', function(event) {
		var $element = $(event.target);
		if ($element.is('div.category')) {
			if (event.type === 'keydown' && event.keyCode !== 32) {
				return;
			}
			toggleCustomTaxItem($element);
			updateCustomTaxonomyInputs();
			event.preventDefault();
		}
	});
}

function updateCustomTaxonomyInputs() {
	var $form = $('#pressthis-form');
	// Remove all existing custom taxonomy hidden inputs
	$form.find('input[data-pt-acf-mod-taxonomy]').remove();
	$('ul[class^="custom-taxonomy-select-"]').each(function(){
		var $ul = $(this);
		var taxonomyMatch = $ul.attr('class').match(/^custom-taxonomy-select-(.+)$/);
		if (!taxonomyMatch) return;
		var taxonomy = taxonomyMatch[1];
		$ul.find('.category.selected').each(function(){
			var termId = $(this).attr('data-term-id') || '';
			var $input = $('<input>', {
				type: 'hidden',
				name: 'tax_input['+taxonomy+'][]',
				value: termId,
				'data-pt-acf-mod-taxonomy': taxonomy
			});
			$form.append($input);
		});
	});
}


	$(document).ready(function(){
		monitorCustomTaxList();
	});
})(jQuery);
