<?php


namespace Wpcb2\Actions;


use Wpcb2\FunctionalityPlugin\Manager;
use Wpcb2\Repository\SnippetRepository;
use Wpcb2\Service\ExternalFile;
use Wpcb2\Service\Minify\MinifyFactory;
use Wpcb2\Service\SCSSUpdater;

class UpdateSnippet
{
    public function execute($id)
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
        if ($data['title'] === '') {
            $data['title'] = 'Untitled';
        }

		$snippetRepository = new SnippetRepository();
		$snippet = $snippetRepository->getSnippet($id);
        $oldTitle = $snippet['title'];

		$snippetData = [
			'title' => $data['title'],
			'description' => isset($data['description']) ? $data['description'] : '',
			'priority' => $data['priority'],
			'runType' => $data['runType']['value'],
			'original_code' => $data['code'],
			'codeType' => $data['codeType']['value'],
			'conditions' => $data['conditions'],
			'location' => is_array($data['location']) ? $data['location']['value'] : '',
			'hook' => $data['hook']
		];

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

                $codeArr['code'] = "<script " . $tagOptions . " src='" . $data['externalUrl']. "'></script>";
                $codeArr['tagOptions'] = $data['tagOptions'];
                $codeArr['externalUrl'] = $data['externalUrl'];

            } else if($data['codeType']['value'] === 'ex_css') {

                $codeArr['code'] = '<link rel="stylesheet" href="' . $data['externalUrl'] . '"/>';
                $codeArr['externalUrl'] = $data['externalUrl'];
            }

			$snippetData['code'] = json_encode($codeArr);
        }
        if(isset($data['renderType']) && is_array($data['renderType'])) {
			$snippetData['renderType'] = $data['renderType']['value'];
		}
        if(isset($data['minify'])) {
			$snippetData['minify'] = $data['minify'];
        }

        if(isset($data['addToQuickActions'])) {
			$snippetData['addToQuickActions'] = $data['addToQuickActions'];
        }

        if (isset($data['saved_to_cloud']) && $data['saved_to_cloud']) {
			$snippetData['saved_to_cloud'] = $data['saved_to_cloud'];
		}

        if(isset($data['tagOptions'])) {
			$snippetData['tagOptions'] = $data['tagOptions'];
        }

        if(isset($data['externalUrl'])) {
			$snippetData['externalUrl'] = $data['externalUrl'];
		}

		$snippetData['lastModified'] = time();

		$snippetRepository->updateSnippet($id, $snippetData);

        try {
            $errorPost = false;
            // Recompile the code that uses this partial
            if ($data['codeType']['value'] === 'scssp') {


				$snippetsThatUsePartial = $snippetRepository->getSnippetsThatUsePartial($data['title']);

                if (is_array($snippetsThatUsePartial) && count($snippetsThatUsePartial) > 0) {
                    foreach ($snippetsThatUsePartial as $snippetThatUsePartial) {

                        $errorPost = $snippetThatUsePartial['title'];

						if($snippetThatUsePartial['codeType'] === 'scss') {
                            $scssUpdater = new SCSSUpdater();
                            $scssUpdater->recompileCode($snippetThatUsePartial);
                        }
                    }
                }
            }
        } catch (\ScssPhp\ScssPhp\Exception\SassException $e) {
            $code = '';
            $error = $e->getMessage(). ' in SCSS Snippet: '.$errorPost;
        }

        $externalFileService = new ExternalFile();

        if(isset($data['renderType']) && is_array($data['renderType']) && $data['renderType']['value'] === 'external') {
            $extension = $data['codeType']['value'];
            if($extension === 'scss' || $extension === 'less') {
                $extension = 'css';
            }
            $externalFileService->writeContentToFile($id. '.' . $extension, $code);
        } else {
            $externalFileService->deleteFile($id);
        }

        $functionalityPlugin = new Manager(\get_option('wpcb_functionality_plugin_name'));

        if($oldTitle !== $data['title']) {
            $functionalityPlugin->renameSnippet($id, $oldTitle, $data['title']);
        }

        $functionalityPlugin->saveSnippet($id);

        if($data['codeType']['value'] === 'css' && isset($data['renderType']) && $data['renderType'] !== 'external') {
            $functionalityPlugin->handleCss();
        }

        if($data['codeType']['value'] === 'js' && isset($data['renderType']) && $data['renderType'] !== 'external') {
            $functionalityPlugin->handleJS();
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
