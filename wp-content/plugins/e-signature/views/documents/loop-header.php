<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

?>

<?php
// To default a var, add it to an array
$vars = array(
	'awaiting_class' // will default $data['awaiting_class']
);
$this->default_vals($data, $vars);

include($this->rootDir . ESIG_DS . 'partials/_tab-nav.php'); ?>

<div class="esig-dashboard-newdoc-section">
	<div>
		<a class="add-new-h2" href="admin.php?page=esign-view-document"><?php _e('Add New Document', 'esig'); ?></a>
	</div>
	<div class="esig-search-container">
		<?php echo $data['esig_document_search_box']; ?>
	</div>
</div>
<div class="e-sign-green-alert" id="sad-invite-send-success" style="display:none">    
	<div class="message"><?php _e('Congrats! You have successfully sent out a document.', 'esig'); ?></div>
</div>

<?php

echo $data['message'];

if (class_exists('WP_E_Notice')) {
	$esig_notice = new WP_E_Notice();

	echo $esig_notice->esig_print_notice();
}

echo do_action("esig_display_alert_message");

?>

<?php echo $data['loop_head']; ?>

<div class="header_left">
	<ul class="subsubsub">
		<!--<li class="all"><a class="<?php echo $data['all_class']; ?>" href="<?php echo $data['manage_all_url']; ?>" title="View all documents">Active Documents</a> <span class="count">(<?php echo $data['document_total']; ?>)</span> |</li>-->
		<li class="awaiting"><a class="<?php echo $data['awaiting_class']; ?>" href="<?php echo $data['manage_awaiting_url']; ?>" title="View documents currently awaiting signatures"><?php _e("Awaiting Signatures", "esig"); ?> <span class="count">(<?php echo $data['total_awaiting']; ?>)</span></a> |</li>
		<li class="draft"><a class="<?php echo $data['draft_class']; ?>" href="<?php echo $data['manage_draft_url']; ?>" title="View documents in draft mode"><?php _e("Draft", "esig"); ?> <span class="count">(<?php echo $data['total_draft']; ?>)</span></a> |</li>
		<li class="signed"><a class="<?php echo $data['signed_class']; ?>" href="<?php echo $data['manage_signed_url']; ?>" title="View signed documents"><?php _e("Signed", "esig"); ?> <span class="count">(<?php echo $data['total_signed']; ?>)</span></a> |</li>
		<li class="trash"><a class="<?php echo $data['trash_class']; ?>" href="<?php echo $data['manage_trash_url']; ?>" title="View documents in trash"><?php _e("Trash", "esig"); ?> <span class="count">(<?php echo $data['total_trash']; ?>)</span></a></li>
		<?php echo $data['document_filters']; ?>

	</ul>
</div>

<div class="header_right">

</div>

<form name="esig_document_form" action="" method="post">

	<div class="esig-documents-list-wrap">
		<table cellspacing="0" class="wp-list-table widefat fixed esig-documents-list">
			<thead>
				<tr>
					<th id="cb" class="check-column">
						<input name="selectall" type="checkbox" id="selectall" class="selectall" value="">
					</th>
					<th style="width: 245px;"><a class="orberby-link" href="?<?php echo esigAddQueryString(array("orderby" => "document_title")); ?>"><?php _e('Title', 'esig'); ?> </a></th>

					<?php
					$isArray = array('stand_alone', 'esig_template');
					$docStatus = esigget('document_status');
					if (in_array($docStatus, $isArray)) {
					?>
						<th style="width: 145px;"><a class="orberby-link" href="?<?php echo esigAddQueryString(array("orderby" => "date_created")); ?>"><?php _e('Created date', 'esig'); ?></a></th>
						<th style="width: 160px;"><a class="orberby-link" href="?<?php echo esigAddQueryString(array("orderby" => "last_modified")); ?>"><?php _e('Last modified', 'esig'); ?></a></th>
						<th style="width: 100px;"><?php _e('Created by', 'esig'); ?></th>
					<?php } else { ?>
						<th style="width: 145px;"><?php _e('Signer(s)', 'esig'); ?></th>
						<th style="width: 160px;"><?php _e('Latest Activity', 'esig'); ?></th>
						<th style="width: 100px;"><a class="orberby-link" href="?<?php echo esigAddQueryString(array("orderby" => "date_created")); ?>"><?php _e('Date', 'esig'); ?></a></th>
					<?php } ?>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<th id="cb" class="manage-column column-cb  check-column">
						<input name="selectall1" type="checkbox" id="selectall1" class="selectall" value="">
					</th>
					<th><?php _e('Title', 'esig'); ?></th>

					<?php

					if (in_array($docStatus, $isArray)) {
					?>
						<th style="width: 145px;"><?php _e('Created date', 'esig'); ?></th>
						<th style="width: 160px;"><?php _e('Last modified', 'esig'); ?></th>
						<th style="width: 100px;"><?php _e('Created by', 'esig'); ?></th>
					<?php } else { ?>
						<th style="width: 145px;"><?php _e('Signer(s)', 'esig'); ?></th>
						<th style="width: 160px;"><?php _e('Latest Activity', 'esig'); ?></th>
						<th style="width: 100px;"><?php _e('Date', 'esig'); ?></th>
					<?php } ?>
				</tr>
			</tfoot>
			<tbody>