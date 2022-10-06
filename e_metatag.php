<?php

/**
 * @file
 * Metatag addon file.
 */


/**
 * Class metatag_metatag.
 *
 * Usage: PLUGIN_metatag
 */
class newsext_metatag
{

	public function config()
	{

		$config['newsext_list'] = array(
			'name'   => "NewsExt List Page",
			'detect' => 'metatag_entity_newssext_index_detect',
			'file'   => '{e_PLUGIN}newsext/includes/metatag.newsext.php',
			'dependencies' => array(
				'plugin'  => 'news',
			),
		);
		return $config;
	}

	/**
	 * Alter config before caching takes place.
	 *
	 * @param $config
	 */
	public function config_alter(&$config)
	{
 
		if(e107::route() == "newsext/category")
		{
			$config['news-category']['detect'] = 'metatag_entity_newsext_category_detect';
			$config['news-category']['load'] = 'metatag_entity_newsext_category_load';
			$config['news-category']['file'] = '{e_PLUGIN}newsext/includes/metatag.newsext.php';
		}	
	}
}


