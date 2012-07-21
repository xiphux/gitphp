<?php
/**
 * Controller for returning raw graph data
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_GraphData extends GitPHP_ControllerBase
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->preserveWhitespace = true;
		$this->DisableLogging();
	}

	/**
	 * Gets the template for this controller
	 *
	 * @return string template filename
	 */
	protected function GetTemplate()
	{
		return 'graphdata.tpl';
	}

	/**
	 * Gets the cache key for this controller
	 *
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return isset($this->params['graphtype']) ? $this->params['graphtype'] : '';
	}

	/**
	 * Gets the name of this controller's action
	 *
	 * @param boolean $local true if caller wants the localized action name
	 * @return string action name
	 */
	public function GetName($local = false)
	{
		return 'graphdata';
	}

	/**
	 * Read query into parameters
	 */
	protected function ReadQuery()
	{
		if (isset($_GET['g']))
			$this->params['graphtype'] = $_GET['g'];
	}

	/**
	 * Loads headers for this template
	 */
	protected function LoadHeaders()
	{
		$this->headers[] = 'Content-Type: application/json';
	}

	/**
	 * Loads data for this template
	 */
	protected function LoadData()
	{
		$head = $this->GetProject()->GetHeadCommit();

		$data = null;

		if ($this->params['graphtype'] == 'commitactivity') {

			$data = array();

			$log = new GitPHP_Log($this->GetProject(), $head, new GitPHP_LogLoad_Git($this->exe), 0, 0);
			$cache = $this->GetProject()->GetObjectManager()->GetMemoryCache();

			foreach ($log as $commit) {
				$data[] = array('CommitEpoch' => (int)$commit->GetCommitterEpoch());
				$cache->Delete($commit->GetCacheKey());
			}

		} else if ($this->params['graphtype'] == 'languagedist') {

			$data = array();

			include_once(GITPHP_GESHIDIR . "geshi.php");
			$geshi = new GeSHi("",'php');

			$files = explode("\n", $this->exe->Execute($this->GetProject()->GetPath(), 'ls-tree', array('-r', '--name-only', $head->GetTree()->GetHash())));
			foreach ($files as $file) {
				$filename = GitPHP_Util::BaseName($file);
				$lang = GitPHP_Util::GeshiFilenameToLanguage($filename);
				if (empty($lang)) {
					$lang = $geshi->get_language_name_from_extension(substr(strrchr($filename, '.'), 1));
					if (empty($lang)) {
						$lang = 'Other';
					}
				}

				if (!empty($lang) && ($lang !== 'Other')) {
					$fulllang = $geshi->get_language_fullname($lang);
					if (!empty($fulllang))
						$lang = $fulllang;
				}

				if (isset($data[$lang])) {
					$data[$lang]++;
				} else {
					$data[$lang] = 1;
				}
			}

		}

		$this->tpl->assign('data', json_encode($data));
	}

}