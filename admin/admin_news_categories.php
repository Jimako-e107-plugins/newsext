<?php

/**
 * @file
 * Admin UI.
 */

require_once('../../../class2.php');

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}

require('admin_leftmenu.php');

class newsext_cat_ui extends e_admin_ui
{
	protected $pluginTitle	= ADLAN_0; // "News"
	protected $pluginName	= 'newsext';
	protected $eventName	= 'news-category';
	protected $table 		= "news_category";
	protected $pid			= "category_id";
	protected $perPage = 0; //no limit
	protected $batchDelete = false;
	protected $batchExport = true;
	protected $sortField = 'category_order';
	protected $listOrder	= "category_order ASC";

	protected $tabs = array(LAN_GENERAL);
 

	protected $fields = array(
		'checkboxes'				=> array('title' => '',				'type' => null, 			'width' => '5%', 'forced' => TRUE, 'thclass' => 'center', 'class' => 'center'),
		'category_id'				=> array(
			'title' => LAN_ID,
			'type' => 'text',
			'readParms' => 'url=category&target=blank ',	'width' => '5%', 'forced' => TRUE, 'readonly' => TRUE
		),

		'category_icon' 			=> array(
			'title' => LAN_IMAGE,			'type' => 'image',
			'data' => 'str',		'width' => '100px',	'thclass' => 'center', 'class' => 'center',
			'readParms' => 'thumb=60&thumb_urlraw=0&thumb_aw=60',
			'writeParms' => 'glyphs=1', 'readonly' => FALSE,
			'batch' => FALSE, 'filter' => FALSE
		),		 // thumb=60&thumb_urlraw=0&thumb_aw=60

		'category_name' 			=> array('title' => LAN_TITLE,			'type' => 'text',	'data' => 'str',		'inline' => true, 'width' => 'auto', 'thclass' => 'left', 'readonly' => FALSE, 'validate' => true, 'writeParms' => array('size' => 'xxlarge')),

		'category_meta_description' => array('title' => LAN_DESCRIPTION,		'type' => 'textarea',	'data' => 'str',	'inline' => true, 'width' => 'auto', 'thclass' => 'left', 'readParms' => 'expand=...&truncate=150&bb=1', 'readonly' => FALSE, 'writeParms' => array('size' => 'xxlarge')),
		'category_meta_keywords' 	=> array('title' => LAN_KEYWORDS,		'type' => 'tags',		'data' => 'str',	'inline' => true, 'width' => 'auto', 'thclass' => 'left', 'readonly' => FALSE),
		'category_sef' 				=> array('title' => LAN_SEFURL,			'type' => 'text', 'data' => 'str',	'inline' => true,	'width' => 'auto', 'readonly' => FALSE, 'writeParms' => array('size' => 'xxlarge', 'sef' => 'category_name')), // Display name
		'category_manager' 			=> array('title' => LAN_MANAGER, 		'type' => 'userclass',	'tab' => 0,	'inline' => true, 'width' => 'auto', 'data' => 'int', 'batch' => TRUE, 'filter' => TRUE),
		'category_template'         => array('title' => LAN_TEMPLATE,       'type' => 'layouts', 'tab' => 0, 'width' => 'auto', 'thclass' => 'left', 'class' => 'left', 'writeParms' => array(), 'help' => 'Template to use as the default view'),

		'category_order' 			=> array('title' => LAN_ORDER,			'type' => 'text',	'tab' => 0,		'width' => 'auto', 'thclass' => 'right', 'class' => 'right'),
		'options' 					=> array('title' => LAN_OPTIONS,		'type' => null,		'batch' => true, 'filter' => true,		'width' => '10%', 'forced' => TRUE, 'thclass' => 'center last', 'class' => 'center', 'sort' => true)
	);

	protected $fieldpref = array('checkboxes', 'category_icon', 'category_id', 'category_name', 'category_description', 'category_sef', 'category_manager', 'category_order', 'options');

	//	protected $newspost;

	function init()
	{
		$this->fields['category_template']['writeParms'] = array('plugin' => 'news', 'id' => 'news', 'merge' => false, 'default' => '(' . LAN_OPTIONAL . ')');

	}
 
	public function beforeCreate($new_data, $old_data)
	{
		if (empty($new_data['category_sef']))
		{
			$new_data['category_sef'] = eHelper::title2sef($new_data['category_name']);
		}
		else
		{
			$new_data['category_sef'] = eHelper::secureSef($new_data['category_sef']);
		}

		$sef = e107::getParser()->toDB($new_data['category_sef']);

		if (e107::getDb()->count('news_category', '(*)', "category_sef='{$sef}'"))
		{
			e107::getMessage()->addError(LAN_NEWS_65);
			return false;
		}

		if (empty($new_data['category_order']))
		{
			$c = e107::getDb()->count('news_category');
			$new_data['category_order'] = $c ? $c : 0;
		}

		return $new_data;
	}


	public function beforeUpdate($new_data, $old_data, $id)
	{
		if (isset($new_data['category_sef']) && empty($new_data['category_sef']))
		{
			$new_data['category_sef'] = eHelper::title2sef($new_data['category_name']);
		}

		$sef = e107::getParser()->toDB($new_data['category_sef']);

		/*	$message = "Error: sef: ".$sef."   id: ".$id."\n";
			$message .= print_r($new_data,true);
			file_put_contents(e_LOG.'uiAjaxResponseInline.log', $message."\n\n", FILE_APPEND);*/

		if (e107::getDb()->count('news_category', '(*)', "category_sef='{$sef}' AND category_id !=" . intval($id)))
		{
			e107::getMessage()->addError(LAN_NEWS_65);
			return false;
		}

		return $new_data;
	}
}

class newsext_cat_form_ui extends e_admin_form_ui
{
}



new news_extended_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
