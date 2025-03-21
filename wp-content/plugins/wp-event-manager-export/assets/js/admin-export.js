jQuery(document).ready(function($) {
	if (jQuery('select[data-multiple="multiple"]').length > 0){
  	jQuery('select[data-multiple="multiple"]').chosen();
  }

  var $product_screen = jQuery('.edit-php.post-type-event_listing');
  $event_action   = $product_screen.find('.page-title-action:first');
  
  $event_action.after('<a href="' + event_manager_export_admin_export.export_events_url + '" class="page-title-action">' + event_manager_export_admin_export.export_events_text + '</a>');
  // $event_action.after('<a href="' + event_manager_export_admin_export.export_events_xlsx_url + '" class="page-title-action">' + event_manager_export_admin_export.export_events_xlsx_text + '</a>');
  $event_action.after('<a href="' + event_manager_export_admin_export.export_events_xls_url + '" class="page-title-action">' + event_manager_export_admin_export.export_events_xls_text + '</a>');
  $event_action.after('<a href="' + event_manager_export_admin_export.export_events_xml_url + '" class="page-title-action">' + event_manager_export_admin_export.export_events_xml_text + '</a>');

  var $product_screen = jQuery('.edit-php.post-type-event_organizer');
  $organizer_action   = $product_screen.find('.page-title-action:first');
  
  $organizer_action.after('<a href="' + event_manager_export_admin_export.export_organizers_url + '" class="page-title-action">' + event_manager_export_admin_export.export_organizers_text + '</a>');
  // $organizer_action.after('<a href="' + event_manager_export_admin_export.export_organizers_xlsx_url + '" class="page-title-action">' + event_manager_export_admin_export.export_organizers_xlsx_text + '</a>');
  $organizer_action.after('<a href="' + event_manager_export_admin_export.export_organizers_xls_url + '" class="page-title-action">' + event_manager_export_admin_export.export_organizers_xls_text + '</a>');
  $organizer_action.after('<a href="' + event_manager_export_admin_export.export_organizers_xml_url + '" class="page-title-action">' + event_manager_export_admin_export.export_organizers_xml_text + '</a>');

  var $product_screen = jQuery('.edit-php.post-type-event_venue');
  $venue_action   = $product_screen.find('.page-title-action:first');
  
  $venue_action.after('<a href="' + event_manager_export_admin_export.export_venues_url + '" class="page-title-action">' + event_manager_export_admin_export.export_venues_text + '</a>');
  // $venue_action.after('<a href="' + event_manager_export_admin_export.export_venues_xlsx_url + '" class="page-title-action">' + event_manager_export_admin_export.export_venues_xlsx_text + '</a>');
  $venue_action.after('<a href="' + event_manager_export_admin_export.export_venues_xls_url + '" class="page-title-action">' + event_manager_export_admin_export.export_venues_xls_text + '</a>');
  $venue_action.after('<a href="' + event_manager_export_admin_export.export_venues_xml_url + '" class="page-title-action">' + event_manager_export_admin_export.export_venues_xml_text + '</a>');
});