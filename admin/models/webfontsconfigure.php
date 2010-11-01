<?php
/**
 * Copyright 2010 Monotype Imaging Inc.  
 * This program is distributed under the terms of the GNU General Public License
 */
 
/**
 * Webfontsc Model for fonts.com webfonts Component
 * 
 * @Components    Fonts.com Webfonts
 * components/com_webfonts/webfonts.php
 * @license    GPL
 */
 
// No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.model');

class WebfontsBaseModelWebfontsConfigure extends JModel
{
	/**
	 * Constructor that retrieves the ID from the request
	 *
	 * @access	public
	 * @return	void
	 */
	function __construct()
	{
		parent::__construct();

		$array = JRequest::getVar('cid',  0, '', 'array');
		$this->setId((int)$array[0]);
		$this->db 	=& JFactory::getDBO();
	}

	/**
	 * Method to set the hello identifier
	 *
	 * @access	public
	 * @param	int Hello identifier
	 * @return	void
	 */
	function setId($id)
	{
		// Set id and wipe data
		$this->_id		= $id;
		$this->_data	= null;
	}

	/**
	 * Method to get a hello
	 * @return object with data
	 */
	function &getData()
	{
		// Load the data
		if (empty( $this->_data )) {
			$query = ' SELECT * FROM #__wfs_configure '.
					'  WHERE wfs_configure_id = '.$this->_id;
			$this->_db->setQuery( $query );
			$this->_data = $this->_db->loadObject();
		}
		if (!$this->_data) {
			$this->_data = new stdClass();
			$this->_data->id = 0;
			$this->_data->webfonts = null;
		}
		return $this->_data;
	}

