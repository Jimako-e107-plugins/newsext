  <?php


	e107::coreLan('newspost', true);


	class news_extended_adminArea extends e_admin_dispatcher
	{

		protected $modes = array(

			'main'	=> array(
				'controller' 	=> 'news_extended_ui',
				'path' 			=> null,
				'ui' 			=> 'news_extended_form_ui',
				'uipath' 		=> null,
				'perm'          => null
			),

			'categories'	=> array(
				'controller' 	=> 'newsext_cat_ui',
				'path' 			=> null,
				'ui' 			=> 'newsext_cat_form_ui',
				'uipath' 		=> null,
				'perm'          => null
			),

		);


		protected $adminMenu = array(

			'main/list'				=> array('caption' => LAN_MANAGE, 'perm' => 'P', 'url' => 'admin_config.php'),
			'main/div0'      		=> array('divider' => true),
			'categories/list'	 	=> array('caption' => LAN_CATEGORIES, 'icon' => 'folder', 'perm' => 'H', 'url' => 'admin_news_categories.php'),
			'categories/create'		=> array(
				'caption' => LAN_NEWS_63,
				'icon' => 'fas-folder-plus',
				'perm' => 'H', 'url' => 'admin_news_categories.php'
			),

			// 'main/div0'      => array('divider'=> true),
			// 'main/custom'		=> array('caption'=> 'Custom Page', 'perm' => 'P'),

		);

		//Route access. (equivalent of getperms() for each mode/action )
		protected $perm = array(
			'main/list'     => 'H|H0|H1|H2',
			'main/create'   => 'H|H0',
			'main/edit'     => 'H|H1', // edit button and inline editing in list mode.
			'main/delete'   => 'X', // delete button in list mode.
			'cat/list'      => 'H',
			'cat/create'    => 'H|H3|H4|H5',
			'cat/edit'      => 'H|H4', // edit button and inline editing in list mode.
			'cat/delete'    => 'H|H5', // delete button in list mode.
			'main/settings' => '0',
			'sub/list'      => 'N'
		);


		protected $adminMenuAliases = array(
			'main/edit'	=> 'main/list',
			'cat/edit'	=> 'cat/list'
		);

		protected $menuTitle = 'News Extended';
	}
