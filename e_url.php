<?php
/*
 * e107 Bootstrap CMS
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 * 
 */

if (!defined('e107_INIT'))
{
	exit;
}

// v2.x Standard 

class newsext_url  
{
	function config()
	{
		$config = array();

		$alias = 'news-categories';




		$config['category'] = array(
			'alias'         => $alias,
			'regex'			=> '^{alias}\/([\d]*)(?:\/|-)([\w-]*)/?\??(.*).html(.*)',
			'sef'			=> '{alias}/{category_id}/{category_sef}.html',
			'redirect'		=> '{e_PLUGIN}newsext/newscategory.php?action=list&id=$1',
		);

		$config['category-canonical'] = array(
			'alias'         => $alias,
			'sef'			=> '{alias}/{category_id}/{category_sef}.html?page={category_page}',
		);

		$config['index'] = array(
			'alias'         => $alias,
			'regex'			=> '^{alias}([\w-]*)\/(.*)',
			'sef'			=> '{alias}/',
			'redirect'		=> '{e_PLUGIN}newsext/newscategory.php?action=index&id=0&',
		);


		$config['index-canonical'] = array(
			'alias'         => $alias,
			'sef'			=> '{alias}/?page={index_page}'
		);

		return $config;
	}
}
