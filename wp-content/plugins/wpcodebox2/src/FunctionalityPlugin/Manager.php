<?php

namespace Wpcb2\FunctionalityPlugin;


use Wpcb2\Repository\SnippetRepository;
use Wpcb2\Snippet\GlobalCSS;
use Wpcb2\Snippet\GlobalJS;
use Wpcb2\Snippet\JsSnippet;
use Wpcb2\Snippet\PhpSnippet;
use Wpcb2\Snippet\SnippetFactory;
use Wpcb2\Snippet\StyleSnippet;

class Manager
{

    private $path;
    private $mainFile;
    private $wpcodeboxPath = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'wpcodebox2' . DIRECTORY_SEPARATOR;
    private $pluginName;


    public function __construct($pluginName)
    {

        if(!$pluginName) {
            $pluginName = 'WPCodeBox Functionality Plugin';
        }
        $this->pluginName = $this->slugify($pluginName);
        $this->path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->pluginName . DIRECTORY_SEPARATOR;
        $this->mainFile = $this->path . 'plugin.php';

    }

    public function isEnabled()
    {
        return file_exists($this->wpcodeboxPath . '.fcpe');
    }

    public function getAssetsUrl()
    {
        return \plugin_dir_url($this->pluginName . '/plugin.php') . 'assets' . DIRECTORY_SEPARATOR;
    }

    public function enable()
    {
        if(!is_writable(WP_PLUGIN_DIR)) {
            throw new PluginsFolderNotWritableException();
        }

        try {

            $this->copyFolder(
                $this->wpcodeboxPath . 'templates' . DIRECTORY_SEPARATOR . 'fp' . DIRECTORY_SEPARATOR,
                $this->path
            );


            touch($this->wpcodeboxPath . '.fcpe');
        } catch (\Throwable $e) {
            echo $e->getMessage(); die;
        }

        $mainFileContents = file_get_contents($this->mainFile);
        $mainFileContents = str_replace('{PLUGIN_NAME}', \get_option('wpcb_functionality_plugin_name'), $mainFileContents);
        $mainFileContents = str_replace('{PLUGIN_DESCRIPTION}', \get_option('wpcb_functionality_plugin_description'), $mainFileContents);
        file_put_contents($this->mainFile, $mainFileContents);

        $this->copySnippets();
        $this->handleJS();
        $this->handleCss();


        $activePlugins = get_option('active_plugins');
        $activePlugins = maybe_unserialize($activePlugins);
        $activePlugins[] = 'wpcodebox_functionality_plugin/plugin.php';
        update_option('active_plugins', $activePlugins);
    }


    public function disable()
    {

        deactivate_plugins('wpcodebox_functionality_plugin/plugin.php');
        delete_plugins(['wpcodebox_functionality_plugin/plugin.php']);
        unlink($this->wpcodeboxPath . '.fcpe');
    }

    public function renameFunctonalityPlugin() {

    }

    function updateStatus($snippetId, $status)
    {
        if(!$this->isEnabled()) {
            return false;
        }

		$snippetRepository = new SnippetRepository();
		$snippet = $snippetRepository->getSnippet($snippetId);
        $fileName = $this->slugify($snippet['title']);

        $mainFileContent = file_get_contents($this->mainFile);

        $snippetIncludeDisabled = "    " . $this->getSnippetFileNameDisabled($fileName, $snippetId) . "\n";
        $snippetIncludeEnabled = "    " . $this->getSnippetFileName($fileName, $snippetId) . "\n";

        if($snippet['codeType'] === 'php' && $snippet['runType'] === 'never') {
            $status = false;
        }

        if($status === 1) {
            $mainFileContent = str_replace($snippetIncludeDisabled, $snippetIncludeEnabled, $mainFileContent);
        } else {
            $mainFileContent = str_replace($snippetIncludeEnabled, $snippetIncludeDisabled, $mainFileContent);

        }
        file_put_contents($this->mainFile, $mainFileContent);

		return true;
    }

