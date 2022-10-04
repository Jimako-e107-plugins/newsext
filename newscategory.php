<?php

/**
 * e107 website system
 *
 * Copyright (C) 2008-2016 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 */


//print_a($_GET);
//print_a(e_QUERY);
// news rewrite for v2.x


if (!defined('e107_INIT'))
{
	require_once(__DIR__ . '/../../class2.php');
}

class news_front
{

	private $action = null;
	private $subAction = null;
	private $route = null;
	private $defaultTemplate = '';
	private $cacheString = 'news.php_default_';
	private $from = 0;
	private $order = 'news_datestamp';
	private $nobody_regexp = '';
	private $ix = null;
	private $newsUrlparms = array();
	private $text = null;
	private $pref;
	private $debugInfo = array();
	private $cacheRefreshTime;
	private $caption = null;
	private $title = '';
	private $templateKey = null;

	private $layout = array();
	private $currentRow = array();
	private $categoryRow = array();

	private $pagination;
	//	private $interval = 1;

	function __construct()
	{

		e107::includeLan(e_LANGUAGEDIR . e_LANGUAGE . '/lan_' . e_PAGE);
		e107::includeLan(e_LANGUAGEDIR . e_LANGUAGE . '/lan_news.php');		// Temporary

		$this->pref = e107::getPref();

		$this->cacheRefreshTime = vartrue($this->pref['news_cache_timeout'], false);

		$this->pagination = varset($this->pref['news_pagination'], 'record');
		// $this->interval = $this->pref['newsposts']-$this>pref['newsposts_archive'];

		require_once(e_HANDLER . "news_class.php");

		$this->nobody_regexp = "'(^|,)(" . str_replace(",", "|", e_UC_NOBODY) . ")(,|$)'";
		$this->ix = new news;

		$this->setConstants();
		$this->setActions();
		$this->setRoute();
		$this->setPagination();
		$this->setTemplateKey();
		$this->detect();
		$this->setCaption();
		$this->setTitle();
		$this->setBreadcrumb();


		return null;
	}

	private function setConstants()
	{

		if (!defined('ITEMVIEW'))
		{
			define('ITEMVIEW', varset($this->pref['newsposts'], 15));
		}

		// ?all and ?cat.x and ?tag are the same listing functions - just filtered differently.
		// NEWSLIST_LIMIT is suitable for all

		if (!defined("NEWSLIST_LIMIT"))
		{
			define("NEWSLIST_LIMIT", varset($this->pref['news_list_limit'], 15));
		}
	}

	private function setActions()
	{

		$action = $_GET['action'];
		$sub_action = 	$_GET['id'];
		$this->action = e107::getParser()->filter($action);
		$this->subAction = e107::getParser()->filter($sub_action, "int");

		if ($this->subAction > 0)
		{
			$this->categoryRow = e107::getDb()->retrieve("news_category", '*', " category_id = " . $this->subAction);
		}
	}


	private function setTemplateKey()
	{
		//$this->templateKey

		//NEWS_LAYOUT constant is not supported

		$this->layout = e107::getTemplate('news', 'news');

		$this->defaultTemplate = e107::getPref('news_default_template');

		if ($this->action == "list")
		{
			$catTemplate = $this->categoryRow['category_template'];

			if (isset($this->layout[$catTemplate]))
			{
				$this->templateKey = $catTemplate;
			}
			elseif (isset($this->layout['category']))
			{
				$this->templateKey = "category";
			}
			else
			{
				$this->templateKey = $this->defaultTemplate;
			}
		}
	}

	private function setTitle()
	{

		$this->title = LAN_CATEGORIES;

		if ($this->action == "list") // default page.  == index 
		{
			$this->title =  $this->categoryRow['category_name'];
		}
	}

	private function setCaption()
	{

		$tmpl = $this->layout[$this->templateKey];
		$param = array();

		$nsc = e107::getScBatch('news')->setScVar('news_item', $this->categoryRow)->setScVar('param', $param);

		$this->caption = $this->title;

		if ($tmpl['caption'])
		{
			$this->caption = e107::getParser()->parseTemplate($tmpl['caption'], true, $nsc);
		}
	}

