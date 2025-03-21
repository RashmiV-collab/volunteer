<?php


namespace Wpcb2\Actions;


use Wpcb2\Service\SCSSUpdater;


class DeleteSnippet
{
    public function execute($id)
    {

        $response = array();

        $externalFileService = new \Wpcb2\Service\ExternalFile();
        $externalFileService->deleteFile($id);

        $fp = new \Wpcb2\FunctionalityPlugin\Manager(\get_option('wpcb_functionality_plugin_name'));
        $fp->deleteSnippet($id);


		$snippetRepository = new \Wpcb2\Repository\SnippetRepository();
		$snippetRepository->deleteSnippet($id);
        echo json_encode([]);
        die;
    }
}
