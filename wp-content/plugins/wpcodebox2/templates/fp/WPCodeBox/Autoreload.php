<?php

namespace WPCodeBox;


class Autoreload
{
    public function outputAutoreload()
    {

        $this->registerAutoreloadEndpoint();

        add_action('wp_head', function () {
            if (!current_user_can('manage_options')) {
                die;
				return;
            }

			if(!defined('WPCODEBOX2_VERSION')) {
				die;
				return;
			}

            $url = admin_url('admin-ajax.php');
            $js = <<<EOD
 <script type='text/javascript'>

	addEventListener("storage", (ev) =>{

        if(ev.key === 'wpcb2Reload') {

            document.querySelectorAll('.wpcb2-inline-style').forEach(function(current){

                let snippetIds = current.getAttribute('wpcb-ids');
                let formData = new FormData();
                formData.append('action', 'wpcb2_get_dev_code');
                formData.append('snippet_ids', snippetIds);


                fetch('$url', {
                      method: "POST",
                      body: formData,
                      credentials: 'same-origin',

                    }).then(response => response.json())
                     .then(response => {
                        let parent = current.parentNode;
                        current.setAttribute('disabled',true);
                        parent.removeChild(current);

                        parent.innerHTML = parent.innerHTML + '<style class="wpcb2-inline-style" wpcb-ids="' + snippetIds + '">' + response.code + '</style>' ;
                     });
                });


                document.querySelectorAll('.wpcb-external-style').forEach(function(current){

                    let href = current.getAttribute('href');

                    if(href.includes('wpcb_rand')) {
                        href += Math.floor(Math.random() * 20);
                    } else {
                        href += '&wpcb_rand=' +  Math.floor(Math.random() * 1000);
                    }

                    current.setAttribute('href', href);

                });

        }
    });

                </script>
EOD;
            echo $js;

        });
    }

    private function registerAutoreloadEndpoint()
    {
		add_action('wp_ajax_wpcb2_get_dev_code', function () {

			if (!current_user_can('manage_options')) {
				wp_die();
				return;
			}
			if(!defined('WPCODEBOX2_VERSION')) {
				wp_die();
				return;
			}

			if (function_exists('session_write_close')) {
				session_write_close();
			}

			$snippet_ids = $_POST['snippet_ids'];

			$snippet_ids = explode(",", $snippet_ids);

			$code = "";
			$snippetRepository = new \Wpcb2\Repository\SnippetRepository();

			foreach ($snippet_ids as $snippet_id) {
				$snippet = $snippetRepository->getSnippet($snippet_id);

				$snippet_code = $snippet['code'];

				$code .= $snippet_code . "\n";
			}

			echo json_encode(['code' => $code]);
			die;


		});
    }
}