	private function setBreadcrumb()
	{
		$this->addDebug('setBreadcrumb', 'complete');

		$breadcrumb = array();

		$breadcrumb[] = array('text' => LAN_PLUGIN_NEWS_NAME, 'url' => e107::url('news', 'index'));

		if (empty($this->categoryRow['category_name']))
		{
			$this->addDebug("Possible Issue", "missing category_name on this->categoryRow");
		}

		$categoryName = e107::getParser()->toHTML($this->categoryRow['category_name'], true, 'TITLE');

		switch ($this->route)
		{

			case 'newsext/index':
				$breadcrumb[] = array('text' => LAN_CATEGORIES, 'url' => null);
				break;

			case 'newsext/category':
				$breadcrumb[] = array('text' => LAN_CATEGORIES, 'url' => e107::url("newsext", "index"));

				$breadcrumb[] = array('text' => $categoryName, 'url' => null);
				break;

			default:
				if (ADMIN)
				{
					$breadcrumb[] = array('text' => "Missing News breadcrumb for route: " . $this->route);
				}
				break;
		}

		e107::breadcrumb($breadcrumb);
	}


	private function detect()
	{

		$this->text = $this->renderCategoryTemplate();


		return;
	}



	private function getRenderId()
	{
		$tmp = explode('/', $this->route);

		if (!empty($this->templateKey))
		{
			$tmp[] = $this->templateKey;
		}

		$unique = implode('-', $tmp);

		return $unique;
	}


	/**
	 * When the template contains a 'caption' - tablerender() is used, otherwise a simple echo is used.
	 * @return bool
	 */
	public function render($return = false)
	{

		$unique = $this->getRenderId();

		$this->addDebug("tablerender ID", $unique);

		e107::getRender()->setUniqueId($unique)->tablerender($this->caption, $this->text, 'news');
	}




	private function setRoute()
	{
		$this->newsUrlparms = array('page' => '--FROM--');
		if ($this->subAction)
		{

			switch ($this->action)
			{
				case 'list':
					$this->newsUrlparms['id'] = $this->subAction;
					$this->newsUrlparms['category_id'] = $this->subAction;
					$newsRoute = 'category';
					break;

				case 'cat':
					$this->newsUrlparms['category_id'] = $this->subAction;
					$newsRoute = 'category';
					break;

				default:
					$newsRoute = 'index';
					break;
			}
		}
		else
		{
			$newsRoute = 'index';
		}


		$this->route = 'newsext/' . $newsRoute;

		$tp = e107::getParser();
	}

	private function setPagination()
	{

		$this->from = (int) ($_GET['page']);
		 
		// New in v2.3.1 Pagination with "Page" instead of "Record".
		if (!empty($this->pref['news_pagination']) && $this->pref['news_pagination'] === 'page' && !empty($_GET['page']))
		{
			switch ($this->action)
			{
				case 'list':
				case 'index':
					$this->from = (int) ($_GET['page'] - 1)  * NEWSLIST_LIMIT;
					break;

				default:
					$this->from = (int) ($_GET['page'] - 1)  * ITEMVIEW;
			}
		}
		else {
			$this->from = (int) ($_GET['page']);
		}
 
		$this->addDebug('NEWSLIST_LIMIT', NEWSLIST_LIMIT);
		$this->addDebug('FROM', $this->from);
	}




	public function debug()
	{
		$title = e107::getSingleton('eResponse')->getMetaTitle();

		echo "<div class='alert alert-info'>";
		echo "<h4>News Debug Info</h4>";
		echo "<table class='table table-striped table-bordered'>";
		echo "<tr><td><b>action:</b></td><td>" . $this->action . "</td></tr>";
		echo "<tr><td><b>subaction:</b></td><td>" . $this->subAction . "</td></tr>";
		echo "<tr><td><b>route:</b></td><td>" . $this->route . "</td></tr>";
		echo "<tr><td><b>e_QUERY:</b></td><td>" . e_QUERY . "</td></tr>";
		echo "<tr><td><b>e_PAGETITLE:</b></td><td>" . vartrue($title, '(unassigned)') . "</td></tr>";

		echo "<tr><td><b>CacheTimeout:</b></td><td>" . $this->cacheRefreshTime . "</td></tr>";
		echo "<tr><td><b>_GET:</b></td><td>" . print_r($_GET, true) . "</td></tr>";

		foreach ($this->debugInfo as $key => $val)
		{
			echo "<tr><td><b>" . $key . ":</b></td><td>" . $val . "</tr>";
		}

		echo "</table></div>";
	}


