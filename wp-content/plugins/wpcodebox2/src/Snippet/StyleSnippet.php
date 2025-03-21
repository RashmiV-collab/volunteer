<?php

namespace Wpcb2\Snippet;


class StyleSnippet extends Snippet
{

	function getCode()
	{

		$hooks = $this->getHook();

		$render_type = $this->snippetData['renderType'];

		$code = '';
		if ($render_type === 'external') {

			foreach ($hooks as $hook) {
				$priority = $hook['priority'];
				$hook = $hook['hook'];

				if($hook === 'custom_after_pagebuilders') {
					$hook = 'wp_head';
					$priority = 1000000;
				}

				if($hook === 'plugins_loaded') {
					$hook = 'wp_head';
				}

				$dir = wp_upload_dir();
				$wpcodeboxDir = $dir['baseurl'] . '/wpcodebox';

				$conditionCode = $this->getConditionCode();
				$version_hash = substr(md5($this->snippetData['lastModified']), 0, 16);
				$snippetUrl = $wpcodeboxDir . DIRECTORY_SEPARATOR . $this->snippetData['id'] . '.css?v=' . $version_hash;
				$code .= <<<EOD
add_action('$hook', function() {
        $conditionCode
        ?>
        <link rel="stylesheet" class="wpcb2-external-style" href="$snippetUrl"/>

    <?php
    }, $priority);


EOD;
			}

		} else {

			foreach ($hooks as $hook) {
				if($hook['hook'] === 'custom_after_pagebuilders') {
					$hook['hook'] = 'wp_head';
					$hook['priority'] = 1000000;
				}
                $this->globalCSS->addStyle($hook['hook'], $hook['priority'], $this->code, $this->snippetData['id']);
			}


			return false;

		}

		return $code;
	}
}
