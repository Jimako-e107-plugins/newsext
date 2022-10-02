<?php
/*
 * e107 Bootstrap CMS
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 * 
 * IMPORTANT: Make sure the redirect script uses the following code to load class2.php: 
 * 
 * 	if (!defined('e107_INIT'))
 * 	{
 * 		require_once(__DIR__.'/../../class2.php');
 * 	}
 * 
 */

if (!defined('e107_INIT'))
{
	exit;
}

// v2.x Standard  - Simple mod-rewrite module. 

class newsext_url // plugin-folder + '_url'
{
	function config()
	{
		$config = array();

		$config['category-page'] = array(
			'alias'         => 'news-category',
			'regex'			=> '^{alias}\/([\d]*)(?:\/|-)([\w-]*)/?\??(.*).html\?page=([\d]*)',
			'sef'			=> '{alias}/{id}/{category_sef}.html?page={page}',
			'redirect'		=> '{e_PLUGIN}newsext/newscategory.php?list.$1.$4',
		);

		$config['category'] = array(
			'alias'         => 'news-category',
			'regex'			=> '^{alias}\/([\d]*)(?:\/|-)([\w-]*)/?\??(.*).html',
			'sef'			=> '{alias}/{id}/{name}.html',
			'redirect'		=> '{e_PLUGIN}newsext/newscategory.php?list.$1.0',
		);

		return $config;
	}
}