	private function addDebug($key, $message)
	{
		if (is_array($message))
		{
			$this->debugInfo[$key] = print_a($message, true);
		}
		else
		{
			$this->debugInfo[$key] = $message;
		}
	}

	// ----------- old functions ------------------------

	/**
	 * @param array $news news and category table row. ie. news_id, news_title, news_sef ... category_id etc.
	 * @param string $type
	 */
	private function setCategoryFrontMeta()
	{
		$tp = e107::getParser();
		$this->addDebug('setCategoryFrontMeta (action)', $this->action);
		$this->addDebug('setCategoryFrontMeta (subAction)', $this->subAction);

		$type = $this->action;

		switch ($type)
		{
			case "list":
				$title = $tp->toHTML($this->categoryRow['category_name'], false, 'TITLE_PLAIN');
				e107::title($title);
				e107::meta('robots', 'index, follow');
				e107::route('newsext/category');
				e107::canonical($this->route, $this->categoryRow);

				break;

			case "index";

				break;
		}
	}


	private function setNewsCache($cache_tag, $cache_data, $rowData = array())
	{
		$e107cache = e107::getCache();
		$e107cache->setMD5(null);

		$e107cache->set($cache_tag, $cache_data);
		$e107cache->set($cache_tag . "_caption", $this->caption);

		$this->addDebug('Cache Caption', $this->caption);
		$e107cache->set($cache_tag . "_title", e107::getSingleton('eResponse')->getMetaTitle());
		$e107cache->set($cache_tag . "_diz", defined("META_DESCRIPTION") ? META_DESCRIPTION : '');

		$e107cache->set($cache_tag . "_rows", e107::serialize($rowData, 'json'));
	}


	/**
	 * @param        $cachetag
	 * @param string $type 'title' or 'diz' or 'rows' or empty for html.
	 * @return array|false|string
	 */
	private function getNewsCache($cachetag, $type = null)
	{
		if (!empty($type))
		{
			$cachetag .= "_" . $type;
		}
		$this->addDebug('CacheString lookup', $cachetag);
		e107::getDebug()->log('Retrieving cache string:' . $cachetag);

		$ret =  e107::getCache()->setMD5(null)->retrieve($cachetag);

		if (empty($ret))
		{
			$this->addDebug('Possible Issue', $cachetag . " is empty");
		}

		if ($type == 'rows')
		{
			return e107::unserialize($ret);
		}

		return $ret;
	}

