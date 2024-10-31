jQuery(document).ready(function()
{
	jQuery('.ipbrecent').on('click', '.list-toggle', function() {
		target = '#' + jQuery(this).closest('.ipbrecent').attr('id') + ' .list-items';
		
		if (jQuery(target).is(':visible')) {
			jQuery(this).find('.fa').removeClass('fa-chevron-down').addClass('fa-chevron-left');
			jQuery(target).slideUp();
		} else {
			jQuery(this).find('.fa').removeClass('fa-chevron-left').addClass('fa-chevron-down');
			jQuery(target).slideDown();
		}
	});
});