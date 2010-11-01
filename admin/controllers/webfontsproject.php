<?php
/**
 * Copyright 2010 Monotype Imaging Inc.  
 * This program is distributed under the terms of the GNU General Public License
 */
 
/**
 * Hello Controller for Hello World Component
 * 
 * @package    Joomla.Tutorials
 * @subpackage Components
 * @link http://docs.joomla.org/Developing_a_Model-View-Controller_Component_-_Part_4
 * @license		GNU/GPL
 */

// No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class WebfontsBaseControllerWebfontsProject extends WebfontsBaseController
{
	/**
	 * constructor (registers additional tasks to methods)
	 * @return void
	 */
	function __construct()
	{
	
		parent::__construct();
		// Register Extra tasks
		$this->registerTask( 'add' , 'add' );
		$this->registerTask( 'sync' , 'sync' );
		
	}
	
	/**
	 * constructor (registers additional tasks to methods)
	 * @return void
	 */
	function display()
    {
		JRequest::setVar( 'view', 'webfontsproject' );
		parent::display();
    }
	
	/*
	*project home page
	*/
	function project_list(){
		$this->setRedirect('index.php?option=com_webfonts&controller=webfontsproject');
	}
	
	/*
	*project home page
	*/
	function login_page(){
		$this->setRedirect('index.php?option=com_webfonts&controller=webfontslogin');
	}
	/**
	 * Add a record (and redirect to main page)
	 * @return void
	 */
	function add()
	{
		$model = $this->getModel('webfontsproject');
		
		if ($model->addProject($post)) {
			$msg = JText::_( 'Project(s) added succesfully!' );
		} else {
			$msg = JText::_( 'Error adding project(s)' );
		}
		$this->setRedirect('index.php?option=com_webfonts&controller=webfontsproject', $msg);
	}
	
	
	/**
	 * remove record(s)
	 * @return void
	 */
	function remove()
	{
		
		$model = $this->getModel('webfontsproject');
		if(!$model->delete()) {
			$msg = JText::_( 'Error: One or More projects Could not be Deleted' );
		} else {
			$msg = JText::_( 'Project(s) Deleted' );
		}
		$this->setRedirect( 'index.php?option=com_webfonts&controller=webfontsproject', $msg );
	}
	
	/*
	* Activate record(s)
	* @return void
	*/
	function publish()
	{
		$model = $this->getModel('webfontsproject');
		if(!$model->activate()) {
			$msg = JText::_( 'Error: One or More projects Could not be Activated' );
		} else {
			$msg = JText::_( 'Project(s) Activated' );
		}
		
		$this->setRedirect( 'index.php?option=com_webfonts&controller=webfontsproject', $msg );
	}
	
	/*
	* Deactivate record(s)
	* @return void
	*/
	function unpublish()
	{
		$model = $this->getModel('webfontsproject');
		if(!$model->deactivate()) {
			$msg = JText::_( 'Error: One or More projects Could not be Deactivated' );
		} else {
			$msg = JText::_( 'Project(s) Deactivated' );
		}
		
		$this->setRedirect( 'index.php?option=com_webfonts&controller=webfontsproject', $msg );
	}
	
	/**
	 * Sync a record (and redirect to main page)
	 * @return void
	 */
	function sync()
	{
		$model = $this->getModel('webfontsproject');
		
		if ($model->syncProject($post)) {
			$msg = JText::_( 'Project(s) synchronized succesfully!' );
		} else {
			$msg = JText::_( 'Error synchronizing project(s)' );
		}
		$this->setRedirect('index.php?option=com_webfonts&controller=webfontsproject', $msg);
	}

	/*
	*Project listing record(s) from ajax 
	* @return project list
	*/
	function project_list_ajax(){
		
		$output="";
		$model = $this->getModel('webfontsproject');
		$pageStart = (isset($_GET['pageStart']))?$_GET['pageStart']:0;
		$wfs_details = getUnPass();
		//Fetching the xml data from WFS
		$apiurl = "xml/Projects/?wfspstart=".$pageStart."&wfsplimit=".PROJECT_LIMIT;
		$wfs_api = new Services_WFS($wfs_details[1],$wfs_details[2],$apiurl);
		$xmlUrl = $wfs_api->wfs_getInfo_post();
		//creating a DOM object
			$doc = new DOMDocument();
			$doc->loadXML($xmlUrl);
			$cnt=1;
			$messages = $doc->getElementsByTagName( "Message" );
			$message = $messages->item(0)->nodeValue;
			if($message == "Success"){			
				//fetching XML data
				$projects = $doc->getElementsByTagName( "Project" );
				foreach( $projects as $project )
				{
					$projectNames = $project->getElementsByTagName("ProjectName");	
					$projectName = $projectNames->item(0)->nodeValue;
					
					$projectKeys = $project->getElementsByTagName("ProjectKey");	
					$projectKey = $projectKeys->item(0)->nodeValue;
					$wfs_projects =$model->getProjectProfile($projectKey, "project_key");
					$sn =$cnt+$pageStart;
					if(empty($wfs_projects[0]->project_key)){
						$output.='<tr><td>'.$sn.'</td><td width="20"><input type="checkbox" class="imp_prj_checkboxes" name="project_key['.$projectKey.']" id="'.$projectKey.'" value="'.$projectKey.'"/></td><td style="text-align:left"> '.$projectName.'<input type="hidden" name="project_name['.$projectKey.']" value="'.$projectName.'" /></td></tr>';
						$cnt++;
					}else{
							$output.='<tr><td>'.$sn.'</td><td width="20"><input type="checkbox" disabled="disabled" name="project_key['.$projectKey.']" id="'.$projectKey.'" value="'.$projectKey.'"/></td><td> '.$projectName.' <i style="color:#0b55c4">(Project already added.</i>)<input type="hidden" name="project_name['.$projectKey.']" value="'.$projectName.'" /></td></tr>';
						$cnt++;
						}
				}//end of foreach
				if($cnt == 1)
					{
						$output.="<tr><td colspan='2'>No project available.</td></tr>";
					}
			
			//Pagination data
			$projects = $doc->getElementsByTagName( "Projects" );
			foreach( $projects as $project )
			{
				$totalrecords = $doc->getElementsByTagName( "TotalRecords" );
				$totalRecordxml =$totalrecords->item(0)->nodeValue;
				
				$pagestarts = $doc->getElementsByTagName( "PageStart" );
				$pageStart =$pagestarts->item(0)->nodeValue;
				
				$pagelimits = $doc->getElementsByTagName( "PageLimit" );
				$pageLimitxml =$pagelimits->item(0)->nodeValue;
			}
			}//enf of if for success
			else{
				$output.='<tr><td colspan="2">'.$message.'</td></tr>';
				}
	
		
		$pageLimit = $pageLimitxml;
		$totalRecord = $totalRecordxml ;
		if($totalRecord!=0 && $pageLimit!="" && $cnt!=1){
			$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,'imp_project_div','project_pagination_div',"index.php?option=com_webfonts&controller=webfontsproject&task=project_list_ajax");
			$wfs_pg_projects =  $wfs_pg->getPagination();
		}
		$value = array('data'=>$output, 'pagination' => $wfs_pg_projects);
		$json = new Services_JSON();
		$result = $json->encode($value);
		echo $result;
		exit;
		}
		
		/*
		* function to get the current key
		*/
		function get_activated_key(){
			
		$params = &JComponentHelper::getParams('com_webfonts');
		$db =& JFactory::getDBO();
		$wfs_userid = $params->get( 'wfs_user_id' );
		
		$wfs_project_id = $_GET['pid'];
		if(!empty($wfs_project_id)){
		$query = "SELECT project_key,wfs_configure_id FROM #__wfs_configure WHERE  `wfs_configure_id` = '$wfs_project_id' ORDER BY updated_date DESC";
		$db->setQuery( $query);
		$rows = $db->loadObjectList();
		$key=$rows[0]->project_key;
			}
		else{
		$query = "SELECT project_key,project_page_option,project_options,project_pages,project_day,wysiwyg_enabled,editor_select FROM #__wfs_configure WHERE `is_active` = '1' and `user_id` = '$wfs_userid' and wysiwyg_enabled = '1' ORDER BY updated_date DESC";
		$db->setQuery( $query);
		$rows = $db->loadObjectList();
	
			foreach($rows as $data)
				{
				$dayValue = $data->project_day;
				if(checkday($dayValue)){
					
						$key=$data->project_key;
						break;
					}	
			}
		}
		
		return $key;
		}
		
		/*
		* function to generate css for ckeditor
		*/
		function font_stylesheet_ckeditor()
		{
			header("content-type: text/css");	
			$wfs_details=getUnPass();
			$key = $this->get_activated_key();
			$browser = browserName();
		
			$apiurl = "xml/Fonts/?wfspid=".$key;
			$wfs_api = new Services_WFS($wfs_details[1],$wfs_details[2],$apiurl);
			$xmlMsg = $wfs_api->wfs_getInfo_post();
			$fontdata = new DOMDocument();
			$fontdata->loadXML($xmlMsg);
			$fonts = $fontdata->getElementsByTagName( "Font" );
			$webfonts=array();
			$fontsList="";
			$stylesheetcss="";
			foreach($fonts as $font){
				
				$FontNames = $font->getElementsByTagName("FontName");
				$FontName= $FontNames->item(0)->nodeValue;
				
				$FontCSSNames = $font->getElementsByTagName("FontCSSName");
				$FontCSSName= $FontCSSNames->item(0)->nodeValue;
				
				$CDNKeys = $font->getElementsByTagName("CDNKey");
				$CDNKey= $CDNKeys->item(0)->nodeValue;
				if($browser =="Internet Explorer (MSIE/Compatible)")
				{
				$TTFs = $font->getElementsByTagName("EOT");
				$TTF= $TTFs->item(0)->nodeValue;
				$ext=".eot";
				}else{
				$TTFs = $font->getElementsByTagName("TTF");
				$TTF= $TTFs->item(0)->nodeValue;
				$ext=".ttf";
				}
				
				//$fontsList.= "\"".$FontName."/'".$FontCSSName."';\" + ";
				$stylesheetcss.="@font-face{font-family:'".$FontCSSName."';src:url('http://fast.fonts.com/d/".$TTF.$ext."?".$CDNKey."&projectId=".$key."');}";
					}
				print $stylesheetcss;
				exit;
				
			}


}