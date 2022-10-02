<?php

class newsext_shortcodes extends e_shortcode
{
	public $override = true;

	function sc_newscategories()
	{
		$sc = e107::getScBatch('news');
		$data = $sc->getScVar('news_item');

		if ($data['news_id'])
		{
			$cats_str = e107::getDb()->retrieve('news_extended', 'news_categories', 'news_id =' . $data['news_id']);
			$cats_array = explode(',', $cats_str);
			foreach ($cats_array as $cat)
			{
				$category = e107::getDb()->retrieve('news_category', '*', 'category_id =' . $cat . ' LIMIT 1');

				$news_item = $category;
				$category_name = !empty($news_item['category_name']) ? e107::getParser()->toHTML($news_item['category_name'], FALSE, 'defs') : '';
				$category = !empty($news_item['category_id']) ? array('id' => $news_item['category_id'], 'name' => $news_item['category_sef']) : array();
				//	$categoryClass = varset($GLOBALS['NEWS_CSSMODE'],'');
				//$style = isset($this->param['catlink']) ? "style='".$this->param['catlink']."'" : '';

				$items[] = "<a class='badge text-bg-secondary' href='" . e107::url('newsext/category', $category) . "'>" . $category_name . "</a>";
			}
		}
		$text = implode(" ", $items);
		return $text;
	}
}
