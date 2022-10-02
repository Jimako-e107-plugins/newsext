<?php

/**
 * Copyright (C) 2008-2011 e107 Inc (e107.org), Licensed under GNU GPL (http://www.gnu.org/licenses/gpl.txt)
 * $Id$
 * 
 * News categories menu
 */

if (!defined('e107_INIT'))
{
	exit;
}

$cacheString = 'nq_news_categories_menu_' . md5(serialize($parm) . USERCLASS_LIST . e_LANGUAGE);
$cached = e107::getCache()->retrieve($cacheString);
if (true)
//if (false === $cached)
{
	e107::plugLan('news');

	if (is_string($parm))
	{
		parse_str($parm, $parms);
	}
	else
	{
		$parms = $parm;
	}

	/** @var e_news_category_tree $ctree */
	$ctree = e107::getObject('newsext_category_tree', null, e_PLUGIN . 'newsext/newscategories_class.php');

 
	$parms['tmpl']      = 'news_menu';
	$parms['tmpl_key']  = 'category';

	$template = e107::getTemplate('news', $parms['tmpl'], $parms['tmpl_key'], true, true);

	$cached = $ctree->loadActive()->render($template, $parms, true);
	e107::getCache()->set($cacheString, $cached);
}

echo $cached;