	/**
	 * Method to store a record
	 *
	 * @access	public
	 * @return	boolean	True on success
	 */
	function store_configure()
	{	
		
		$db = JFactory::getDBO();
		$data = JRequest::get( 'post' );	
		/*Storing data for project configuration*/
		$row =& $this->getTable('webfonts');
		$row->wfs_configure_id = JRequest::getvar( 'project_id' );
		$row->project_options = JRequest::getvar( 'source_selection' );
		$row->wysiwyg_enabled = JRequest::getvar( 'enable_editor' );
		$row->project_day = JRequest::getvar( 'days' );
		$row->project_page_option = JRequest::getvar( 'page_visiblity' );
		$row->editor_select = JRequest::getvar( 'editor_selection' );
		
		/****
		Changes: Added show_system_fonts
		- By: Keshant
		****/
		$row->show_system_fonts = JRequest::getvar( 'system_fonts' );
		/**** End ***/
		
		$query = 'Update `#__wfs_configure`  SET `wysiwyg_enabled` = "0" ';
		$db->setQuery( $query );
				if(!$db->query())
					{
					 return false;	
					}
		
		// Bind the form fields to the hello table
		if (!$row->bind($data)) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
		
		// Make sure the hello record is valid
		if (!$row->check()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		// Store the web link table to the database
		if (!$row->store()) {
			$this->setError( $row->getErrorMsg() );
			return false;
		}
		
		/*end */
		/*storing data for project visibility*/
		$row_menu =& $this->getTable('webfontsmenu');
		$pages=array();
		$pages = JRequest::getvar( 'selections' );
		$moduleid =$this->getModuleId();
		$query = "DELETE FROM `#__modules_menu` WHERE `moduleid` = '".$moduleid."'";
		$db->setQuery( $query );
		if(!$db->query())
			{
			 return false;	
			}
		if(empty($pages)){
			$page = 0;
			$query = "INSERT INTO `#__modules_menu` (`moduleid`,`menuid`) VALUES (".$moduleid.",".$page.")";
				$db->setQuery( $query );
				if(!$db->query())
					{
					 return false;	
					}
			}else{
				foreach($pages as $page){
				$query = "INSERT INTO `#__modules_menu` (`moduleid`,`menuid`) VALUES (".$moduleid.",".$page.")";
				$db->setQuery( $query );
				if(!$db->query())
					{
					 return false;	
					}
				}
		}
		/*end*/
		return true;
	}
	
	/**
	 * Method to get the fonts of a particular project
	 * @return array with data
	 */
	function &getFonts($project_key = null)
	{
		if($project_key == null){		
			$project_key = $this->_data->project_key;
		}
		$wfs_details = getUnPass();
		
		//fetch xml data from WFS
		$apiurl = "xml/Fonts/?wfspstart=0&wfsplimit=".FONT_LIMIT."&wfspid=".$project_key;
		$wfs_api = new Services_WFS($wfs_details[1],$wfs_details[2],$apiurl);
		$xmlMsg = $wfs_api->wfs_getInfo_post();
		
		$xmlDomObj = new DOMDocument();
		$xmlDomObj->loadXML($xmlMsg);
		$fonts = $xmlDomObj->getElementsByTagName( "Font" );
		$webfonts=array();
		$TotalRecords = $xmlDomObj->getElementsByTagName( "TotalRecords" );
		$webfonts['TotalRecords']= $TotalRecords->item(0)->nodeValue;
		
		$PageLimit = $xmlDomObj->getElementsByTagName( "PageLimit" );
		$webfonts['PageLimit']= $PageLimit->item(0)->nodeValue;
		
		$PageStart = $xmlDomObj->getElementsByTagName( "PageStart" );
		$webfonts['PageStart']= $PageStart->item(0)->nodeValue;
		foreach($fonts as $font){
			$fontids = $font->getElementsByTagName("FontID");
			$webfonts['fontid'][]= $fontids->item(0)->nodeValue;
			
			$FontNames = $font->getElementsByTagName("FontName");
			$webfonts['FontName'][]= $FontNames->item(0)->nodeValue;
			
			$FontPreviewTextLongs = $font->getElementsByTagName("FontPreviewTextLong");
			$webfonts['FontPreviewTextLong'][]= $FontPreviewTextLongs->item(0)->nodeValue;
			
			$FontFondryNames = $font->getElementsByTagName("FontFondryName");
			$webfonts['FontFondryName'][]= $FontFondryNames->item(0)->nodeValue;
			
			$FontCSSNames = $font->getElementsByTagName("FontCSSName");
			$webfonts['FontCSSName'][]= $FontCSSNames->item(0)->nodeValue;
			
			$FontLanguages = $font->getElementsByTagName("FontLanguage");
			$webfonts['FontLanguage'][]= $FontLanguages->item(0)->nodeValue;
			
			$FontSizes = $font->getElementsByTagName("FontSize");
			$webfonts['FontSize'][]= $FontSizes->item(0)->nodeValue;
			
			$EnableSubsettings = $font->getElementsByTagName("EnableSubsetting");
			$webfonts['EnableSubsetting'][]= $EnableSubsettings->item(0)->nodeValue; 
		}
		return $webfonts;	

	}
	
	/*
	* Fetch all the fonts given a project key from ajax call
	*/
	function wfs_font_list_pagination($pid=null){
		
		$pageStart = (!empty($_GET['pageStart']))?$_GET['pageStart']:0;

		$wfs_details = getUnPass();
		
		//fetch xml data from WFS
		$apiurl = "xml/Fonts/?wfspstart=".$pageStart."&wfsplimit=".FONT_LIMIT."&wfspid=".$_GET['pid'];
		$wfs_api = new Services_WFS($wfs_details[1],$wfs_details[2],$apiurl);
		$xmlMsg = $wfs_api->wfs_getInfo_post();
		//Creating JSON Instance
		
		$xmlDomObj = new DOMDocument();
		$xmlDomObj->loadXML($xmlMsg);
		$webfonts=array();
		$Messages = $xmlDomObj->getElementsByTagName( "Message" );
		$Message= $Messages->item(0)->nodeValue;
		if($Message == "Success"){
		//Pagination data
		$TotalRecords = $xmlDomObj->getElementsByTagName( "TotalRecords" );
		$totalRecord= $TotalRecords->item(0)->nodeValue;
		
		$PageLimit = $xmlDomObj->getElementsByTagName( "PageLimit" );
		$pageLimit= $PageLimit->item(0)->nodeValue;
		
		$PageStart = $xmlDomObj->getElementsByTagName( "PageStart" );
		$pageStart= $PageStart->item(0)->nodeValue;
		
		$fonts = $xmlDomObj->getElementsByTagName( "Font" );
		foreach($fonts as $font){
			
			$fontids = $font->getElementsByTagName("FontID");
			$webfonts['fontid'][]= $fontids->item(0)->nodeValue;
			
			$FontNames = $font->getElementsByTagName("FontName");
			$webfonts['FontName'][]= $FontNames->item(0)->nodeValue;
			
			$FontPreviewTextLongs = $font->getElementsByTagName("FontPreviewTextLong");
			$webfonts['FontPreviewTextLong'][]= $FontPreviewTextLongs->item(0)->nodeValue;
			
			$FontFondryNames = $font->getElementsByTagName("FontFondryName");
			$webfonts['FontFondryName'][]= $FontFondryNames->item(0)->nodeValue;
			
			$FontCSSNames = $font->getElementsByTagName("FontCSSName");
			$webfonts['FontCSSName'][]= $FontCSSNames->item(0)->nodeValue;
			
			$FontLanguages = $font->getElementsByTagName("FontLanguage");
			$webfonts['FontLanguage'][]= $FontLanguages->item(0)->nodeValue;
			
			$FontSizes = $font->getElementsByTagName("FontSize");
			$webfonts['FontSize'][]= $FontSizes->item(0)->nodeValue;
			
			$EnableSubsettings = $font->getElementsByTagName("EnableSubsetting");
			$webfonts['EnableSubsetting'][]= $EnableSubsettings->item(0)->nodeValue; 
		
			 
		}
		$output="";
		$editorArray =$this->getFontsEditor($pid);
		$editorFontNameArr = $editorArray[0];
		$editorFontNameArrStatus = $editorArray[1];
		for($i=0;$i< count($webfonts["FontName"]);$i++){
				$checkedFront = "";
				$checkedBack  = "";
				if(in_array($webfonts["FontName"][$i].'='.$webfonts["FontCSSName"][$i].';',$editorFontNameArr))
				{
					$keyFontData =  array_search($webfonts["FontName"][$i].'='.$webfonts["FontCSSName"][$i].';',$editorFontNameArr);
					if($editorFontNameArrStatus[$keyFontData] == 2)
					{
						$checkedFront = 'checked = "checked"';
						$checkedBack = 'checked = "checked"';
					}
					else if($editorFontNameArrStatus[$keyFontData] == 1){
						$checkedFront = 'checked = "checked"';
					}
					else if($editorFontNameArrStatus[$keyFontData] == 0){
						$checkedBack = 'checked = "checked"';
						}
				}
			
			$output.= '<tr class="row'.$i.'"><td>
						<div class="font_sep '.$class.'">
						<span class="font_img" style="font-family:\''.$webfonts["FontCSSName"][$i].'\' !important;font-size:30px;">'.$webfonts["FontPreviewTextLong"][$i].'</span>
						<div class="fontnames"><u>'.$webfonts["FontName"][$i].'</u> | <u>'.$webfonts["FontFondryName"][$i].'</u> | <u>'.$webfonts["FontLanguage"][$i].'</u>'.$webfonts["FontSize"][$i].'
						</div>
						</div></td>
						<td><input type="checkbox" name="frontend['.$i.']" value="1" '.$checkedFront .'></td>
						 <td><input type="checkbox" name="backend['.$i.']" value="1" '.$checkedBack .'><input  type="hidden"  name="fontlist['.$i.']" value="'.$webfonts["FontName"][$i].'--'.$webfonts["FontCSSName"][$i].';"></td></tr>';
		
				}
		
		if($pageLimit!="" && $totalRecord!=0 && count($webfonts["FontName"])!=0){
				$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,'wfs_fonts_div', 'fonts_pagination_div',"index.php?option=com_webfonts&controller=webfontsconfigure&task=fonts_list_ajax");
				$pagination = $wfs_pg->getPagination();
			}
			
		}else{
			$output = $Message;
			}
	
	//	return	$xmlMsg;
	
	return array('data' => $output,'pagination'=>$pagination);
	
	}
	
