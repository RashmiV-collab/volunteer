<?php


namespace Wpcb2\Actions;


use Wpcb2\FunctionalityPlugin\Manager;
use Wpcb2\Repository\SnippetRepository;
use Wpcb2\Service\ExternalFile;
use Wpcb2\Service\HookMapper;
use Wpcb2\Service\Minify\MinifyFactory;


class CreateSnippet
{
    public function execute()
    {

        $error = false;

        $data = file_get_contents("php://input");
        $data = json_decode($data, true);

        try {
            $compiler = new \Wpcb2\Compiler();
            $code = $compiler->compileCode($data['code'], $data['codeType']['value']);

        } catch (\ScssPhp\ScssPhp\Exception\SassException $e) {
            $code = '';

            $error = $e->getMessage();
        }

        if(isset($data['minify']) && $data['minify']) {
            $minifyFactory = new MinifyFactory();
            $minifyService = $minifyFactory->createMinifyService($data['codeType']['value']);
            $code = $minifyService->minify($code);
        }

        if ($data['title'] == '') {
            $data['title'] = 'Untitled';
        }

		if(!isset($data['priority'])) {
			$data['priority'] = 10;
		}

		if(!isset($data['conditions'])) {
			$data['conditions'] = [];
		}

		if(!isset($data['hook'])) {
			$data['hook'] = HookMapper::getDefaultHooks($data['codeType']['value']);
		}

        $snippetData =[
            'title' => $data['title'],
            'description' => isset($data['description']) ? $data['description'] : '',
            'priority' => $data['priority'],
			'runType' => $data['runType']['value'],
			'original_code' => $data['code'],
			'codeType' => $data['codeType']['value'],
			'conditions' => $data['conditions'],
			'location' => is_array($data['location']) ? $data['location']['value'] : '',
			'hook' => $data['hook'],
			'snippet_order' => -1
        ];

        if($data['codeType']['value'] === 'php') {
            $token = openssl_random_pseudo_bytes(16);
            $token = bin2hex($token);
            $token = sha1(uniqid().wp_salt().$token);

			$snippetData['secret'] = $token;
        }

        if($data['codeType']['value'] !== 'ex_js' && $data['codeType']['value'] !== 'ex_css') {
			$snippetData['code'] = $code;
        } else {

            $codeArr = [];

            if($data['codeType']['value'] === 'ex_js') {

                $tagOptions = "";
                foreach($data['tagOptions'] as $value) {
                    if(isset($value['value'])) {
                        if ($value['value'] === 'async') {
                            $tagOptions .= " async ";
                        } else if ($value['value'] === 'defer') {
                            $tagOptions .= " defer ";
                        }
                    }
                }

                $codeArr['code'] = "<script " . $tagOptions . " src='" . $data['externalUrl']. "' ></script>";
                $codeArr['tagOptions'] = $data['tagOptions'];
                $codeArr['externalUrl'] = $data['externalUrl'];

            } else if($data['codeType']['value'] === 'ex_css') {

                $codeArr['code'] = '<link rel="stylesheet" href="' . $data['externalUrl'] . '"/>';
                $codeArr['externalUrl'] = $data['externalUrl'];
            }

			$snippetData['code'] = json_encode($codeArr);
		}

        if(isset($data['tagOptions'])) {
        	$snippetData['tagOptions'] = $data['tagOptions'];
        }

        if(isset($data['renderType']) && is_array($data['renderType'])) {
			$snippetData['renderType'] = $data['renderType']['value'];
        }
        if(isset($data['minify'])) {
			$snippetData['minify'] = $data['minify'];
        }

		$snippetData['lastModified'] = time();

		$snippetRepository = new SnippetRepository();
		$id = $snippetRepository->createSnippet($snippetData);

        if(isset($data['renderType']) && is_array($data['renderType']) && $data['renderType']['value'] === 'external') {

            $extension = $data['codeType']['value'];
            if($extension === 'scss' || $extension === 'less') {
                $extension = 'css';
            }

            $externalFileService = new ExternalFile();
            $externalFileService->writeContentToFile($id. '.' . $extension, $code);
        }

        $functionalityPlugin = new Manager(\get_option('wpcb_functionality_plugin_name'));

        if($functionalityPlugin->isEnabled()) {
            $functionalityPlugin->saveSnippet($id);

            if ($data['codeType']['value'] === 'css') {
                $functionalityPlugin->handleCss();
            }

            if ($data['codeType']['value'] === 'js') {
                $functionalityPlugin->handleJS();
            }
        }

        if($error) {
            echo json_encode([
                'error' => true,
                'message' => $error
            ]);

            die;

        }

        echo json_encode(['post_id' => $id]);

        die;
    }
}
