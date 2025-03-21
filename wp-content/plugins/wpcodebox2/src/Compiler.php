<?php

namespace Wpcb2;


use Wpcb2\Repository\SnippetRepository;

class Compiler
{
    /**
     * @param $data
     * @return string
     */
    public function compileCode($code, $codeType)
    {
        if ($codeType === 'scss' || $codeType === 'scssp') {

            if(!class_exists('\ScssPhp\ScssPhp\Compiler')) {
                require_once __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "scssphp" . DIRECTORY_SEPARATOR . "scss.inc.php";
            }

			$snippetRepository = new SnippetRepository();

            $compiler = new \ScssPhp\ScssPhp\Compiler();

            $matches = [];
            preg_match_all("/\@use *'(_.*?)';/", $code, $matches);
            $partials = $matches[1];


            foreach($partials as $partialKey => $partialSlug) {

				$partialSnippet = $snippetRepository->getSnippetByTitle($partialSlug);
                if($partialSnippet) {
					$partialCode = $partialSnippet['original_code'];
                    $code = str_replace($matches[0][$partialKey], $partialCode . "\n\n", $code);
                }
            }

            $compiler->setImportPaths([ABSPATH . 'wp-content']);
            if(method_exists($compiler, 'compileString')) {
                $code = $compiler->compileString($code)->getCss();
            } else {
                $code = $compiler->compile($code);
            }
        }

        if ($codeType === 'less') {

            if(!class_exists('\lessc')) {
                require_once __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "lessphp" . DIRECTORY_SEPARATOR . "lessc.inc.php";
            }

            $less = new \lessc();

            try {
                $code = $less->compile($code);
            } catch (\Exception $e) {

                echo json_encode([
                    'error' => true,
                    'message' => $e->getMessage()
                ]);

                die;
            }
         }

        return $code;
    }
}