	function &getSelectorsList($project_key = null){
		if($project_key == null){		
			$project_key = $this->_data->project_key;
		}
		$output="";
		$wfs_details = getUnPass();
		$pageStart = (!empty($_GET['pageStart']))?$_GET['pageStart']:0;
	
		//fetching xml data from WFS
		$apiurl = "xml/Selectors/?wfspstart=".$pageStart."&wfsplimit=".SELECTOR_LIMIT."&wfspid=".$project_key;
		$wfs_api = new Services_WFS($wfs_details[1],$wfs_details[2],$apiurl);
		$xmlUrl = $wfs_api->wfs_getInfo_post();
		
		//creating a DOM object
		$doc = new DOMDocument();
		$doc->loadXML($xmlUrl);
		//fetching XML data
		$messages = $doc->getElementsByTagName( "Message" );
		$message = $messages->item(0)->nodeValue;
		$wfsSelectorTag=array();
		$count = 1;
		if($message == "Success"){
			$selectors = $doc->getElementsByTagName( "Selector" );
			foreach( $selectors as $selector ){
				$SelectorTags = $selector->getElementsByTagName("SelectorTag");	
				$SelectorTag = $SelectorTags->item(0)->nodeValue;
				$wfsSelectorTag[]=$SelectorTag;//array for list of selectors						
				$SelectorIDs = $selector->getElementsByTagName("SelectorID");	
				$SelectorID = $SelectorIDs->item(0)->nodeValue;
				
				$SelectorFontIDs = $selector->getElementsByTagName("SelectorFontID");	
				$SelectorFontID = $SelectorFontIDs->item(0)->nodeValue;
				$fontsArr=array();
				$fontsArr = $this->wfs_font_list($project_key,$SelectorFontID,$count);
				$sn =$count+$pageStart;
				$output.='<tr >
					<td>'.$sn.'</td>
					<td><strong>'.$SelectorTag.'</strong></td>
					<td>'.$fontsArr[0].'</td>
					<td><div style="width:650px;overflow-x:hidden"><span class="wfs_selectors" style="font-size:26px;font-family:'.$fontsArr[3].' !important;" id="fontid_'.$count.'">'.$fontsArr[1].'</span></div></td>
					<td><a href="javascript:;" onclick="submitbuttonDelete(\''.$SelectorID.'\')" >Remove</a><input type="hidden" id="selector_'.$count.'" name="selector_'.$count.'" value="'.$SelectorID.'" />
					</td>
				</tr>';
				$count++;
			}//end of foreach
			if($count == 1){
				$output.='<td colspan="5" style="text-align:center">No selector available.</td>';
				}
			$totalrecords = $doc->getElementsByTagName( "TotalRecords" );
			$totalRecord =$totalrecords->item(0)->nodeValue;
			
			$pagestarts = $doc->getElementsByTagName( "PageStart" );
			$pageStart =$pagestarts->item(0)->nodeValue;
			
			$pagelimits = $doc->getElementsByTagName( "PageLimit" );
			$pageLimit =$pagelimits->item(0)->nodeValue;

		}else{ //else for not success
			$output.='<td colspan="5" style="text-align:center">'.$message.'. Please reload the page.</td>';
		}
		 if($totalRecord !=0 && $pageLimit!="" && $count != 1){
		$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,'wfs_selectors_div','selectors_pagination_div',"index.php?option=com_webfonts&controller=webfontsconfigure&task=selector_list_ajax");
		$pagination = $wfs_pg->getPagination(); 
		}
		return array($output,$pagination,$wfsSelectorTag); 				
		}
	/**
	 * Method to get the Font information
	 * @return array with data
	 */
	 function wfs_font_list($project_key,$defaultFont="null",$count){
		$wfs_details = getUnPass();
		$result = array();
		$options ='<select id="fonts-list@'.$count.'" class="fonts-list" name="font_list[]" style="width:200px">';  
		$options.= '<option value="-1" >- - - - - Please select a font- - - - --</option>';  
		// XML feed file/URL 
				
		$apiurl = "xml/Fonts/?wfspid=".$project_key;
		$wfs_api = new Services_WFS($wfs_details[1],$wfs_details[2],$apiurl);
		$xmlUrl = $wfs_api->wfs_getInfo_post();
		
		//creating a DOM object
		$doc = new DOMDocument();
		$doc->loadXML($xmlUrl);
		
		//fetching XML data  
		$fonts = $doc->getElementsByTagName( "Font" );
		
		foreach( $fonts as $font )
		{
		$FontNames = $font->getElementsByTagName("FontName");	 
		$FontName = $FontNames->item(0)->nodeValue; 
		
		$FontCSSNames = $font->getElementsByTagName("FontCSSName");	 
		$FontCSSName = $FontCSSNames->item(0)->nodeValue; 
		
		$FontIDs = $font->getElementsByTagName("FontID");	 
		$FontID = $FontIDs->item(0)->nodeValue; 
			
		$FontPreviewTextLongs = $font->getElementsByTagName("FontPreviewTextLong");	 
		$FontPreviewTextLong = $FontPreviewTextLongs->item(0)->nodeValue; 
		
		$selected =($defaultFont == $FontID)?"Selected":"";
		if($defaultFont == $FontID){
			$fontCssName=$FontCSSName;
			$fontPreviewTextLong = $FontPreviewTextLong;
		}
		
		$options.= '<option value="'.$FontCSSName.'@!'.$FontPreviewTextLong.'@!'.$FontID.'" '.$selected.' >'.$FontName.'</option>'; 
		
			 
		}
		$options.= '</select>';	
		array_push($result,$options);
		array_push($result,$fontPreviewTextLong);
		array_push($result,$FontName);
		array_push($result,$fontCssName);
		
		return $result;

		 }
		 
	/**
	* Method for fetching the font for that project 
	* according to config
	*/
	
	function &getFontsEditor($pid = null){
		//$array = JRequest::getVar('cid',  0, '', 'array');
		//$project_key = (int)$array[0]);
		if(!empty($pid))
			{
			$pid = $pid;	
			}
		else{
			$pid = $this->_id;
			}
		$query = "SELECT * FROM `#__wfs_editor_fonts` WHERE `pid` = '$pid' and is_active = '1'";
		$fontsArr = array();
		$fontsArrStatus = array();
		 $rs = $this->_getList( $query );
		if ($rs) {
			foreach ($rs as $key=>$data ) {
			$fontsArr[] = $data->tinymce_name;
			$fontsArrStatus[] = $data->is_admin;
			}
		}
		return array($fontsArr,$fontsArrStatus);
		}
	
	/**
	 * Method to save the editor fonts
	 * @return bool
	 */
	 function save_editor_fonts(){
		$fontInPage = JRequest::getVar('fontlist');
		$frontEndFonts = JRequest::getVar('frontend');
		$backEndFonts = JRequest::getVar('backend');
		$pid = JRequest::getVar('project_id');
		$editorFontArray = array();
				foreach($fontInPage  as $key => $fontName)
			{
				if($frontEndFonts[$key]==1 && $backEndFonts[$key]==1)
				{
					$editorFontArray['FontName'][]=$fontName;
					$editorFontArray['isFront'][]=2;
					$editorFontArray['isStatus'][]=1;
				}
				else if($frontEndFonts[$key]==1 && $backEndFonts[$key]==0)
				{
					$editorFontArray['FontName'][]=$fontName;
					$editorFontArray['isFront'][]=1;
					$editorFontArray['isStatus'][]=1;
				}
					else if($frontEndFonts[$key]==0 && $backEndFonts[$key]==1)
				{
					$editorFontArray['FontName'][]=$fontName;
					$editorFontArray['isFront'][]=0;
					$editorFontArray['isStatus'][]=1;
				}
				else{
					$editorFontArray['FontName'][]=$fontName;
					$editorFontArray['isFront'][]=0;
					$editorFontArray['isStatus'][]=0;
					}
			}
			
				if(!empty($editorFontArray)){
				$db = JFactory::getDBO();
				foreach($editorFontArray['FontName'] as $keyEditor => $editorFont){
					$fontArr = explode("--",$editorFont);
					$tinymce = mysql_escape_string($fontArr[0].'='.$fontArr[1]);
					$ckeditor = mysql_escape_string($fontArr[0].'/'.$fontArr[1]);
					$query = "SELECT * FROM `#__wfs_editor_fonts` WHERE `tinymce_name` = '$tinymce' and `ckeditor_name` = '$ckeditor' and pid = '$pid'";
					$rs = $this->_getList( $query );
					
					
					if($rs)
					{
						
						echo $query = "UPDATE `#__wfs_editor_fonts` SET tinymce_name = '$tinymce', ckeditor_name = '$ckeditor', is_admin = '".$editorFontArray['isFront'][$keyEditor]."', is_active = '".$editorFontArray['isStatus'][$keyEditor]."', pid = '$pid' WHERE wfs_font_id = '".$rs[0]->wfs_font_id."'";
						$db->setQuery( $query );
						if(!$db->query())
						{
							 return false;	
						}
					}
					else{
						 $query = "INSERT INTO `#__wfs_editor_fonts` (tinymce_name,ckeditor_name,is_admin,pid,is_active) VALUES('$tinymce','$ckeditor','".$editorFontArray['isFront'][$keyEditor]."','$pid','".$editorFontArray['isStatus'][$keyEditor]."')";
						$db->setQuery( $query );
						if(!$db->query())
							{
							 return false;	
							}
						}
				}
			}
	
		 return true;	
	}
	
	/**
	 * Method to save the selector form wfs porject
	 * @return bool
	 */
	 function add_selector(){
		$output="";
		$wfs_details = getUnPass();
		$project_key = JRequest::getvar( 'project_key' );
		$selector_name = JRequest::getvar( 'selectorname' );
		
		//Fetching the xml data from WFS
		$apiurl = "xml/Selectors/?wfspstart=0&wfsplimit=".SELECTOR_LIMIT."&wfspid=".$project_key;
		$wfs_api = new Services_WFS($wfs_details[1],$wfs_details[2],$apiurl);
		$xmlUrl = $wfs_api->addSelector($selector_name);
		//creating a DOM object
		$doc = new DOMDocument();
		$doc->loadXML($xmlUrl);
		//fetching XML data
		$messages = $doc->getElementsByTagName( "Message" );
		$message = $messages->item(0)->nodeValue;
		$wfsSelectorTag=array();
		$count = 1;
		if($message == "Success"){
			$selectors = $doc->getElementsByTagName( "Selector" );
			foreach( $selectors as $selector ){
				$SelectorTags = $selector->getElementsByTagName("SelectorTag");	
				$SelectorTag = $SelectorTags->item(0)->nodeValue;
				$wfsSelectorTag[]=$SelectorTag;//array for list of selectors						
				$SelectorIDs = $selector->getElementsByTagName("SelectorID");	
				$SelectorID = $SelectorIDs->item(0)->nodeValue;
				
				$SelectorFontIDs = $selector->getElementsByTagName("SelectorFontID");	
				$SelectorFontID = $SelectorFontIDs->item(0)->nodeValue;
				$fontsArr=array();
				$fontsArr = $this->wfs_font_list($project_key,$SelectorFontID,$count);
				$sn =$count+$pageStart;
				$output.='<tr >
					<td>'.$sn.'</td>
					<td><strong>'.$SelectorTag.'</strong></td>
					<td>'.$fontsArr[0].'</td>
					<td><div style="width:650px;overflow-x:hidden"><span class="wfs_selectors" style="font-size:26px;font-family:'.$fontsArr[3].' !important;" id="fontid_'.$count.'">'.$fontsArr[1].'</span></div></td>
					<td><a href="javascript:;" onclick="submitbuttonDelete(\''.$SelectorID.'\')" >Remove</a><input type="hidden" id="selector_'.$count.'" name="selector_'.$count.'" value="'.$SelectorID.'" />
					</td>
				</tr>';
				$count++;
			}//end of foreach
			$totalrecords = $doc->getElementsByTagName( "TotalRecords" );
			$totalRecord =$totalrecords->item(0)->nodeValue;
			
			$pagestarts = $doc->getElementsByTagName( "PageStart" );
			$pageStart =$pagestarts->item(0)->nodeValue;
			
			$pagelimits = $doc->getElementsByTagName( "PageLimit" );
			$pageLimit =$pagelimits->item(0)->nodeValue;

		}else{ //else for not success
			if($message == 'DuplicateSelectorName'){
				$output.= $message;
			}else{
				$output.='<td colspan="5" style="text-align:center">'.$message.'. Please reload the page.</td>';
			}
		}
		 if($totalRecord !=0 && $pageLimit!="" && $count != 1){
		$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,'wfs_selectors_div','selectors_pagination_div',"index.php?option=com_webfonts&controller=webfontsconfigure&task=selector_list_ajax");
		$pagination = $wfs_pg->getPagination(); 
		}
		return array($output,$pagination); 				
		 
	}
	
	/**
	 * Method to save the selector form wfs porject
	 * @return bool
	 */
	 function save_selector(){
		$wfs_details = getUnPass();
		$project_key = JRequest::getvar( 'project_key' );
		$fontIdList = array();
		$selectorIdList = array();
		foreach($_POST['font_list'] as $key => $fontname)
			{
			$fontidarr = explode("@!",$fontname);
			if($fontidarr[2] != ''){
				array_push($fontIdList,$fontidarr[2]);
			}else{
				array_push($fontIdList,'-1');
			} 
				$cnt = $key + 1;
				$selctor_id = $_POST['selector_'.$cnt];
				array_push($selectorIdList,$selctor_id);
			}
		$fontids = implode(",",$fontIdList);
		$selectorsids = implode(",",$selectorIdList);
		
		//Fetching the xml data from WFS
		$apiurl = "xml/Selectors/?wfspid=".$project_key;
		$wfs_api = new Services_WFS($wfs_details[1],$wfs_details[2],$apiurl);
		$xmlUrl = $wfs_api->saveSelector($fontids,$selectorsids);
		//creating a DOM object
		$doc = new DOMDocument();
		$doc->loadXML($xmlUrl);
		$messages = $doc->getElementsByTagName( "Message" );
		$message = $messages->item(0)->nodeValue;
		if($message == "Success"){
			 return true;
			}else{
			return false;
				}
		}
		
	/**
	 * Method to remove the selector form wfs porject
	 * @return bool
	 */
	 function remove_selector(){
		$project_key = JRequest::getvar( 'project_key' );
		$selector_delete = JRequest::getvar( 'selector_delete' );
		$wfs_details = getUnPass();
	
		//Fetching the json data from WFS
		$apiurl = "xml/Selectors/?wfspid=".$project_key."&wfsselector_id=".urlencode($selector_delete);
		$wfs_api = new Services_WFS($wfs_details[1],$wfs_details[2],$apiurl);
		$xmlUrl = $wfs_api->deleteSelector();
		//creating a DOM object
		$doc = new DOMDocument();
		$doc->loadXML($xmlUrl);
		$messages = $doc->getElementsByTagName( "Message" );
		$message = $messages->item(0)->nodeValue;
		if($message == "Success"){
			 return true;
			}else{
			return false;
				}
		}
		
	/**
	 * Method to get the joomla existing selectors
	 * @return array with data
	*/
	function &getJoomlaSelectorsList(){
		$template = $this->getCurrentTemplate();
		$baseurl = JPATH_SITE.'/templates/'.$template;
		$url = opendir($baseurl.'/css'); 
		$wfs_selector = '';
		while(false !== ($file = readdir($url))){
				$ext = substr($file, strrpos($file, '.') + 1);
				if($ext == 'css'){ // check for css file
					//get all existing selectors
					$style = $baseurl.'/css/'.$file;
					$style_arr = getFileContent($style);
					if($wfs_selector == ''){
						$wfs_selector = array_unique($style_arr);
					} else {
						$wfs_selector = array_unique (array_merge( $wfs_selector, array_unique($style_arr)));
					}		
				}
				
		}
		return $wfs_selector;
	}	
	
	/*
	* Function to get the current template of the site
	* return string template name
	*/
	function getCurrentTemplate() {
		
		$db 	=& JFactory::getDBO();
		
		$query = 'SELECT template'
				. ' FROM #__templates_menu'
				. ' WHERE client_id = 0 AND (menuid = 0)'
				. ' ORDER BY menuid DESC'
				;
				
		$db->setQuery( $query );
		$data = $db->loadObject();
		
		return $data->template;
		
	}
		
	/*
	* Fetch all the domains given a project key from ajax call
	*/
	function &getDomains($project_key = null){
		if($project_key == null){		
			$project_key = $this->_data->project_key;
		}
		$array = JRequest::getVar('cid',  0, '', 'array');
		$pid = $array[0];
		$pageStart = (!empty($_GET['pageStart']))?$_GET['pageStart']:0;
		$wfs_details = getUnPass();
	
		//Fetching the xml data from WFS
		$apiurl = "xml/Domains/?wfspstart=".$pageStart."&wfsplimit=".DOMAIN_LIMIT."&wfspid=".$project_key;
		$wfs_api = new Services_WFS($wfs_details[1],$wfs_details[2],$apiurl);
		$xmlMsg = $wfs_api->wfs_getInfo_post();
		$xmlDomObj = new DOMDocument();
		$xmlDomObj->loadXML($xmlMsg);
		$webdomains = array();
		$Messages = $xmlDomObj->getElementsByTagName( "Message" );
		$Message= $Messages->item(0)->nodeValue;
		if($Message == "Success"){
		//Pagination data
			$TotalRecords = $xmlDomObj->getElementsByTagName( "TotalRecords" );
			$totalRecord= $TotalRecords->item(0)->nodeValue;
			$PageLimit = $xmlDomObj->getElementsByTagName( "PageLimit" );
			$pageLimit= $PageLimit->item(0)->nodeValue;
			$PageStart = $xmlDomObj->getElementsByTagName( "PageStart" );
			$pageStart= $PageStart->item(0)->nodeValue;
			$domains = $xmlDomObj->getElementsByTagName( "Domain" );
			$output="";
			$count = 1;
			foreach( $domains as $domain )
			{
				$domainNames = $domain->getElementsByTagName("DomainName");	
				$domainName = $domainNames->item(0)->nodeValue;
				$domainIDs = $domain->getElementsByTagName("DomainID");	
				$domainID = $domainIDs->item(0)->nodeValue;
				$sn =$count+$pageStart;				
				$output.=	'<tr><td>'.$sn.'</td><td style="text-align:left">'.$domainName.'<input type="hidden" name="'.$domainName.'" value="'.$domainName.'" /></td><td><a href="index.php?option=com_webfonts&controller=webfontsconfigure&task=edit_domain_form&did='.$domainID.'&dname='.$domainName.'&pkey='.$project_key.'&pid='.$pid.'" >Edit</a>&nbsp;|&nbsp;<a href="javascript:;" onclick="submitbuttonDomainDelete(\''.$domainID.'\')" >Remove</a></td></tr>';				
				$count++;
			}
			if($count == 1)
			{
				$output.= "<tr><td colspan='3' style='text-align:center;'>No domain available.</td></tr>";
			}
			
			if($pageLimit!="" && $totalRecord!=0 && $count !=1){
				$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,'wfs_domains_div', 'domain_pagination_div',"index.php?option=com_webfonts&controller=webfontsconfigure&task=domain_list_ajax");
				$pagination = $wfs_pg->getPagination();
			}
			
		}else{
			$output = "<tr><td colspan='3' style='text-align:center;'>".$Message. "Please reload the page.</td></tr>";
		}
	return array($output,$pagination); 		
	}
	
	/*
	* Method to Add domain with ajax call
	*/
	function addDomain(){
		$wfs_details = getUnPass();
		$output = "";
		$pid = JRequest::getvar( 'project_id' );
		$project_key = JRequest::getvar('project_key');
		$domain_name = JRequest::getvar( 'domainname' );
		$pageStart = (!empty($_GET['pageStart']))?$_GET['pageStart']:0;$pageStart = 0;
		
		//Fetching the xml data from WFS
		$apiurl = "xml/Domains/?wfspstart=0&wfsplimit=".DOMAIN_LIMIT."&wfspid=".$project_key;
		$wfs_api = new Services_WFS($wfs_details[1],$wfs_details[2],$apiurl);
		$xmlUrl = $wfs_api->addDomain($domain_name);
		//creating a DOM object
		$xmlDomObj = new DOMDocument();
		$xmlDomObj->loadXML($xmlUrl);
		//fetching XML data
		$count = 1;
		$messages = $xmlDomObj->getElementsByTagName( "Message" );
		$message = $messages->item(0)->nodeValue;
		if($message == "Success"){
			//Pagination data
				$TotalRecords = $xmlDomObj->getElementsByTagName( "TotalRecords" );
				$totalRecord= $TotalRecords->item(0)->nodeValue;
				$PageLimit = $xmlDomObj->getElementsByTagName( "PageLimit" );
				$pageLimit= $PageLimit->item(0)->nodeValue;
				$PageStart = $xmlDomObj->getElementsByTagName( "PageStart" );
				$pageStart= $PageStart->item(0)->nodeValue;
				$domains = $xmlDomObj->getElementsByTagName( "Domain" );
				$output="";
				$count = 1;
				foreach( $domains as $domain )
				{
					$domainNames = $domain->getElementsByTagName("DomainName");	
					$domainName = $domainNames->item(0)->nodeValue;
					$domainIDs = $domain->getElementsByTagName("DomainID");	
					$domainID = $domainIDs->item(0)->nodeValue;
					$sn =$count+$pageStart;				
					$output.=	'<tr><td>'.$sn.'</td><td style="text-align:left">'.$domainName.'<input type="hidden" name="'.$domainName.'" value="'.$domainName.'" /></td><td><a href="index.php?option=com_webfonts&controller=webfontsconfigure&task=edit_domain_form&did='.$domainID.'&dname='.$domainName.'&pkey='.$project_key.'&pid='.$pid.'" >Edit</a>&nbsp;|&nbsp;<a href="javascript:;" onclick="submitbuttonDomainDelete(\''.$domainID.'\')" >Remove</a></td></tr>';						
					$count++;
				}
				if($count == 1)
				{
					$output.= "<tr><td colspan='2'>No domain available.</td></tr>";
				}
				
				if($pageLimit!="" && $totalRecord!=0 && $count !=1){
					$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,'wfs_domains_div', 'domain_pagination_div',"index.php?option=com_webfonts&controller=webfontsconfigure&task=domain_list_ajax");
					$pagination = $wfs_pg->getPagination();
				}
				
			}else{
				 if($message == 'DuplicateDomainName'){
					$output.= $message;
				}else{
					$output.='<td colspan="5" style="text-align:center">'.$message.'. Please reload the page.</td>';
				}
			}
	return array($output, $pagination);
	}
	/*
	* Edit domain
	*/
	function editDomain(){
		$wfs_details = getUnPass();
		$output = "";
		$project_key = $_POST['project_key'];
		$domain_name = $_POST['domainname'];
		$domain_id = $_POST['did'];
		
		//Fetching the xml data from WFS
		$apiurl = "xml/Domains/?wfspid=".$project_key.'&wfsdomain_id='.urlencode($domain_id);
		$wfs_api = new Services_WFS($wfs_details[1],$wfs_details[2],$apiurl);
		$xmlUrl = $wfs_api->editDomain($domain_name);

		//creating a DOM object
		$xmlDomObj = new DOMDocument();
		$xmlDomObj->loadXML($xmlUrl);
		//fetching XML data
		$count = 1;
		$messages = $xmlDomObj->getElementsByTagName( "Message" );
		$message = $messages->item(0)->nodeValue;
		if($message == "Success"){
			return true;
		}else{
			return false;
			}
	}
	/*
	* Domain removing function
	*/
	function removeDomain(){
		$wfs_details = getUnPass();
		$project_key = JRequest::getvar( 'project_key' );
		$domain_id = JRequest::getvar( 'domain_delete' );
		//Fetching the xml data from WFS
		$apiurl = "xml/Domains/?wfspid=".$project_key."&wfsdomain_id=".urlencode($domain_id);
		$wfs_api = new Services_WFS($wfs_details[1],$wfs_details[2],$apiurl);
		$xmlMsg = $wfs_api->deleteDomain();
		//creating a DOM object
		$doc = new DOMDocument();
		$doc->loadXML($xmlMsg);
		//fetching XML data
		$messages = $doc->getElementsByTagName( "Message" );
		$message = $messages->item(0)->nodeValue;
		if(strtolower($message)=="success"){
			return true;
		} else {
			return false;
		}
	}
	
	 /**
	 * Method to get the module id of component
	 * @return array with data
	 */
	function getModuleId()
	{
		$query = 'SELECT id AS moduleid'. ' FROM #__modules'. ' WHERE module = "mod_webfonts"';
		$this->db->setQuery( $query );
		$moduleidlookup = $this->db->loadObjectList();
		$module_id = $moduleidlookup[0]->moduleid;
		return $module_id;
	}
	/**
	 * Method to get the module menu assignment
	 * @return array with data
	 */
	
	function &getModuleMenuAssignent(){
		$module_id = $this->getModuleId();
		$query = 'SELECT menuid AS value'. ' FROM #__modules_menu'. ' WHERE moduleid = "'.(int) $module_id.'"';
		$this->db->setQuery( $query );
		$lookup = $this->db->loadObjectList();	
		return $lookup;
	}
	
	
}