<?php

namespace WPCodeBox;

class Functionality
{
    public function __construct()
    {
        register_shutdown_function([$this, 'shutdown']);
    }

    public function handleError(\Throwable $e) {

        $file = explode('snippets' . DIRECTORY_SEPARATOR, $e->getFile());
        $file = end($file);

        $fileContents = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'plugin.php');

        if(strpos($fileContents,"//include_once 'snippets/$file';" ) !== false) {
            return;
        }

        $matches = [];
        $response = preg_match_all("/include_once \'snippets\/" . str_replace(".php", "", $file) . "\.php\';\ \/\/\[([0-9]*)\]/", $fileContents, $matches);

        $snippetId = $matches[1][0];

        update_post_meta($snippetId, 'wpcb_enabled', false);
        update_post_meta($snippetId, 'wpcb_error', true);
        update_post_meta($snippetId, 'wpcb_error_message', $e->getMessage());
        update_post_meta($snippetId, 'wpcb_error_trace', $e->getTraceAsString());
        update_post_meta($snippetId, 'wpcb_error_line', $e->getLine()-2);

        $fileContents = str_replace("include_once 'snippets/$file'; //[$snippetId]", "//include_once 'snippets/$file'; //[$snippetId]", $fileContents);
        file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'plugin.php', $fileContents);

        do_action('wpcb_snippet_disabled', \FPCurrentSnippet::$currentSnippet);
    }


    public function shutdown()
    {

        $lasterror = error_get_last();

        if(is_array($lasterror)) {
            if (in_array($lasterror['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_CORE_WARNING, E_COMPILE_WARNING, E_PARSE])) {

                $matches = [];
                if (preg_match('/snippets\/(.*)-([0-9]*)\.php/', $lasterror['file'], $matches)) {

                    $file = explode('snippets' . DIRECTORY_SEPARATOR, $lasterror['file']);
                    $file = end($file);
                    $fileContents = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'plugin.php');


                    $snippetId = $matches[2];

                    update_post_meta($snippetId, 'wpcb_enabled', false);
                    update_post_meta($snippetId, 'wpcb_error', true);
                    update_post_meta($snippetId, 'wpcb_error_message', $lasterror['message']);
                    update_post_meta($snippetId, 'wpcb_error_trace', '');
                    update_post_meta($snippetId, 'wpcb_error_line', $lasterror['line'] - 2);

                    $fileContents = str_replace("//include_once 'snippets/$file'; //[$snippetId]", "include_once 'snippets/$file'; //[$snippetId]", $fileContents);

                    $fileContents = str_replace("include_once 'snippets/$file'; //[$snippetId]", "//include_once 'snippets/$file'; //[$snippetId]", $fileContents);
                    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'plugin.php', $fileContents);

                    do_action('wpcb_snippet_disabled', $snippetId);
                }

            }
        }
    }

}