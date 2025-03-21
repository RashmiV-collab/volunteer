<?php

namespace Wpcb2\Snippet;


use Wpcb2\Repository\SnippetRepository;

abstract class Snippet
{

	protected $code;

	/** @var  GlobalCSS */
	protected $globalCSS;

	/** @var  GlobalJS */
	protected $globalJS;

	protected $snippetData;

	protected $isFp = false;

	public function __construct(GlobalCSS $globalCSS, GlobalJS $globalJS, $snippet, $isFp = false)
	{

		$this->code = $snippet['code'];

		$this->globalCSS = $globalCSS;
		$this->globalJS = $globalJS;
		$this->isFp = $isFp;
		$this->snippetData = $snippet;
	}

	/**
	 * @return array
	 */
	protected function getHook()
	{

		$hooks = $this->snippetData['hook'];

		if(!$hooks) {
			return false;
		}

		$hooks = json_decode($hooks, true);

		if (is_array($hooks) && isset($hooks[0]['hook'])) {
			$response = [];
			foreach ($hooks as $hook) {
				$response[] = $this->processHook($hook);
			}

			return $response;
		} else {
			return [$this->processHook($hooks)];
		}

	}

	private function processHook($hookData)
	{
		if (isset($hookData['hook']) && is_array($hookData['hook'])) {
			$hook = $hookData['hook']['value'];
		} else if (isset($hookData['hook'])) {
			$hook = $hookData['hook'];
		}

		if (isset($hookData['value'])) {
			$hook = $hookData['value'];
		}


		if ($hook === 'custom_custom_action') {
			$hook = $hookData['customAction'];
		}

		$hook = $this->mapCustomHookToWPHooks($hook);

		$priority = 10;



		return array(
			'hook' => $this->mapCustomHookToWPHooks($hook),
			'priority' => $priority,
			'shortcode' => isset($hookData['shortcode']) ? $hookData['shortcode'] : '',
			'customAction' => isset($hookData['customAction']) ? $hookData['customAction'] : '');

	}

	public function disableSnippetAndLogError(\Throwable $e)
	{

        $snippetRepository = new SnippetRepository();

        $snippetRepository->updateSnippet($this->snippetData['id'], [
            'enabled' => false,
            'error' => 1,
            'errorMessage' => $e->getMessage(),
            'errorTrace' => $e->getTraceAsString(),
            'errorLine' => $e->getLine()-4
        ]);

		do_action('wpcb_snippet_disabled', $this->snippetData['id']);

	}

	public function getConditionCode()
	{
		if ($this->isFp) {
			$conditionCode = "{{WPCB_CONDITION_CODE}}";
		} else {
			$conditionCode = "if(!\Wpcb2\ConditionBuilder\ShouldExecute::should_execute(" . $this->snippetData['id'] . ")) { return false; }";
		}

		return $conditionCode;
	}

	abstract function getCode();

	/**
	 * @param $hook
	 * @return string
	 */
	protected function mapCustomHookToWPHooks($hook)
	{
		if ($hook == 'custom_frontend_header') {
			$hook = 'wp_head';
		}

		if ($hook == 'custom_login_header') {
			$hook = 'login_head';
		}

		if ($hook === 'custom_admin_header') {
			$hook = 'admin_head';
		}

		if ($hook === 'custom_frontend_footer') {
			$hook = 'wp_footer';
		}

		if ($hook == 'custom_login_footer') {
			$hook = 'login_footer';
		}

		if ($hook == 'custom_admin_footer') {
			$hook = 'admin_footer';
		}

		if ($hook == 'custom_plugins_loaded') {
			$hook = 'plugins_loaded';
		}

		if ($hook == 'root') {
			$hook = 'custom_root';
		}
		return $hook;
	}

	public function isEnabled()
	{
		return $this->snippetData['enabled'];
	}

	/**
	 * @param $codeType
	 * @param $hookPriority
	 * @param $location_value
	 * @param $where_to_run
	 * @return array
	 */


}
