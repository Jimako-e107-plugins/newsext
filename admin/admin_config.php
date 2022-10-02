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

// e107::lan('news_extended',true);
e107::coreLan('newspost', true);

/* TODO LIST 
1. Hide delete button from record options
2. Add event on delete news 
3. Not allow action create, removing from menu is not enough
4. Batch support
*/

/* generate data - only for inline editing, edit mode still works */

$query = "SELECT n.news_id, n.news_title, n.news_category, 
          ne.news_id AS id, ne.news_categories FROM `#news` AS n 
	 	  LEFT JOIN `#news_extended` AS ne ON n.news_id = ne.news_id
		  WHERE ne.news_id IS NULL"; 

$missing_records = e107::getDb()->retrieve($query , true);
 
foreach($missing_records AS $row) {
	$insert_ne = [
		'data' => [
			'news_id' => $row['news_id'],
			'news_categories' =>
			$row['news_category'],
		],
		'_DUPLICATE_KEY_UPDATE' => true,
	];

	$cid = e107::getDb()->insert('news_extended', $insert_ne);
}


class news_extended_adminArea extends e_admin_dispatcher
{

	protected $modes = array(

		'main'	=> array(
			'controller' 	=> 'news_extended_ui',
			'path' 			=> null,
			'ui' 			=> 'news_extended_form_ui',
			'uipath' 		=> null
		),

	);


	protected $adminMenu = array(

		'main/list'			=> array('caption' => LAN_MANAGE, 'perm' => 'P'),
		//	'main/create'		=> array('caption' => LAN_CREATE, 'perm' => 'P'),

		// 'main/div0'      => array('divider'=> true),
		// 'main/custom'		=> array('caption'=> 'Custom Page', 'perm' => 'P'),

	);

	protected $adminMenuAliases = array(
		'main/edit'	=> 'main/list'
	);

	protected $menuTitle = 'News Extended';
}





class news_extended_ui extends e_admin_ui
{

	protected $pluginTitle		= 'News Extended';
	protected $pluginName		= 'news_extended';
	protected $fieldPrefName	= 'news_extended';
	protected $table 		= "news";
	protected $pid			= "news_id";
	protected $perPage			= 40;
	protected $batchDelete		= false;
	protected $batchExport     	= true;
	protected $batchCopy		= false;

	protected $listQry      = "SELECT SQL_CALC_FOUND_ROWS n.news_id, n.news_title, n.news_category,  
								ne.news_categories 
							   FROM `#news` AS n 
	 						   LEFT JOIN `#news_extended` AS ne ON n.news_id = ne.news_id 
                              
                              "; // without any Order or Limit.

	protected $editQry = "SELECT n.news_id, n.news_category,ne.news_categories FROM `#news` AS n LEFT JOIN `#news_extended` AS ne ON n.news_id = ne.news_id WHERE n.news_id = {ID}";


	protected $listOrder	= "news_id desc";
	protected $fields = array(
		'checkboxes'	   		=> array('title' => '', 			'type' => null, 		'width' => '3%', 	'thclass' => 'center first', 	'class' => 'center', 	'nosort' => true, 'toggle' => 'news_selected', 'forced' => TRUE),
		'news_id'				=> array('title' => LAN_ID, 	    'type' => 'text', 	    'width' => '5%', 	'thclass' => 'center', 			'class' => 'center',  	'nosort' => false, 'readParms' => 'link=sef&target=blank'),
		'news_title'			=> array(
			'title' => LAN_TITLE, 		'type' => 'text',   'noedit' => true,  'readonly' => TRUE,
			'data' => 'safestr',  'filter' => true,  'tab' => 0, 'writeParms' => array('required' => 1, 'size' => 'block-level'), 'inline' => true,		'width' => 'auto', 'thclass' => '', 				'class' => null, 		'nosort' => false
		),

		'news_category'			=> array('title' => 'Primary '. NWSLAN_6, 		'type' => 'dropdown',   'data' => 'int', 'tab' => 0, 'inline' => true,	'width' => 'auto', 	'thclass' => '', 				'class' => null, 		'nosort' => false, 'batch' => true, 'filter' => true),

		'news_categories'         =>

		array(
			'title' => 'Multi Categories',  
			'type' => 'dropdown',
			'data' => false,  
			'width' => 'auto',  
		//	'batch' => 'value', 
			'inline' => true,   
			'filter' => 1,  
			'help' => '',
			'readParms' => array('type' => 'checkboxes'),  'writeParms' =>  array(),
			'class' => 'left',  'thclass' => 'left',
		),

		'options'				=> array(
			'title' => LAN_OPTIONS, 	
			'type' => null, 		
			'width' => '10%', 	
			'thclass' => 'center last',
			'class' => 'center', 	
			'nosort' => true,
			'forced' => TRUE
		)

	);

	protected $fieldpref = array('checkboxes', 'news_id', 'news_thumbnail', 'news_title', 'news_datestamp', 'news_category', 'news_class', 'options', 'news_categories');

	//	protected $preftabs        = array('General', 'Other' );
	protected $prefs = array();


	public function init()
	{

		e107::getDb()->gen("SELECT category_id,category_name FROM #news_category");
		while ($row = e107::getDb()->fetch())
		{
			$cat = $row['category_id'];
			$this->cats[$cat] = $row['category_name'];
		}
		asort($this->cats);

		$this->fields['news_category']['writeParms']['optArray'] = $this->cats;
		$this->fields['news_category']['writeParms']['multiple'] = 0;

		$this->fields['news_categories']['writeParms']['optArray'] = $this->cats;
		$this->fields['news_categories']['writeParms']['multiple'] = 1;
	}

 
	//nasty way how to not deleting anything 
	public function ListDeleteTrigger($posted)
	{
		//create is not allowed
		return NULL;
	}
 
	// ------- Customize Create --------

	public function beforeCreate($new_data, $old_data)
	{
		//create is not allowed
		return NULL;
	}

	public function afterCreate($new_data, $old_data, $id)
	{
		// do something
	}

	public function onCreateError($new_data, $old_data)
	{
		// do something		
	}


	// ------- Customize Update --------

	public function beforeUpdate($new_data, $old_data, $id)
	{
 
		if (!empty($new_data['news_categories']))
		{

			$categories = $new_data['news_categories']; //it is already imploded - diff vs e_admin addon

			$insert_ne = [
				'data' => [
					'news_id' => $id,
					'news_categories' => $categories
				],
				'_DUPLICATE_KEY_UPDATE' => true,
			];

			$cid = e107::getDb()->insert('news_extended', $insert_ne);

			/*
			$query = "INSERT INTO " . MPREFIX . "news_extended ( `news_id`, `news_categories`) VALUES ( {$id}, '{$categories}') ON DUPLICATE KEY UPDATE 
			news_categories = '{$categories}'; ";
			*/
		}

		return $new_data;
	}

	public function afterUpdate($new_data, $old_data, $id)
	{

		// do something	
	}

	public function onUpdateError($new_data, $old_data, $id)
	{
		// do something		
	}

	 
	public function renderHelp()
	{
		$caption = LAN_HELP;
		$text = 'The list of news with related categories. Mainly for overview. You can set this on news page too. ';

		return array('caption' => $caption, 'text' => $text);
	}
}



class news_extended_form_ui extends e_admin_form_ui
{
}


new news_extended_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