	/**
	 * @param $cacheString
	 * @return bool|string
	 */
	private function checkCache($cacheString)
	{
		$e107cache = e107::getCache();
		$this->addDebug("checkCache", 'true');
		$e107cache->setMD5(null);

		$cache_data = $e107cache->retrieve($cacheString, $this->cacheRefreshTime);
		$cache_title = $e107cache->retrieve($cacheString . "_title", $this->cacheRefreshTime);
		$cache_diz = $e107cache->retrieve($cacheString . "_diz", $this->cacheRefreshTime);
		$etitle = ($cache_title != "e_PAGETITLE") ? $cache_title : "";
		$ediz = ($cache_diz != "META_DESCRIPTION") ? $cache_diz : "";

		if ($etitle)
		{
			e107::title($etitle);
		}

		if ($ediz)
		{
			define("META_DESCRIPTION", $ediz);
		}

		if ($cache_data)
		{
			return $cache_data;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @param $caption
	 * @param $text
	 * @return null
	 */
	private function renderCache($caption, $text)
	{
		global $pref, $tp, $sql, $CUSTOMFOOTER, $FOOTER, $cust_footer, $ph;
		global $db_debug, $ns, $eTimingStart, $error_handler, $db_time, $sql2, $mySQLserver, $mySQLuser, $mySQLpassword, $mySQLdefaultdb, $e107;

		$this->text = $text;
		$this->caption = $caption;
		$this->addDebug("Cache", 'active');

		return $this->text;
	}



	private function getQuery()
	{

		if ($this->action == "list")
		{


			$query = "SELECT ne.news_id FROM #news_extended AS ne WHERE FIND_IN_SET({$this->subAction} , ne.news_categories)  ";


			$data = e107::getDb()->retrieve($query, true);

			foreach ($data as $key => $item)
			{
				$item_array[] = $item['news_id'];
			}
			if (is_array($item_array))
			{
				$values = implode(",", $item_array);
			}

			$query = "
				  
				SELECT SQL_CALC_FOUND_ROWS n.*, u.user_id, u.user_name, u.user_customtitle, u.user_image, nc.category_id, nc.category_name, nc.category_sef,
				nc.category_icon, nc.category_meta_keywords, nc.category_meta_description, nc.category_template  
				FROM #news AS n
				LEFT JOIN #user AS u ON n.news_author = u.user_id
				LEFT JOIN #news_category AS nc ON nc.category_id = n.news_category
				WHERE n.news_id IN ({$values})
				AND n.news_class REGEXP '" . e_CLASS_REGEXP . "' AND NOT (n.news_class REGEXP " . $this->nobody_regexp . ")
				AND n.news_start < " . time() . " AND (n.news_end=0 || n.news_end>" . time() . ")
				ORDER BY n.news_sticky DESC," . $this->order . " DESC LIMIT " . $this->from . "," . ITEMVIEW;
		}
		if ($this->action == "index")
		{
			$query = "SELECT SQL_CALC_FOUND_ROWS n.*, u.user_id, u.user_name, u.user_customtitle, u.user_image, nc.category_id, nc.category_name, nc.category_sef,
				nc.category_icon, nc.category_meta_keywords, nc.category_meta_description, nc.category_template  
				FROM #news AS n
				LEFT JOIN #user AS u ON n.news_author = u.user_id
				LEFT JOIN #news_category AS nc ON nc.category_id = n.news_category
				AND n.news_class REGEXP '" . e_CLASS_REGEXP . "' AND NOT (n.news_class REGEXP " . $this->nobody_regexp . ")
				AND n.news_start < " . time() . " AND (n.news_end=0 || n.news_end>" . time() . ")
				ORDER BY n.news_sticky DESC," . $this->order . " DESC LIMIT " . $this->from . "," . ITEMVIEW;
		}


		return $query;
	}



	private function renderCategoryTemplate()
	{
		$this->addDebug("Method", 'renderCategoryTemplate()');
		$tp = e107::getParser();
		$sql = e107::getDb();

		$this->cacheString = 'news.php_default_';

		$interval = $this->pref['newsposts'];

		$query = $this->getQuery();

		switch ($this->action)
		{
			case "list":

				$noNewsMessage = LAN_NEWS_463;

				break;

			case 'index':
			default:

				$interval = $this->pref['newsposts'] - $this->pref['newsposts_archive'];		// Number of 'full' posts to show




				//	}

				$noNewsMessage = LAN_NEWS_83;
		}	// END - switch($action)


		if ($newsCachedPage = $this->checkCache($this->cacheString)) // normal news front-page - with cache.
		{


			//if(!$this->action)
			//	{
			// Removed, themes should use {FEATUREBOX} shortcode instead
			//		if (isset($this->pref['fb_active']))
			//		{
			//			require_once(e_PLUGIN."featurebox/featurebox.php");
			//		}
			// Removed, legacy
			// if (isset($this->pref['nfp_display']) && $this->pref['nfp_display'] == 1)
			// {
			// require_once(e_PLUGIN."newforumposts_main/newforumposts_main.php");
			// }

			//	}

			//news archive
			if ($this->action != "item" && $this->action != 'list' && $this->pref['newsposts_archive'])
			{
				$sql = e107::getDb();

				if ($sql->gen($query))
				{

					$newsAr = $sql->db_getList();

					if ($newsarchive = $this->checkCache('newsarchive'))
					{
						$newsCachedPage = $newsCachedPage . $newsarchive;
					}
				}
			}

			$this->renderCache($this->caption, $newsCachedPage);
			return null;
		}

		if (!($news_total = $sql->gen($query)))  // No news items
		{
			$this->setCategoryFrontMeta();
			return "<div class='news-empty'><div class='alert alert-info' style='text-align:center'>" . $noNewsMessage . "</div></div>";
		}

		$newsAr = $sql->db_getList();

		$news_total = $sql->total_results;


		$p_title = ($this->action == "item") ? $newsAr[1]['news_title'] : $tp->toHTML($newsAr[1]['category_name'], FALSE, 'TITLE');

		switch ($this->action)
		{


			case 'list':
			default:
				$this->setCategoryFrontMeta();
				break;
		}


		$currentNewsAction = $this->action;

		$action = $currentNewsAction;

		ob_start();

		$newpostday = 0;
		$thispostday = 0;
		$this->pref['newsHeaderDate'] = 1;
		$gen = new convert();

		// #### normal newsitems, rendered via render_newsitem(), the $query is changed above (no other changes made) ---------
		$param = array();
		$param['current_action'] = $action;
		$param['template_key'] = 'news/default';

		// Get Correct Template
		// XXX we use $NEWSLISTSTYLE above - correct as we are currently in list mode - XXX No this is not NEWSLISTSTYLE - which provides only summaries.
		// TODO requires BC testing if we comment this one



		$catTemplate = $this->CategoryRow['category_template'];

		if (!empty($newsAr[1]['category_template']) && !empty($this->layout[$catTemplate])) // defined by news_category field.
		{
			$this->addDebug("Template Mode", 'news_category database field');

			$tmpl = $this->layout[$this->templateKey];
			$param['template_key'] = 'news/' . $this->templateKey;
		}
		elseif ($this->action === 'list' && isset($this->layout['category']) && !isset($this->layout['category']['body'])) // make sure it's not old news_categories.sc
		{
			$this->addDebug("Template Mode", "'category' key defined in template file");
			$tmpl = $this->layout['category'];

			$param['template_key'] = 'news/category';
		}
		elseif (!empty($layout[$this->defaultTemplate])) // defined by default template 'news' pref.  (newspost.php?mode=main&action=settings)
		{
			$this->addDebug("Template Mode", 'News Preferences: Default template');
			$tmpl = $this->layout[$this->defaultTemplate];
		}
		else // fallback.
		{
			$this->addDebug("Template Mode", 'Fallback');
			$tmpl = $this->layout['default'];

			$this->templateKey = 'default';
		}

		$tmpl = $this->layout[$this->templateKey];

		$this->addDebug('Template key', $this->templateKey);

		$template = $tmpl['item'];


		if (isset($tmpl['caption']))
		{

			if ($this->action != "list") // default page.  == index 
			{
				$row['category_name'] = $this->currentRow['category_name'];
			}

			//	$nsc = e107::getScBatch('news')->setScVar('news_item', $row)->setScVar('param', $param);
			//	$this->caption = $tp->parseTemplate($tmpl['caption'], true, $nsc);
		}

		if (!empty($tmpl['start'])) //v2.1.5
		{
			$nsc = e107::getScBatch('news')->setScVar('news_item', $newsAr[1])->setScVar('param', $param);
			echo $tp->parseTemplate($tmpl['start'], true, $nsc);
		}
		elseif ($this->subAction && 'list' == $action && vartrue($this->categoryRow['category_name'])) //old v1.x stuff
		{
			// we know category name - pass it to the nexprev url
			$category_name = $this->categoryRow['category_name'];

			if (vartrue($newsAr[1]['category_sef'])) $newsUrlparms['name'] = $newsAr[1]['category_sef'];
			if (!isset($NEWSLISTCATTITLE))
			{
				$NEWSLISTCATTITLE = "<h1 class='newscatlist-title'>" . $tp->toHTML($category_name, FALSE, 'TITLE') . "</h1>";
			}
			else
			{
				$NEWSLISTCATTITLE = str_replace("{NEWSCATEGORY}", $tp->toHTML($category_name, FALSE, 'TITLE'), $NEWSLISTCATTITLE);
			}
			echo $NEWSLISTCATTITLE;
		}


		$i = 1;

		$socialInstalled = e107::isInstalled('social');

		while (isset($newsAr[$i]) && $i <= $interval)
		{
			$news = $newsAr[$i];

			if (!isset($this->newsUrlparms['category_sef']) && !empty($news['category_sef']))
			{
				$this->newsUrlparms['category_sef'] = $news['category_sef'];
			}

			// Set the Values for the social shortcode usage.
			if ($socialInstalled == true)
			{
				$socialArray = array('url' => e107::getUrl()->create('news/view/item', $news, 'full=1'), 'title' => $tp->toText($news['news_title']), 'tags' => $news['news_meta_keywords']);
				$socialObj = e107::getScBatch('social');

				if (is_object($socialObj))
				{
					$socialObj->setVars($socialArray);
				}
			}

			//        render new date header if pref selected ...
			$thispostday = eShims::strftime("%j", $news['news_datestamp']);
			if ($newpostday != $thispostday && (isset($this->pref['news_newdateheader']) && $this->pref['news_newdateheader']))
			{
				echo "<div class='" . DATEHEADERCLASS . "'>" . eShims::strftime("%A %d %B %Y", $news['news_datestamp']) . "</div>";
			}
			$newpostday = $thispostday;
			$news['category_id'] = $news['news_category'];
			if ($action == "item")
			{
				unset($news['news_render_type']);
				e107::getEvent()->trigger('user_news_item_viewed', $news);
				//e107::getDebug()->log($news);
			}
			// $template = false;



			$this->ix->render_newsitem($news, 'default', '', $template, $param);


			$i++;
		}

		if ($this->action == "index")
		{
		}

		if (true)
		{

			$parms = $this->getPaginationParms($news_total, ITEMVIEW);  /*ok */
			$paginationSC = false;

			if (!empty($tmpl['end']))
			{
				e107::setRegistry('core/news/pagination', $parms);
				$nsc = e107::getScBatch('news')->setScVar('news_item', $newsAr[1])->setScVar('param', $param);
				echo $tp->parseTemplate($tmpl['end'], true, $nsc);
				if (strpos($tmpl['end'], '{NEWS_PAGINATION') !== false) // BC fix.
				{
					$paginationSC = true;
					$this->addDebug("Pagination Shortcode", 'true');
				}
			}

			if ($paginationSC === false) // BC Fix.
			{
				echo $tp->parseTemplate("{NEXTPREV={$parms}}");
				$this->addDebug("Pagination Shortcode", 'false');
			}
		}
		else
		{
			echo $tp->parseTemplate($tmpl['end'], true, $nsc);
		}


		$cache_data = ob_get_clean();

		$this->setNewsCache($this->cacheString, $cache_data);

		return $cache_data;
	}

	/**
	 * @param int $total
	 * @param int $amount
	 * @return string
	 */
	private function getPaginationParms($total, $amount)
	{


		$opts = [
			'tmpl_prefix' => deftrue('NEWS_NEXTPREV_TMPL', 'default'),
			'total'       => (int) $total,
			'amount'      => (int) $amount,
			'current'     => $this->from,
			'url'         => e107::url($this->route, $this->newsUrlparms)
		];

		if ($this->pagination === 'page')
		{
			$opts['type'] = 'page';
			$opts['total'] = ceil($opts['total'] / $opts['amount']);
			$opts['current'] = ($opts['current'] / $opts['amount']) + 1;
		}

		$opts['url'] = $opts['url'] . "?page=--FROM--";

		$this->addDebug('newsUrlParms', $this->newsUrlparms);
		$this->addDebug('paginationParms', $opts);

		//	$parms  	= 'tmpl_prefix='.deftrue('NEWS_NEXTPREV_TMPL', 'default').'&total='.$news_total.'&amount='.$amount.'&current='.$this->from.$nitems.'&url='.$url;

		$parms = http_build_query($opts);

		return $parms;
	}
}

$newsObj = new news_front;
//$content = e107::getRender()->getContent(); // get tablerender content
require_once(HEADERF);
//e107::getRender()->setContent($content,null); // reassign tablerender content if HEADERF uses render.
$newsObj->render();
if (E107_DBG_BASIC && ADMIN)
{
	$newsObj->debug();
}
require_once(FOOTERF);
