<?php


//v2.x Standard for extending admin areas.


class newsext_admin implements e_admin_addon_interface
{

	var $cats = array();

	public function __construct()
	{
		e107::getDb()->gen("SELECT category_id,category_name FROM #news_category");
		while ($row = e107::getDb()->fetch())
		{
			$cat = $row['category_id'];
			$this->cats[$cat] = $row['category_name'];
		}
		asort($this->cats);
	}


	/**
	 * Populate custom field values.
	 * @param string $event
	 * @param string $ids
	 * @return array
	 */
	public function load($event, $ids)
	{

		$controller = e107::getAdminUI()->getController();

		$data = e107::getDb()->retrieve('news_extended', 'news_id, news_categories', "
		`news_id` IN(" . $ids . ')', true);

		foreach ($data as $row)
		{
			$id = (int) $row['news_id'];
			$ret[$id]['news_categories'] = explode(",", $row['news_categories']);
		}

		return $ret;
	}



	/**
	 * Extend Admin-ui Configuration Parameters eg. Fields etc.
	 * @param $ui admin-ui object
	 * @return array
	 */
	public function config(e_admin_ui $ui)
	{
		$action     = $ui->getAction(); // current mode: create, edit, list
		$type       = $ui->getEventName(); // 'wmessage', 'news' etc. (core or plugin)
		$id         = $ui->getId();

		switch ($type)
		{
			case 'news': // hook into the news admin form.

				$ui->addTab('news_categories', "News Categories");



				$default = array();

				if (($action == 'edit') && !empty($id))
				{
					$record = e107::getDb()->retrieve('news_extended', 'news_categories', "
							`news_id` = " . $id);
					$nc = explode(",", $record);
					foreach ($nc as $c)
					{
						$default[$c] = $this->cats[$c];
					}
				}
				else
				{
					$default = array();
				}

				switch ($action)
				{
					case "list":
					case "create":
						$config['fields']['news_categories'] = array(
							'title' => "News Categories",
							'tab' => 'news_categories',
							'type' => 'dropdown',  'data' => 'str',    'width' => 'auto',
						);
						$config['fields']['news_categories']['writeParms']['optArray'] = $this->cats;
						$config['fields']['news_categories']['writeParms']['multiple'] = 1;
						$config['fields']['news_categories']['writeParms']['empty'] = '0';
						$config['fields']['news_categories']['writeParms']['default'] = $default;

						break;
					case "edit":
						$config['fields']['news_categories'] = array(
							'title' => "News Categories",
							'tab' => 'news_categories',
							'type' => 'method',  'data' => 'str',    'width' => 'auto',
						);
						break;
				}



				break;
		}

		//Note: 'urls' will be returned as $_POST['x_newsext_url']. ie. x_{PLUGIN_FOLDER}_{YOURFIELDKEY}

		return $config;
	}


	/**
	 * Process Posted Data.
	 * @param object $ui admin-ui
	 * @param int|array $id - Primary ID of the record being created/edited/deleted or array data of a batch process.
	 */
	public function process(e_admin_ui $ui, $id = null)
	{


		$data       = $ui->getPosted(); // ie $_POST field-data
		//$type       = $ui->getEventName(); // eg. 'news'
		$action     = $ui->getAction(); // current mode: create, edit, list, batch
		//$changed    = $ui->getModel()->dataHasChanged(); // true when data has changed from what is in the DB.


		switch ($action)
		{
			case 'create':
			case 'edit':

				if (!empty($id) && !empty($data['x_newsext_news_categories']))
				{
					$categories = implode(",", $data['x_newsext_news_categories']);
					$insert = [];
					$insert = [
						'news_id' => $id,
						'news_categories' => $categories,
						'_DUPLICATE_KEY_UPDATE' => true,
					];
				}

				e107::getDb()->insert('news_extended', $insert);

				break;

			case 'delete':

				break;

			case 'batch':
				/*	$id = (array) $id;
				$arrayOfRecordIds = $id['ids'];
				$command = $id['cmd'];
			
			*/
				break;

			default:
				// code to be executed if n is different from all labels;
		}
	}
}


/**
 * Custom field methods
 */
class newsext_admin_form extends e_form
{

	var $cats = array();

	public function __construct()
	{
		e107::getDb()->gen("SELECT category_id,category_name FROM #news_category");
		while ($row = e107::getDb()->fetch())
		{
			$cat = $row['category_id'];
			$this->cats[$cat] = $row['category_name'];
		}
		asort($this->cats);
	}

	/**
	 * @param mixed $curval
	 * @param string $mode
	 * @param null|array $att
	 * @return null|string
	 */
	function x_newsext_news_categories($curval, $mode, $att = null) // 'x_' + plugin-folder + custom-field name.
	{
		/** @var e_admin_controller_ui $controller */
		$controller = e107::getAdminUI()->getController();

		$event = $controller->getEventName(); // eg 'news' 'page' etc.
		$id = $controller->getId();

		$text = '';

		switch ($mode)
		{
			case "read":
				$field = $event . '_id'; // news_id or page_id etc.
				$text = "<span class='e-tip' title='" . $controller->getFieldVar($field) . "'>Custom</span>";
				break;

			case "write":
				$field = $event . '_id'; // news_id or page_id etc.

				$news_category =  $controller->getFieldVar('news_category');

				$curVal = array();
				$select = array();
				$curVal = e107::getDb()->retrieve('news_extended', 'news_categories', "
							`news_id` = " . $id);

				$values = explode(",", $curVal);
				$cname = 'x_newsext_news_categories[]';

				$text .= "<div class='row mb-3'>";

				foreach ($this->cats as $k => $label)
				{
					$key = $k;
					$c = in_array($k, $values) ? true : false;

					$options['label'] = "[" . $key . "] " . $label;

					if ($k == $news_category)
					{

						//$options['disabled'] = true;
						$options['label'] = "<span class='text-primary'>" . $options['label'] . "</span>";
						$c = true;
					}
					else $options['disabled'] = false;

					//using $this is not working  
					$select[] = e107::getForm()->checkbox($cname, $key, $c, $options);
				}

				//	$id = empty($options['id']) ? $this->name2id($cname) . '-container' : $options['id'];
				$text .=
					"<div id='" . $id . "' class='checkboxes checkbox' style='display:inline-block'>" . implode('', $select) . '</div>';
				$text .= '</div>';

			case "filter":
			case "batch":
				break;
		}

		return $text;
	}
}
