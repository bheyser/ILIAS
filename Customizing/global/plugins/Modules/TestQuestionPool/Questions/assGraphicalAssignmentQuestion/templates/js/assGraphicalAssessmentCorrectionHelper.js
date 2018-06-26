il.assGraphicalAssessmentCorrectionHelper = (function (scope) {
	'use strict';

	var pub = {}, pro = {
		'css_class' : '.clone_fields_add'
	};

	pub.init = function()
	{
		pro.registerOverviewListener();
	};
	
	pro.registerOverviewListener = function()
	{
		$(pro.css_class).off('click');

		$(pro.css_class).on('click', function() {
			pro.insertNewFormRowAndValues($(this));
		});
	};

	pro.registerRemoveListener = function(first_element, that)
	{
		$(first_element + ' .answwzd:last .answerwizard_remove').on('click');
		$(first_element + ' .answwzd:last .answerwizard_remove').on('click', function() {
			that.show();
		});
	};
	
	pro.insertNewFormRowAndValues = function(that)
	{
		var position = pro.parsePosition(that.attr('name'));
		if(position !== false)
		{
			var first_element = '#items_' + position;
			$(first_element + ' .answwzd:last .answerwizard_add').click();
			that.hide();
			pro.insertValues(first_element, that);
			pro.registerRemoveListener(first_element, that);
		}
	};
	
	pro.insertValues = function(first_element, that)
	{
		$(first_element + ' .answwzd:last input:first').val(that.data('answer'))
		$(first_element + ' .answwzd:last input').eq(1).val(0);
	};
	
	pro.parsePosition = function(name)
	{
		var result = name.split('_');
		if(result.length === 4)
		{
			return result[2];
		}
		return false;
	};
	
	pub.protect = pro;
	return pub;

}(il));

$( document ).ready(function() {
	il.assGraphicalAssessmentCorrectionHelper.init();
});