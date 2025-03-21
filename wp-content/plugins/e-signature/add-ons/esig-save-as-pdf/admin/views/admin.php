<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

?>

<?php

include($this->rootDir . ESIG_DS . 'partials/_tab-nav.php');

// To default a var, add it to an array
$vars = array(
	'other_form_element', // will default $data['other_form_element']
	'pdf_options',
	'active_campaign_options'
);
$this->default_vals($data, $vars);
?>

<div class="esign-main-tab">

	<a class="misc_link " href="admin.php?page=esign-misc-general"><?php _e('General Option', 'esig'); ?></a>

	| <a class="misc_link" href="admin.php?page=esign-mails-general"><?php _e('White Label Option', 'esig'); ?></a>


	<?php echo $data['customizztion_more_links']; ?>

</div>
<h3>Save as PDF Settings</h3>

<?php echo esigget('message',$data); ?>

<form name="settings_form" class="settings-form" method="post" action="<?php echo $data['post_action']; ?>">
	<table class="form-table">
		<tbody>

			<tr>
				<td>
					<p>
					<h4 class="esig-pdf-heading"> <?php _e('Save as PDF <span class="description"> page format:</span>', 'esig'); ?> </h4>

					<select style="width:500px;" data-placeholder="Choose a Option..." name="esig_pdf_page_format" class="esig-select2" tabindex="9">

						<?php
								foreach(esigPdfSetting::supportedPageFormat() as $format)
								{
									$selected =(esigPdfSetting::pageFormat() === $format) ? "selected" : false;
									echo '<option value="' . $format . '" '. $selected .'>' . $format . '</option> ' ; 
								}
						?>
						
					</select>

					</p>
				</td>
			</tr>

			<tr>
				<td>
					<span id="advanced-settings">
						<?php echo $data['pdf_options']; ?>
					</span>
				</td>
			</tr>

			<tr>
				<td>
					<?php echo $data['other_form_element']; ?>
				</td>
			</tr>


		</tbody>
	</table>


	<p>
		<input type="submit" name="esig-pdf-option-submit" class="button-appme button" value="Save Settings" />
	</p>
</form>