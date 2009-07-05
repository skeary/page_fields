<?php

	require_once(EXTENSIONS . '/page_fields/lib/page_fields_defines.php');
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
 	require_once(TOOLKIT . '/class.fieldmanager.php');
	
	
	/**
	 * Manage Page Fields admin page implementation.
	 *
	 * This admin page lists all defined pages and allows the user to create, edit,
	 * or a page fields section for each.
	 *
	 */
	class contentExtensionPage_fieldsManage extends AdministrationPage
	{
		/**
		 * Render standard list view.   All pages are listed in a table
		 * and links to edit, create or delete Page Fields actions as applicable.
		 */
		public function __viewIndex()
		{
			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Pages Fields'))));
			
			$this->appendSubheading(__('Page Fields'));

			// Retrieve all pages and, if applicable, the section id of the associated
			// page fields section.
			//
			$pfSectionHandlePrefix = Lang::createHandle(PF_SECTION_TITLE_PREFIX);
			if (strrpos(PF_SECTION_TITLE_PREFIX, ' ') === strlen(PF_SECTION_TITLE_PREFIX) - 1)
			{
				$pfSectionHandlePrefix .= '-';
			}

			$pages = $this->_Parent->Database->fetch(
				"SELECT
					p.*, s.id as section_id
				FROM
					`tbl_pages` AS p LEFT OUTER JOIN `tbl_sections` AS s
				ON (s.handle = CONCAT('" . $pfSectionHandlePrefix . "', p.id))
				ORDER BY
					p.sortorder ASC"
			);

			// Create column headers for table.  These are, page title, page url and page fields actions.
			//
			$aTableHead = array(
				array(__('Page Title'), 'col'),
				array(__('Page <acronym title="Universal Resource Locator">URL</acronym>'), 'col'),
				array(__('Page Field Actions'), 'col')
			);	
			
			$aTableBody = array();
			
			if (!is_array($pages) or empty($pages))
			{
				// There are no pages defined
				//
				$aTableBody = array(Widget::TableRow(array(
					Widget::TableData(__('None found.'), 'inactive', null, count($aTableHead))
				), 'odd'));
				
			}
			else
			{
				$isOdd = true;

				// Append row for each page
				//
				foreach ($pages as $page)
				{
					$aTableBody[] = $this->createViewPageFieldsRowForPage($page, $isOdd);
					$isOdd = !$isOdd;
				}
			}
			
			$table = Widget::Table(
				Widget::TableHead($aTableHead), null, 
				Widget::TableBody($aTableBody), null
			);
			
			$this->Form->appendChild($table);
		}


		/**
		 * Create row for page on the manage page fields page.  This contains the applicable actions
		 * for the page fields.
		 *
		 * @param array page The page details.
		 * @param bool Is the row odd or not?
		 *
		 * @return Widget Row for page.
		 */
		private function createViewPageFieldsRowForPage($page, $isOdd)
		{
			$class = array();
			$pageTitle = $this->_Parent->resolvePageTitle($page['id']);
			$pageUrl = URL . '/' . $this->_Parent->resolvePagePath($page['id']) . '/';
			$pageEditUrl = URL . '/symphony/blueprints/pages/edit/' . $page['id'] . '/';
			
			$colTitle = Widget::TableData(
				Widget::Anchor($pageTitle, $pageEditUrl, $page['handle'])
			);
			$colTitle->appendChild(Widget::Input("items[{$page['id']}]", null, 'checkbox'));
			
			$colUrl = Widget::TableData(Widget::Anchor($pageUrl, $pageUrl));
			
			if ($page['section_id'] == NULL)
			{
				$colActions = Widget::TableData(
					Widget::Anchor('Create', URL . PF_MANAGE_URL . 'create/' . $page['id'] . '/')
				);
			}
			else
			{
				$colActions = Widget::TableData();
				$colActions->appendChild(Widget::Anchor('Edit', URL . '/symphony/blueprints/sections/edit/' . $page['section_id'] . '/'));
				$colActions->appendChild(new XMLElement('span', ', '));
				$colActions->appendChild(Widget::Anchor('Delete', URL . PF_MANAGE_URL . 'delete/' . $page['section_id'] . '/'));
			}
			
			if ($isOdd)
			{
				$class[] = 'odd';
			}
			if (in_array($page['id'], $this->_hilights))
			{
				$class[] = 'failed';
			}
		
			return Widget::TableRow(
				array($colTitle, $colUrl, $colActions),
				implode(' ', $class)
			);
		}


		/**
		 * Render create Page Fields section admin page.  This creates the Page Fields section
		 * for the specified page.
		 *
		 */
		public function __viewCreate()
		{
			// Retrieve title and handle of specified pages.
			//
			$pageId = $this->_context[1];
			$pageQuery = "SELECT handle, title FROM `tbl_pages` WHERE `id` = $pageId LIMIT 1";
			$pageRow = $this->_Parent->Database->fetchrow(0, $pageQuery);
			
			// Now get the Id for the next extry in the section table.  We need this to create
			// a new entry.
			//
			$nextSectionIdQuery = 'SELECT MAX(`sortorder`) + 1 AS `next` FROM tbl_sections LIMIT 1';
			$nextSectionId = $this->_Parent->Database->fetchVar('next', 0, $nextSectionIdQuery);
			
			// Initialise details of the new section.
			//
			$newSectionDetails['sortorder'] = ($nextSectionId ? $nextSectionId : '1');
			$newSectionDetails['handle'] = Lang::createHandle(PF_SECTION_TITLE_PREFIX . $pageId);
			$newSectionDetails['navigation_group'] = 'Content';
			$newSectionDetails['name'] = PF_SECTION_TITLE_PREFIX . $pageId;
			$newSectionDetails['hidden'] = 'yes';

			// Create the section and then display the (now updated) index page.
			// 
			$sectionManager = new SectionManager($this->_Parent);
			$sectionManager->add($newSectionDetails);

			// Reditect to the manage page fields page.  (We can't just call __viewIndex()
			// to render the page as we need to redraw the menu).
			//
			redirect(URL . PF_MANAGE_URL);
		}


		/**
		 * Render delete Page Fields section admin page.  This deletes the Page Fields section.
		 *
		 */
		public function __viewDelete()
		{
			$sectionId = $this->_context[1];

			// Delete the section.
			//
			$sectionManager = new SectionManager($this->_Parent);
			$sectionManager->delete($sectionId);

			// Reditect to the manage page fields page.  (We can't just call __viewIndex()
			// to render the page as we need to redraw the menu).
			//
			redirect(URL . PF_MANAGE_URL);
		}
	}
	
?>