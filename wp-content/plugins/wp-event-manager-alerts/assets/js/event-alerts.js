jQuery(document).ready(function($) {
    
    //This is added in the js file because when calendar is installed then it will not show any alerts .
    localStorage.setItem("layout", "line-layout");

	$('.event-manager-chosen-select').chosen();

	$('.event-alerts-action-delete').click(function() {
		var answer = confirm( event_manager_alerts.i18n_confirm_delete );
		if (answer)
			return true;

		return false;
	});
});