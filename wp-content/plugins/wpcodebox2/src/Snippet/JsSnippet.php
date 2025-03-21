<?php

namespace Wpcb2\Snippet;


use Wpcb2\FunctionalityPlugin\Manager;

class JsSnippet extends Snippet
{

    function getCode()
    {
        $hooks = $this->getHook();

        $render_type = $this->snippetData['renderType'];

        $code = '';
        if ($render_type === 'external') {

            foreach ($hooks as $hook) {

				if($hook['hook'] === 'custom_after_pagebuilders') {
					$hook['hook'] = 'wp_head';
					$hook['priority'] = 1000000;
				}


				$priority = $hook['priority'];
                $hook = $hook['hook'];

				if($hook === 'plugins_loaded') {
					$hook = 'wp_head';
				}

                $dir = wp_upload_dir();

                $tagOptionsString = "";

                $tagOptions = $this->snippetData['tagOptions'];
				$tagOptions = json_decode($tagOptions, true);

                if (is_array($tagOptions)) {
                    foreach ($tagOptions as $value) {
                        if ($value['value'] === 'async') {
                            $tagOptionsString .= " async ";
                        } else if ($value['value'] === 'defer') {
                            $tagOptionsString .= " defer ";
                        }
                    }
                }

                $wpcodeboxDir = $dir['baseurl'] . '/wpcodebox';

                $version_hash = substr(md5($this->snippetData['lastModified']), 0, 16);


                if ($this->isFp) {
                    $fp = new Manager(\get_option('wpcb_functionality_plugin_name'));
                    $snippetCode = "\n" . '<script type="text/javascript" ' . $tagOptionsString . ' src="' . $fp->getAssetsUrl() . 'js' . DIRECTORY_SEPARATOR . $fp->slugify($this->snippetData['title']) . '_' . $this->snippetData['id'] . '.js?v=' . $version_hash . '"></script>' . "\n";
                } else {
                    $snippetCode = "\n" . '<script type="text/javascript" ' . $tagOptionsString . ' src="' . $wpcodeboxDir . DIRECTORY_SEPARATOR . $this->snippetData['id'] . '.js?v=' . $version_hash . '"></script>' . "\n";
                }
                $conditionCode = $this->getConditionCode();

                $code .= <<<EOD
add_action('$hook', function() {
    $conditionCode
?>
$snippetCode
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

                $this->globalJS->addScript($hook['hook'], $hook['priority'], $this->code, $this->snippetData['id']);
            }


            return false;
        }


        return $code;

    }
}
