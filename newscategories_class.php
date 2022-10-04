<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2013 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * News handler
 *
*/

/**
 *
 * @package     e107
 * @subpackage	e107_handlers
 * @author      e107inc
 *
 * Classes:
 * news - old news class
 * e_news_item - news data model - the future
 * e_news_tree - news items collection
 * newsext_category_item - news category data model
 * e_news_category_tree - news category items collection
 */

if (!defined('e107_INIT')) { exit; }
 

require_once(e_HANDLER.'news_class.php');



 
class newsext_category_item extends e_front_model
{
	protected $_db_table = 'news_category';
	protected $_field_id = 'category_id';

	/**
	 * Shorthand getter for news category fields
	 *
	 * @param string $category_field name without the leading 'category_' prefix
	 * @param mixed $default
	 * @return mixed data
	 */
	public function cat($category_field, $default = null)
	{
		return parent::get('category_' . $category_field, $default);
	}

	/**
	 * @param $parm
	 * @return array|string
	 */
	public function sc_news_category_title($parm = '')
	{
		if ('attribute' == $parm)
		{
			return e107::getParser()->toAttribute($this->cat('name'));
		}

		return e107::getParser()->toHTML($this->cat('name'), true, 'TITLE_PLAIN');
	}

	/**
	 * @param $parm
	 * @return string
	 */
	public function sc_news_category_url($parm = '')
	{

		$url = e107::url('newsext/category', array(
				'id' => $this->getId(), 
				'category_id' => $this->getId(), 
				'name' => $this->cat('sef'), 
				'category_sef' => $this->cat('sef')));
				
		switch ($parm)
		{
			case 'link':
				return '<a href="' . $url . '" class="news-category">' . $this->sc_news_category_title() . '</a>';
				break;

			case 'link_icon':
				return '<a href="' . $url . '" class="news-category">' . $this->sc_news_category_icon() . '&nbsp;' . $this->sc_news_category_title() . '</a>';
				break;

			default:
				return $url;
				break;
		}
	}

	/**
	 * @return string
	 */
	public function sc_news_category_link()
	{
		return $this->sc_news_category_url('link');
	}

	/**
	 * @param $parm
	 * @return array|string
	 */
	public function sc_news_category_icon($parm = '')
	{
		if (!$this->cat('icon'))
		{
			return '';
		}
		if (strpos($this->cat('icon'), '{') === 0)
		{
			$src = e107::getParser()->replaceConstants($this->cat('icon'));
		}
		else
		{
			$src = e_IMAGE_ABS . 'icons/' . $this->cat('icon');
		}
		switch ($parm)
		{
			case 'src':
				return $src;
				break;
			case 'link':
				return '<a href="' . $this->sc_news_category_url() . '" class="news-category" title="' . $this->sc_news_category_title('attribute') . '"><img src="' . $src . '" class="icon news-category" alt="' . $this->sc_news_category_title('attribute') . '" /></a>';
				break;

			default:
				return '<img src="' . $src . '" class="icon news-category" alt="' . $this->sc_news_category_title('attribute') . '" />';
				break;
		}
	}

	/**
	 * @param $parm
	 * @return string
	 */
	public function sc_news_category_news_count($parm = null)
	{
 
		$nobody_regexp = "'(^|,)(" . str_replace(",", "|", e_UC_NOBODY) . ")(,|$)'";
		$time = time();

		$query = 
		"SELECT ne.news_id FROM #news_extended AS ne 
		LEFT JOIN #news AS n ON n.news_id = ne.news_id
		WHERE FIND_IN_SET({$this->getId()} , ne.news_categories)   
		AND n.news_class REGEXP '" . e_CLASS_REGEXP . "' AND NOT (n.news_class REGEXP " . $nobody_regexp . ")
		AND n.news_start < " . $time . " AND (n.news_end=0 || n.news_end>" . $time . ")";
		
		$data = e107::getDb()->retrieve($query, true);

		$news_count = count($data);
    
		if ($parm === 'raw')
		{
			return (string) $news_count;
		}

		return (string) e107::getParser()->toBadge($news_count, $parm);
	}
}
/**
 *
 */
class newsext_category_tree extends e_front_tree_model
{
	protected $_field_id = 'category_id';

	/**
	 * Load category data from the DB
	 *
	 * @param boolean $force
	 * @return e_tree_model
	 */
	public function loadBatch($force = false)
	{
		$this->setParam('model_class', 'newsext_category_item')
			->setParam('nocount', true)
			->setParam('db_order', 'category_order ASC')
			->setParam('noCacheStringModify', true)
			->setCacheString('news_category_tree')
			->setModelTable('news_category');

		return parent::loadBatch($force);
	}

	/**
	 * Load active categories only (containing active news items)
	 *
	 * @param boolean $force
	 * @return e_tree_model|e_news_category_tree
	 */
	public function loadActive($force = false)
	{

		$nobody_regexp = "'(^|,)(" . str_replace(",", "|", e_UC_NOBODY) . ")(,|$)'";
		$time = time();
 
 
		$qry = "
			SELECT nc.* FROM #news_category AS nc
			ORDER BY nc.category_order ASC
			";


		$this->setParam('model_class', 'newsext_category_item')
			->setParam('db_query', $qry)
			->setParam('nocount', true)
			->setParam('db_debug', false)
			->setCacheString(true)
			->setModelTable('news_category');

		$this->setModelTable('news_category');

		return parent::loadBatch($force);
	}

	/**
	 * Render Category tree
	 *
	 * @param array $template 
	 * @param array $parms [return, parsesc=>1|0, mode=>string]
	 * @param boolean $tablerender
	 * @return string
	 */
	function render($template = array(), $parms = array(), $tablerender = true)
	{
		if (!$this->hasTree())
		{
			return '';
		}
 
		$ret = array();
		$tp = e107::getParser();

		if (!isset($parms['parsesc'])) $parms['parsesc'] = true;
		$parsesc = $parms['parsesc'] ? true : false;

		$active = '';
		if (e_PAGE == 'news.php')
		{
			$tmp = explode('.', e_QUERY);
			if (!empty($tmp[1])) $active = $tmp[1];
		}
		$bullet = defined('BULLET') ? THEME_ABS . 'images/' . BULLET : THEME_ABS . 'images/bullet2.gif';
		$obj = new e_vars(array('BULLET' => $bullet));

		/** @var e_tree_model $cat */
		foreach ($this->getTree() as $cat)
		{
			$obj->ACTIVE = '';
			if ($active && $active == $cat->getId())
			{
				$obj->ACTIVE = ' active';
			}

			$ret[] = $cat->toHTML($template['item'], $parsesc, $obj);
		}

		if ($ret)
		{
			$separator = varset($template['separator'], '');
			$ret = $template['start'] . implode($separator, $ret) . $template['end'];
			$return = isset($parms['return']) ? true : false;

			if ($tablerender)
			{
				$caption = vartrue($parms['caption']) ? defset($parms['caption'], $parms['caption']) : LAN_NEWSCAT_MENU_TITLE; // found in plugins/news/languages/English.php

				if (!empty($parms['caption'][e_LANGUAGE]))
				{
					$caption = $parms['caption'][e_LANGUAGE];
				}

				$mod = true === $tablerender ? 'news_categories_menu' : $tablerender;
				return e107::getRender()->tablerender($caption, $ret, varset($parms['mode'], $mod), $return);
			}

			if ($return) return $ret;
			echo $ret;
		}

		return '';
	}
}
