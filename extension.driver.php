<?php

	require_once(EXTENSIONS . '/page_fields/lib/page_fields_defines.php');
	
	/**
	 * Page Fields Extension class.  Provides delegates in order
	 * to customise admin user interface for page fields support.
	 *
	 * Page fields provide a mechanism to more easily associate a page
	 * with a unique section and automatically provide a data source for it that
	 * can be used in the page xslt.  Addditionally, by customising the user
	 * interface, the user is prevented from adding more than one entry to
	 * the section.
	 */
	Class extension_page_fields extends Extension
	{
		public function about()
		{
			return array(
				'name'         => 'Page Fields',
				'version'      => '1.0.3',
				'release-date' => '2009-07-05',
				'author'       => array(
					'name'    => 'Simon Keary',
					'website' => 'http://www.birdstudios.com.au',
					'email'   => 'hello@birdstudios.com.au'
				)
			);
		}

		/**
		 * Returns the list of delegates to call for events we are interested in knowing
		 * about.  This method is called by the Symphony framework.
		 *
		 * @return An array of delegate information.
		 */
		public function getSubscribedDelegates()
		{
			return array(
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendPageResolved',
					'callback'	=> 'addPageFieldsDataSourceToPage'
				),
				array(
					'page' => '/administration/',
					'delegate' => 'ExtensionsAddToNavigation',
					'callback' => 'customiseAdminNavigationMenus'
				),
				array(
					'page' => '/administration/',
					'delegate' => 'AdminPagePostGenerate',
					'callback' => 'customiseAdminPageOutput'
				),
				array(
					'page' => '/system/preferences/',
					'delegate' => 'AddCustomPreferenceFieldsets',
					'callback' => 'displayPreferences'
				),
				array(
					'page' => '/system/preferences/',
					'delegate' => 'Save',
					'callback' => 'savePreferences'
				)
			);
		}

		
		/**
		 * Installs the extension.
		 *
		 * @return true iff the extension is installed.  false, otherwise.
		 */
		public function install()
		{	
			return true;
		}			

	
		/**
		 * Adds the "page_fields" data source to the current page.  This means
		 * that the page fields data will automatically be available as a datasource to
		 * access from the page's xslt without the user having to explicitly add it.
		 *
		 * @param array context The context for the page.
		 */
		public function addPageFieldsDataSourceToPage($context)
		{	
			$pageDataSources = $context['page_data']['data_sources'];
			$pageDataSources = explode(',', $pageDataSources);
			$pageDataSources = array_merge($pageDataSources, array('page_fields'));
			$pageDataSources = array_unique($pageDataSources);
			$pageDataSources = implode(',', $pageDataSources);
			
			// Apply the updated list of data sources to the page.
			//
			$context['page_data']['data_sources'] = $pageDataSources;
		}


		/**
		 * Handler to customise applicable admin pages for page fields support.
		 *
		 * @param array context The context for the page.
		 */
		public function customiseAdminPageOutput($context)
		{
			// Load the page HTML into a DOM so we can examine and modify it.
			//
		    $dom = new DOMDocument;
			$dom->preserveWhiteSpace = true; 

			function maskErrors() {}
			set_error_handler('maskErrors');
			$dom->loadHTML($context['output']);
			restore_error_handler();
	
			// Locate the form element in the page and get it's action attribute.
			// This indicates the page that is being rendered.
			//
			$form = $dom->getElementsByTagName('form')->item(0);
			$formAction = $form->getAttribute('action');

			// Customise the page if applicable.
			//
			if (strpos($formAction, '/symphony/blueprints/sections/edit/') != FALSE)
			{
				$this->customiseEditSectionAdminPage($dom, $form);
			}
			else if (strpos($formAction, '/symphony/publish/') != FALSE)
			{
				$this->customisePublishAdminPage($dom);
			}
			else if (substr($formAction, -strlen('/blueprints/sections/')) === '/blueprints/sections/')
			{
				$this->customiseListSectionsAdminPage($form);
			}
			else
			{
				// It's not a page we want to customise so return without doing anything.
				//
				return;
			}

			// Update the output page HTML with our customised version.
			//
			$context['output'] = $dom->saveHTML();
		}


		/**
		 * Handler to customise the edit section admin page.  For "page fields" sections 
		 * this hides the "Essentials" fieldset as we don't want the user to view (or edit) it.
		 *
		 * @param DOMDocument dom A DOM representation of the page.
		 * @param DOMElement form The single form element contained within the page.
		 */
		protected function customiseEditSectionAdminPage(&$dom, &$form)
		{
			$h2 = $form->getElementsByTagName('h2')->item(0);
			
			if (strpos($h2->nodeValue, PF_SECTION_TITLE_PREFIX) === 0)
			{
				$pageId = substr($h2->nodeValue, strlen(PF_SECTION_TITLE_PREFIX));
				
				$getPageTitleQuery = 'SELECT `title` FROM tbl_pages WHERE id=\'' . $pageId . '\'';
				$associatedPageName = $this->_Parent->Database->fetchVar('title', 0, $getPageTitleQuery);
				if ($associatedPageName === NULL)
				{
					// If we can't locate the page name fall back to the specified page id.
					//
					$associatedPageName = $pageId;
				}

				$title = $dom->getElementsByTagName('title')->item(0);
				$h2->nodeValue = 'Page Fields For ' . $associatedPageName;
				$title->nodeValue = 'Symphony &ndash; ' . $h2->nodeValue;

				// Hide the first "Essentials" fieldset.  This contains entry fields for the
				// section name, navigation group and show on publish menu checkbox.  For a page
				// fields section we don't want the user to change any of these.
				//
				$essentialsFieldSetIdx = 0;
				$fieldset = $form->getElementsByTagName('fieldset')->item($essentialsFieldSetIdx);
				$fieldset->setAttribute('style', 'display: none');
			}
		}


		/**
		 * Handler to customise the publish admin page.  For entries for "page fields" sections 
		 * this cleans up the title.
		 *
		 * @param DOMDocument dom A DOM representation of the page.
		 */
		protected function customisePublishAdminPage(&$dom)
		{
			$title = $dom->getElementsByTagName('title')->item(0);

			$pfSectionTitlePrefixPos = strpos($title->nodeValue, PF_SECTION_TITLE_PREFIX); 

			if ($pfSectionTitlePrefixPos > 0)
			{
				$pageId = substr($title->nodeValue, $pfSectionTitlePrefixPos + strlen(PF_SECTION_TITLE_PREFIX));
				$pageId = substr($pageId, 0, strpos($pageId, ' '));
				
				$getPageTitleQuery = 'SELECT `title` FROM tbl_pages WHERE id=\'' . $pageId . '\'';
				$associatedPageName = $this->_Parent->Database->fetchVar('title', 0, $getPageTitleQuery);
				
				$title->nodeValue = 'Symphony &ndash; ' . $this->getPageContentMenuLabel() . ' for ' . $associatedPageName;
			}
			
			$buttons = $dom->getElementsByTagName('button');
			foreach ($buttons as $button)
			{
				if ($button->getAttribute('name') === 'action[delete]')
				{
					$button->parentNode->removeChild($button);
					break;
				}
			}
		}


		/**
		 * Handler to customise the list sections admin page.  All "page fields" sections 
		 * are hidden as we don't want the user to play around with them as "normal" sections.
		 * Note that page fields sections are listed in, and edited through, the manage Page Fields
		 * admin page.
		 *
		 * @param DOMElement form The single form element contained within the page.
		 */
		protected function customiseListSectionsAdminPage(&$form)
		{
			$tbody = $form->getElementsByTagName('tbody')->item(0); 
			$rows = $tbody->getElementsByTagName('tr'); 
	
			$nodesToDelete = array();

			$rowIdx = 1;	
			foreach($rows as $row)
			{
				$anchor = $row->getElementsByTagName('a')->item(0); 								
				$alias = $anchor->nodeValue;
				
				if (strpos($alias, PF_SECTION_TITLE_PREFIX) === 0)
				{
					$nodesToDelete[] = $row;
				}
				else
				{
					if ($rowIdx % 2 == 1)
					{
						$row->setAttribute('class', 'odd');
					}
					else
					{
						$row->removeAttribute('class');
					}
					$rowIdx = $rowIdx + 1;
				}
			}
				
			foreach($nodesToDelete as $node)
			{	
				$tbody->removeChild($node);
			}
		}
		
		
		/**
		 * Customise the admin menu to support Page Fields.
		 *
		 * Note that we use a delegate rather than override {@see fetchNavigation()}
		 * as that doesn't support accessing the database.
		 *
		 * @param array context The context for the page.
		 */
		public function customiseAdminNavigationMenus($context)
		{
			$childMenuItems = array();
		
			// Get all the pages that have associated Page Field sections.
			//
			$pfSectionHandlePrefix = Lang::createHandle(PF_SECTION_TITLE_PREFIX);
			if (strrpos(PF_SECTION_TITLE_PREFIX, ' ') === strlen(PF_SECTION_TITLE_PREFIX) - 1)
			{
				$pfSectionHandlePrefix .= '-';
			}
			$pages = $this->_Parent->Database->fetch(
				"SELECT
					p.*, s.id as section_id, s.handle as section_handle
				FROM
					`tbl_pages` AS p INNER JOIN `tbl_sections` AS s
				ON (s.handle = CONCAT('" . $pfSectionHandlePrefix . "', p.id))
				ORDER BY
					p.sortorder ASC"
			);

			if (is_array($pages))
			{
				// Add a menu item for each page to the Page Content menu.  These allow the user to
				// enter values for the pages fields (ie. edit/create the entry in the associated
				// page fields section).
				//
				foreach ($pages as $page)
				{
					$getEntryIdForPageFieldsSectionQuery = "SELECT id FROM `tbl_entries` WHERE section_id = " . 
						$page['section_id'] . " LIMIT 1";
					
					$entryId = $this->_Parent->Database->fetchVar('id', 0, $getEntryIdForPageFieldsSectionQuery);

					if ($entryId == NULL)
					{
						$childMenuItems[] = array(
							'name' => $page['title'],
							'link' => '/publish/' . $page['section_handle'] . '/new/'							
						);
					}
					else
					{
						$childMenuItems[] = array(
							'name' => $page['title'],
							'link' => '/publish/' . $page['section_handle'] . '/edit/' . $entryId . '/'							
						);
					}
				}
			}

			// Add the "Page Content' menu.
			//
			$pageContentMenu = array(
				'index' => 10,
				'name' => $this->getPageContentMenuLabel(),
				'children' => $childMenuItems
			);

			$context['navigation'][10] = $pageContentMenu;
			
			// Now add a link to allow the user to manage Page Fields.
			//
			$pageFieldsMenuItem = array(
				'link' => '/extension/page_fields/manage/',
				'name' => 'Page Fields',
				'visible' => 'yes'
			);
			$context['navigation'][100]['children'][] = $pageFieldsMenuItem;  
		}
		

		/**
		 * Saves the Page Fields preferences.
		 *
		 * @param array context The context for the page.
		 */
		public function savePreferences($context)
		{
			$newMenuLabelText = $_POST['page_fields']['page_content_menu_label'];
			$this->_Parent->Configuration->set('page_content_menu_label', $newMenuLabelText, 'page_fields');
		}
		
		
		/**
		 * Add the Page Field preferences to the display/edit preference admin page.
		 *
		 * @param array context The context for the page.
		 */
		public function displayPreferences($context)
		{
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', 'Page Fields'));			

			$label = Widget::Label('Page Content Menu Label');
			$label->appendChild(
				Widget::Input('page_fields[page_content_menu_label]', $this->getPageContentMenuLabel())
			);

			$group->appendChild($label);
						
			$group->appendChild(
				new XMLElement(
					'p',
					'Indicates the text label used for the menu to edit page field values. ' .
					'By default this is \'' .  __('Page Content') . '\'.', 
					array('class' => 'help')
				)
			);

			$context['wrapper']->appendChild($group);
		}
		
		
		/**
		 * Gets the configured 'Page Content' menu label text.
		 *
		 * @return The configured label text.
		 */
		private function getPageContentMenuLabel()
		{
			$menuLabel = $this->_Parent->Configuration->get('page_content_menu_label', 'page_fields');
			if ($menuLabel === NULL)
			{
				$menuLabel = __('Page Content');
			}
			return $menuLabel;					
		}
	}

?>