    function disableSnippet($snippetId)
    {
        if(!$this->isEnabled()) {
            return false;
        }

		$snippetRepository = new SnippetRepository();
		$snippet = $snippetRepository->getSnippet($snippetId);

        $fileName = $this->slugify($snippet['title']);

        $mainFileContent = file_get_contents($this->mainFile);

        $snippetFileName = $this->getSnippetFileName($fileName, $snippetId);
        $snippetFileNameDisable = $this->getSnippetFileNameDisabled($fileName, $snippetId);

        $mainFileContent = str_replace("    " . $snippetFileName . "\n", "    " . $snippetFileNameDisable . "\n", $mainFileContent);

        file_put_contents($this->mainFile, $mainFileContent);

		return true;
    }

    public function slugify($text, string $divider = '_')
    {
        // replace non letter or digits by divider
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, $divider);

        // remove duplicate divider
        $text = preg_replace('~-+~', $divider, $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    public function saveSnippet($snippetId)
    {
        if(!$this->isEnabled()) {
            return false;
        }

		$snippetRepository = new SnippetRepository();
		$snippet = $snippetRepository->getSnippet($snippetId);

        if($snippet['codeType'] === 'txt') {
            return false;
        }

        $name = $snippet['title'];
        $enabled = !!$snippet['enabled'];

        $globalCSS = new GlobalCSS();
        $globalJS = new GlobalJS();

        $snippetFactory = new SnippetFactory($globalCSS, $globalJS, $snippet);
        $internalSnippet = $snippetFactory->createInternalSnippet(true);


        if($internalSnippet instanceof StyleSnippet || $internalSnippet instanceof JsSnippet && $snippet['renderType'] !== 'external') {

            $extension = $snippet['codeType'];
            if($extension === 'scss' || $extension === 'less') {
                $extension = 'css';
            }

            if(file_exists($this->path . 'assets' . DIRECTORY_SEPARATOR . $extension . DIRECTORY_SEPARATOR . $this->slugify($snippet['title']) . '_' . $snippet['id'] . '.' . $extension)) {
                unlink ($this->path . 'assets' . DIRECTORY_SEPARATOR . $extension . DIRECTORY_SEPARATOR . $this->slugify($snippet['title']) . '_' . $snippet['id'] . '.' . $extension);
                unlink ($this->path . 'snippets' . DIRECTORY_SEPARATOR . $this->slugify($snippet['title']).'-' . $snippet['id'] . '.php');

                $mainFileContent = file_get_contents($this->mainFile);

                $mainFileContent = str_replace($this->getSnippetFileName($this->slugify($snippet['title']), $snippet['id']), '', $mainFileContent);
                $mainFileContent = str_replace($this->getSnippetFileNameDisabled($this->slugify($snippet['title']), $snippet['id']), '', $mainFileContent);

                file_put_contents($this->mainFile, $mainFileContent);

            }

        }
        if( ($internalSnippet instanceof StyleSnippet || $internalSnippet instanceof JsSnippet) && $snippet['renderType'] !== 'external' ) {
            return false;
        }

        $renderType = $snippet['renderType'];
        if($renderType === 'external') {

            $extension = $snippet['codeType'];
            if($extension === 'scss' || $extension === 'less') {
                $extension = 'css';
            }

            $dir = wp_upload_dir();
            $wpcbUploadsDir = $dir['basedir'] . DIRECTORY_SEPARATOR . 'wpcodebox';

            $fileName = $wpcbUploadsDir . DIRECTORY_SEPARATOR . $snippet['id'] . '.' . $extension;
            $destinationFile = $this->path . 'assets' . DIRECTORY_SEPARATOR . $extension . DIRECTORY_SEPARATOR . $this->slugify($snippet['title']) . '_' . $snippet['id'] . '.' . $extension;

            copy($fileName, $destinationFile);
        }

        $fileName = $this->slugify($name);
        if(!$this->isEnabled()) {
            return false;
        }
        try {
            $code = $internalSnippet->getCode();
        } catch (\Exception $e) {
            echo $e->getMessage(); die;
        }
        $code = "<?php \n".$code;

        $conditions = json_encode($snippet['conditions']);
        $conditionBuilderCode = "if(!WPCodeBox\ConditionBuilder\ShouldExecute::should_execute('".base64_encode($conditions)."')) { return; }\n\n";


        $code = str_replace("{{WPCB_CONDITION_CODE}}", $conditionBuilderCode , $code);

        file_put_contents($this->path . 'snippets' . DIRECTORY_SEPARATOR . $fileName . "-$snippet[id]" . '.php', $code);

        $mainFileContent = file_get_contents($this->mainFile);

        if($snippet['codeType'] === 'php' && ($snippet['runType'] === 'external' || $snippet['runType'] === 'once')) {
            $this->deleteSnippet($snippet['id']);
            return true;
        }
        // Disable snippets that are set to not run
        if($snippet['codeType'] === 'php' && $snippet['runType'] === 'never') {
            $enabled = false;
        }

        $mainFileContent = str_replace("    " . $this->getSnippetFileNameDisabled($fileName, $snippet['id']) . "\n", '', $mainFileContent);
        $mainFileContent = str_replace("    " . $this->getSnippetFileName($fileName, $snippet['id']) . "\n", '', $mainFileContent);

        if ($enabled) {
            $mainFileContent = str_replace('    // Snippets will go before this line, do not edit', "    " . $this->getSnippetFileName($fileName, $snippet['id']) . "\n    // Snippets will go before this line, do not edit", $mainFileContent);
        } else {
            $mainFileContent = str_replace('    // Snippets will go before this line, do not edit', "    " . $this->getSnippetFileNameDisabled($fileName, $snippet['id']) . "\n    // Snippets will go before this line, do not edit", $mainFileContent);
        }

        file_put_contents($this->mainFile, $mainFileContent);

        return true;
    }

    function deleteSnippet($snippetId)
    {
        if(!$this->isEnabled()) {
            return;
        }

		$snippetRepository = new SnippetRepository();

		$snippet = $snippetRepository->getSnippet($snippetId);
        $fileName = $this->slugify($snippet['title']);

        $mainFileContent = file_get_contents($this->mainFile);

        $mainFileContent = str_replace("    " . $this->getSnippetFileNameDisabled($fileName, $snippetId) . "\n", '', $mainFileContent);
        $mainFileContent = str_replace("    " . $this->getSnippetFileName($fileName, $snippetId) . "\n", '', $mainFileContent);
        file_put_contents($this->mainFile, $mainFileContent);



        @unlink($this->path . 'snippets' . DIRECTORY_SEPARATOR . $fileName.'-'. $snippetId  . '.php' );
    }

    public function copyFolder($from, $to)
    {
        if (!is_dir($from)) {
            return false;
        }

        if (!is_dir($to)) {
            if (!mkdir($to)) {
                return false;
            };
        }

        $dir = opendir($from);
        while (($ff = readdir($dir)) !== false) {
            if ($ff != "." && $ff != "..") {
                if (is_dir("$from$ff")) {
                    $this->copyFolder("$from$ff/", "$to$ff/");
                } else {
                    if (!copy("$from$ff", "$to$ff")) {
                        exit("Error copying $from$ff to $to$ff");
                    }
                }
            }
        }
        closedir($dir);

        return true;
    }

    public function recursiveRemoveDirectory($directory)
    {
        foreach(glob("{$directory}/*") as $file)
        {
            if(is_dir($file)) {
                $this->recursiveRemoveDirectory($file);
            } else {
                unlink($file);
            }
        }
        rmdir($directory);
    }

    private function getSnippetFileName($fileName, $snippetId)
    {
        return "include_once 'snippets" . DIRECTORY_SEPARATOR . "$fileName-$snippetId.php'; //[$snippetId]";
    }

    private function getSnippetFileNameDisabled($fileName, $snippetId)
    {
        return "//include_once 'snippets" . DIRECTORY_SEPARATOR . "$fileName-$snippetId.php'; //[$snippetId]";
    }

    private function copySnippets()
    {
        global $wpdb;

        $snippetRepository = new \Wpcb2\Repository\SnippetRepository();
        $snippets = $snippetRepository->getAllSnippetsQuery();

        foreach($snippets as $snippet) {
            $this->saveSnippet($snippet['id']);

        }
    }

    public function handleCss()
    {
        if(!$this->isEnabled()) {
            return;
        }
        global $wpdb;

        $globalCSS = new GlobalCSS();
        $globalJS = new GlobalJS();

        $snippetRepository = new \Wpcb2\Repository\SnippetRepository();
        $snippets = $snippetRepository->getAllSnippetsQuery();

        foreach($snippets as $snippet) {

            if($snippet['codeType'] === 'txt') {
                continue;
            }

            $snippetFactory = new SnippetFactory($globalCSS, $globalJS, $snippet);
            $internalSnippet = $snippetFactory->createInternalSnippet($snippet, true);
            if($internalSnippet->isEnabled() && $snippet['renderType'] !== 'external') {
                $internalSnippet->getCode();
            }

        }

        $cssCode = $globalCSS->getCodeForFP();

        file_put_contents($this->path . 'snippets' . DIRECTORY_SEPARATOR . "inline_styles" . '.php', $cssCode);

    }

    public function handleJS()
    {
        if(!$this->isEnabled()) {
            return;
        }
        global $wpdb;

        $globalCSS = new GlobalCSS();
        $globalJS = new GlobalJS();

        $snippetRepository = new \Wpcb2\Repository\SnippetRepository();
        $snippets = $snippetRepository->getAllSnippetsQuery();

        foreach($snippets as $snippet) {

            if($snippet['codeType'] === 'txt') {
                continue;
            }

            $snippetFactory = new SnippetFactory($globalCSS, $globalJS, $snippet);
            $internalSnippet = $snippetFactory->createInternalSnippet(true);

            if( $internalSnippet->isEnabled() && $snippet['renderType'] !== 'external' ) {
                $internalSnippet->getCode();
            }

        }

        $jsCode = $globalJS->getCodeForFP();

        file_put_contents($this->path . 'snippets' . DIRECTORY_SEPARATOR . "inline_scripts" . '.php', $jsCode);

    }

    public function getSnippetPath($snippet)
    {
        if(!$this->isEnabled()) {
            return '';
        }

        $fileName = $this->slugify($snippet->post_title);
        $snippetId = $snippet->ID;

        return $this->path . 'snippets' . DIRECTORY_SEPARATOR . "$fileName-$snippetId.php";

    }


    public function renameSnippet($snippetId, $oldTitle, $newTitle) {

        if(!$this->isEnabled()) {
            return;
        }
        $mainFileContent = file_get_contents($this->mainFile);

        $newFileName = $this->slugify($newTitle);
        $oldFileName = $this->slugify($oldTitle);

        @rename($this->path . 'snippets' . DIRECTORY_SEPARATOR . $oldFileName . '-' . $snippetId . '.php',
               $this->path . 'snippets' . DIRECTORY_SEPARATOR . $newFileName . '-' . $snippetId . '.php');


		$snippetRepository = new SnippetRepository();
		$snippet = $snippetRepository->getSnippet($snippetId);

        $extension = $snippet['codeType'];
        if($extension === 'scss' || $extension === 'less') {
            $extension = 'css';
        }

        $oldAssetFileName = $this->path . 'assets' . DIRECTORY_SEPARATOR . $extension . DIRECTORY_SEPARATOR . $this->slugify($oldTitle) . '_' . $snippetId . '.' . $extension;
        if(file_exists($oldAssetFileName)) {
            @unlink($oldAssetFileName);
        }

        $mainFileContent = str_replace($oldFileName . '-' . $snippetId . '.php', $newFileName . '-' . $snippetId . '.php', $mainFileContent);

        file_put_contents($this->mainFile, $mainFileContent);
    }
}
