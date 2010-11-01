<?php
/**
 * Copyright 2010 Monotype Imaging Inc.  
 * This program is distributed under the terms of the GNU General Public License
 */
 
/**
 * Webfonts template for fonts.com webfonts Component
 * 
 * @Components    Fonts.com Webfonts
 * components/com_webfonts/webfonts.php
 * @license    GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access'); ?>
<!-- Project listing table -->
<form action="index.php" method="post" name="adminForm">
<div id="editcell">
    <table class="adminlist">
    <thead>
        <tr>
          <th width="20">#</th>
          <th width="20">
    <input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->wfs_projects ); ?>);" />
		  </th>
          <th style="text-align:left">
                <?php echo JText::_( 'Project Name' ); ?>
           </th>
            <th width="60">
                <?php echo JText::_( 'Active' ); ?>
           </th>
        </tr>            
    </thead>
    <?php
    $k = 0;
	$n=count( $this->wfs_projects );
	$projectKeyArray = array();
	if($n!=0){
    for($i=0;  $i < $n; $i++)
    {
		$row =& $this->wfs_projects[$i];
		$checked    = JHTML::_( 'grid.id', $i, $row->wfs_configure_id );
		
		$link = JRoute::_( 'index.php?option=com_webfonts&controller=webfontsconfigure&task=edit&cid[]='. $row->wfs_configure_id );

        ?>
        <tr class="<?php echo "row$k"; ?>" style="text-align:center">
            <td><?php echo $i+1; ?></td>
            <td width="20">
               <?php echo $checked; ?> 
            </td>
          	<td style="text-align:left">
    		  <a href="<?php echo $link; ?>"><?php echo $row->project_name; ?></a>
			</td>

            <td>
              <?php $active_image = ($row->is_active == 1)?"tick.gif":"cross.gif"; ?>
              <?php echo  JHTML::_('image.site', $active_image, 'components/com_webfonts/assets/images/', NULL, NULL, 'thewebfonts.fonts.com'); ?>
            </td>
        </tr>
        <?php
        $k = 1 - $k;
		$projectKeyArray[] =  $row->project_key;
    	}//end of for loop
	}//end of if n!=0
	else{ ?>
		 <tr class="<?php echo "row$k"; ?>" style="text-align:center"><td colspan="4">No project available</td></tr> 
	<?php } ?>
     <tfoot>
    <tr>
      <td colspan="9"><?php echo $this->pagination_project->getListFooter(); ?></td>
    </tr>
  </tfoot>
    </table>
</div>
 
<input type="hidden" name="option" value="com_webfonts" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="controller" value="webfontsproject" />
<input type="hidden" name="user_id" value="<?php echo $this->user_id ?>" />
<div class="wfs_panel" style="margin-top:30px">
<h3 id="wfs_hidediv"><span class="left"><?php echo JText::_( 'Import Project(s)' ); ?></span><span class="arrowhead" style="display:none"></span>
    <div class="clear"></div></h3>
	<div class="wfs_hiddendiv"  style="display:none">
    <table cellspacing="1"  class="adminlist">
        <thead>
            <tr>
            <th width="20">#</th>
            <th  width="20"><input type="checkbox" id="imp_prj_main" /></th>
            <th style="text-align:left">Project List</th>
            </tr>
        </thead>
        <tbody id="imp_project_div">
    <?php 	$xmlUrl = $this->wfs_import_projects;
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
					$model = $this->getModel('webfontsproject');
					$wfs_projects =$model->getProjectProfile($projectKey, "project_key");
					if(empty($wfs_projects[0]->project_key)){
						echo '<tr><td>'.$cnt.'</td><td width="20"><input type="checkbox" class="imp_prj_checkboxes" name="project_key['.$projectKey.']" id="'.$projectKey.'" value="'.$projectKey.'"/></td><td style="text-align:left"> '.$projectName.'<input type="hidden" name="project_name['.$projectKey.']" value="'.$projectName.'" />';
						echo '</td></tr>';
						$cnt++;
					}else{
							echo '<tr><td>'.$cnt.'</td><td width="20"><input type="checkbox" disabled="disabled" name="project_key['.$projectKey.']" id="'.$projectKey.'" value="'.$projectKey.'"/></td><td> '.$projectName.' <i style="color:#0b55c4">(Project already added.</i>)<input type="hidden" name="project_name['.$projectKey.']" value="'.$projectName.'" />';
						echo '</td></tr>';
						$cnt++;
						}
				}//end of foreach
				if($cnt == 1)
					{
						echo "<tr><td colspan='2'>No project available.</td></tr>";
					}
			
			//Pagination data
			$projects = $doc->getElementsByTagName( "Projects" );
			foreach( $projects as $project )
			{
				$totalrecords = $doc->getElementsByTagName( "TotalRecords" );
				$totalRecord =$totalrecords->item(0)->nodeValue;
				
				$pagestarts = $doc->getElementsByTagName( "PageStart" );
				$pageStart =$pagestarts->item(0)->nodeValue;
				
				$pagelimits = $doc->getElementsByTagName( "PageLimit" );
				$pageLimit =$pagelimits->item(0)->nodeValue;
			}
			}//enf of if for success
			else{
				echo $message;
				}
	?>
   </tbody> 
   <tfoot>
   <tr>
     <td colspan="5" style="height:40px">
     	<input type="hidden" id="prj_page_limit" value="<?php echo $pageLimit?>" />
        <input type="hidden" id="prj_page_start" value="<?php echo $pageStart?>" />
        <input type="hidden" id="prj_total_record" value="<?php echo $totalRecord?>" />
     	<div class="pagination" id="project_pagination_div">
        <?php 
		if($totalRecord!=0 && $pageLimit!="" && $cnt!=1){
		$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,'imp_project_div','project_pagination_div',"index.php?option=com_webfonts&controller=webfontsproject&task=project_list_ajax");
		echo $wfs_pg_projects =  $wfs_pg->getPagination();
		
		}
?> </div>
      </td>
   </tr>
   </tfoot>  
	</table>
    <div class="clear wfs_row" style="margin-top:10px;">
    	<div class="left">
        	<span class="b_outer"><span class="b_inner">
    		<input type="button" class="b_style" id="refresh-list" value="Refresh List" />
    		</span></span>
         </div>
         <div class="left ml">
        	<span class="b_outer"><span class="b_inner">
    		<input type="button" class="b_style" value="Import Project" id="imp_prj_btn" />
    		</span></span>
         </div>
    </div>
	<div class="clear"></div>
	
	</div>
</div> 


	
</form>