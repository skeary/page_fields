<?php

	require_once(EXTENSIONS . '/page_fields/lib/page_fields_defines.php');
	require_once(TOOLKIT . '/class.datasource.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
	

	/**
	 * Data source to display values for the single entry in the
	 * current page's associated page Fields section.
	 *
	 * Note that since the data source retrieves data from different sections
	 * it may have a different schema for each page.
	 */
	Class datasourcepage_fields extends Datasource
	{
		/**
		 * The root element for this data source XML.
		 */
		public $dsParamROOTELEMENT = 'page-fields';


		public $dsParamORDER = 'desc';
		public $dsParamLIMIT = '1';
		public $dsParamREDIRECTONEMPTY = 'no';
		public $dsParamSORT = 'system:id';
		public $dsParamSTARTPAGE = '1';
		public $dsParamINCLUDEDELEMENTS = array();


		/**
		 * Member to hold onto the section id for the page's associated page field
		 * section.  This is set in the {@see processParameters()} method and then
		 * accessed in the {@see grab()} and {@see getSource()} methods because 
		 * it is not available directly in them.	 
		 */
		private $pageFieldsSectionId;

		public function __construct(&$parent, $env = NULL, $process_params = true)
		{
			parent::__construct($parent, $env, $process_params);
			$this->_dependencies = array();
		}


		/**
		 * Returns array of information about the data source for Symphony to display.
		 *
		 * @returns The summary information.
		 */
		public function about()
		{
			return array(
				'name' => 'Page Fields',
				'author' => array(
					'name' => 'Simon Keary',
					'website' => 'http://www.birdstudios.com.au',
					'email' => 'hello@birdstudios.com.au'
				),
				'version' => '1.0',
				'release-date' => '2009-07-01T15:20:20+00:00'
			);	
		}
	

		/**
		 * Returns the page fields sectin for the current page.
		 */
		public function getSource()
		{
			return $this->pageFieldsSectionId;
		}
		
		
		/**
		 * Is the editor allowed to pars this data source.
		 */
		public function allowEditorToParse()
		{
			return true;
		}	


		/**
		 * Process the page's parameters.  This is used to extract the page id
		 * and set the associated data source to the id of the Page Fields section
		 * for it.
		 *
		 * @param array env The parameters.
		 */
		function processParameters($env = NULL)
		{
			// Get the handle of the PF section associated with the page.
			//
			$pageId = $env['param']['current-page-id'];
			$pageFieldsSectionHandle = Lang::createHandle(PF_SECTION_TITLE_PREFIX . $pageId);

			// Retrieve and store the Id of the section so we can return it from getSource()
			//
			$sectionManager = new SectionManager($this->_Parent);
			$this->pageFieldsSectionId = $sectionManager->fetchIDFromHandle($pageFieldsSectionHandle);
			

			// Initialise $dsParamINCLUDEDELEMENTS with the names of all fields for the section.
			//
			$fieldNames = $this->_Parent->Database->fetchCol('element_name', "SELECT `element_name` FROM `tbl_fields` WHERE `parent_section` = '$this->pageFieldsSectionId'");
			$this->dsParamINCLUDEDELEMENTS = array();
			
			if(is_array($fieldNames) && !empty($fieldNames))
			{
				foreach($fieldNames as $elementName)
				{
					$this->dsParamINCLUDEDELEMENTS[] = $elementName;
				}
			}

			// Call parent class implementation.
			//
			parent::processParameters($env);
		}
		
		
		/**
		 * Returns an xml representation of the Page Fields data ("Page Content") for the current page.
		 *
		 * @param array param_pool The output? parameters.
		 */
		public function grab(&$param_pool)
		{
			$result = new XMLElement($this->dsParamROOTELEMENT);
						
			if ($this->pageFieldsSectionId == NULL)
			{
				return $this->emptyXMLSet();
			}
	
			try
			{
				include(TOOLKIT . '/data-sources/datasource.section.php');
			}
			catch(Exception $e)
			{
				$result->appendChild(new XMLElement('error', $e->getMessage()));
				return $result;
			}	

			if($this->_force_empty_result)
			{
				$result = $this->emptyXMLSet();
			}
			return $result;
		}
	}

?>
