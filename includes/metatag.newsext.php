<?php

/**
 * Determines if the current page is a newsext category page.
 *
 * @return mixed
 *  True if the current page is a news category page, otherwise false.
 */
function metatag_entity_newsext_category_detect()
{
 
	if(e107::route() == 'newsext/category' )
	{
		return (int) $_GET['id'];
	}

	return false;
}

/**
 * Determines if the current page is a newsext index page.
 *
 * @return mixed
 *  True if the current page is a news list page, otherwise false.
 */
function metatag_entity_newssext_index_detect()
{

	if (e107::route() == 'newsext/index')
	{
		return true;
	}

	return false;
}
 
function metatag_entity_newsext_category_load($category_id)
{
	
	$db = e107::getDb();
	$db->select('news_category', '*', 'category_id = ' . (int) $category_id);

	$entity = array();

	while($row = $db->fetch())
	{
		$entity = $row;
		$entity['category_url'] = e107::url('newsext', 'category', $row, array('mode'=>'full'));
	}
 
	return $entity;
}


 