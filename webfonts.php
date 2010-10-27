<?php
ob_start();
/*
Plugin Name: Webfonts
Plugin URI: http://webfonts.fonts.com/developers
Version: 1.0
Description: A Plugin that will add a webfonts service in wordpress.
Author: MTI
Author URI:http://www.webfonts.fonts.com/
Copyright 2010 Monotype Imaging Inc.  
This program is distributed under the terms of the GNU General Public License
*/

/*    
* Function to call when the plugins is avtivated
*/
function wfs_activate(){ //This is all the stuff the plug-in needs to do when it is activated

	global $wp_wfs_configure_table;
	$sql = "DROP TABLE IF EXISTS `".$wp_wfs_configure_table."`;
		CREATE TABLE `".$wp_wfs_configure_table."` (
		`wfs_configure_id` int(200) NOT NULL auto_increment,
		`project_name` varchar(255) NOT NULL default '',
		`project_key` varchar(255) NOT NULL default '',
		`project_day` varchar(255) NOT NULL default '0-6',
		`project_page_option` enum('0','1','2') NOT NULL default '0',
		`project_pages` text NOT NULL,
		`project_options` enum('0','1') NOT NULL default '0',
		`wysiwyg_enabled` enum('0','1') NOT NULL default '0' COMMENT '0>disabled, 1>enabled',
		`is_active` enum('0','1') NOT NULL default '0' COMMENT '0>inActive, 1>Active',
		`user_id` varchar(255) NOT NULL default '',
		`user_type` enum('0','1') NOT NULL default '0' COMMENT '0>free, 1> paid',
		 `editor_select` enum('0','1') NOT NULL DEFAULT '0',
		`updated_date` timestamp NOT NULL default '0000-00-00 00:00:00' on update CURRENT_TIMESTAMP,
		PRIMARY KEY  (`wfs_configure_id`)
		) ENGINE=MyISAM  AUTO_INCREMENT=0  DEFAULT CHARSET=utf8 ;";
createTable($wp_wfs_configure_table, $sql); //Little function to check that the table does not already exist
}

/*
*Function to creat table in wordpress datbaase
*@call in the activation
*/
function createTable($tableName, $sql){//reusable function
    global $wpdb;//call $wpdb to the give us the access to the DB
    if($wpdb->get_var("show tables like '". $tableName . "'") != $tableName) { //check whether the table exists or not
    require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta($sql);
    }
}

/*
deactivate the plugin
*/
function wfs_deactivate () {
	global $wp_wfs_configure_table, $wpdb;
	$wpdb->query("DROP TABLE {$wp_wfs_configure_table}");
	delete_option('webfonts_public_key');
	delete_option('webfonts_private_key');
	delete_option('webfonts_userid');
	delete_option('webfonts_usertype');
	}

/*
* Add a webfonts menu in the admin section
*/
function wfs_menu() {//Function to create our menu
	$wfs_admin_page = add_menu_page('Fonts.com Webfonts', 'Fonts.com Webfonts', 'administrator', 'wfs_options', 'wfs_options');
	add_action( "admin_print_scripts-$wfs_admin_page", 'wfs_admin_head' );
	
}

/*
Admin interface for the plugin
*/
function wfs_options(){
	global $wpdb;
	global $wp_wfs_configure_table;
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	include_once('webfonts_admin.php');
}
/**
* Authenticating with Webfonts when we provide username and password.
* pass two parameters username and password of webfonts
*/
function wfs_authSubmit($wfs_public_key,$wfs_private_key){
		
		//start checking in webfonts for authentication
		//Fetching the xml data from WFS
		$apiurl = "xml/Projects/";
		$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
		$xmlMsg = $wfs_api->wfs_getInfo_post();
		//authenticate stored username and password...
		update_option('webfonts_public_key',$wfs_public_key);
		update_option('webfonts_private_key',$wfs_private_key);
		
		$xmlDomObj = new DOMDocument();
		$xmlDomObj->loadXML($xmlMsg);
		$Messages = $xmlDomObj->getElementsByTagName("Message");
		$Message  = $Messages->item(0)->nodeValue;
		if($Message=="Success"){
			$note = $xmlDomObj->getElementsByTagName("Projects"); 
			$UserIds = $xmlDomObj->getElementsByTagName("UserId");
			$UserId  = $UserIds->item(0)->nodeValue;
			
			$UserRoles = $xmlDomObj->getElementsByTagName("UserRole");
			$UserRole  = $UserRoles->item(0)->nodeValue;
			
			update_option('webfonts_userid',$UserId);
			update_option('webfonts_usertype',(strtolower($UserRole)=="free")?0:1);
			
			$status=1;
		}else{
			$_SESSION['wfs_message']="Invalid Authentication";
			$status=0;
		}
		return array($status);
		//end checking in webfonts for authentication
}

/*
* Listing the project from ajax call
*/
function wfs_project_list(){
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	global $wp_wfs_configure_table;
	global $wpdb;
	$output = "";
	$pageStart = (!empty($_POST['pageStart']))?$_POST['pageStart']:0;
    //fetchin xml data from fonts
	$apiurl = "xml/Projects/?wfspstart=".$pageStart."&wfsplimit=".PROJECT_LIMIT;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$xmlUrl = $wfs_api->wfs_getInfo_post();
	//create xml instance
	if($xmlUrl != ""){
	//creating a DOM object
	$doc = new DOMDocument();
	$doc->loadXML($xmlUrl);
	
	$messages = $doc->getElementsByTagName( "Message" );
	$message = $messages->item(0)->nodeValue;
	if($message == "Success"){
	//fetching XML data
	$projects = $doc->getElementsByTagName( "Project" );
	$cnt = 1;
	$output.= '<ul >';
	foreach( $projects as $project )
		{
			$projectNames = $project->getElementsByTagName("ProjectName");	
			$projectName = $projectNames->item(0)->nodeValue;
			$projectKeys = $project->getElementsByTagName("ProjectKey");	
			$projectKey = $projectKeys->item(0)->nodeValue;
			$webfonts_added_project =   webfonts_project_profile_load($projectKey, "project_key");
			if(empty($webfonts_added_project->project_key)){
			$output.= '<li><label class="selectit" for="'.$projectKey.'">
					   <input id="'.$projectKey.'" type="checkbox"  value="'.$projectKey.'" name="project_import[]" class="import_project"/>&nbsp;&nbsp;'.$projectName.'.
					</label><input  type="hidden"  value="'.$projectName.'" name="project_name['.$projectKey.']"/></li>';
			$cnt++;
			$status = true;
			}else{
			$output.= '<li><label class="selectit" for="'.$projectKey.'">
						   <input id="'.$projectKey.'" type="checkbox" disabled="disabled"  value="'.$projectKey.'" name="project_import[]" class="import_project"/>&nbsp;&nbsp;'.$projectName.' <i style="font-size:10px;color:#21759B;">(Project already added.)</i>
						</label><input  type="hidden"  value="'.$projectName.'" name="project_name['.$projectKey.']"/></li>';
			$cnt++;
			$status = true;
			}
			
		}
	$totalRecordjson =$projectsArray['Projects']['TotalRecords'];
	$pageStartjson =$projectsArray['Projects']['PageStart'];
	$pageLimitjson =$projectsArray['Projects']['PageLimit'];
	
	if($cnt == 1){
		$output.= '<li> No project available.</li>';
		$status = true;
		}
	$output.= '</ul>';
	}else{
		$status = false;
		$output = $message;
		}

	$pageLimit =(!empty($_POST['pageLimit']))?$_POST['pageLimit']:$pageLimitjson;
	$totalRecord = (!empty($_POST['totalRecords']))?$_POST['totalRecords']:$totalRecordjson;
	$contentDiv = $_POST['contentDiv'];
	$paginationDiv = $_POST['paginationDiv'];
	$pagination="&nbsp;";
	if($totalRecord !=0 && $pageLimit!="" && $cnt != 1){
		$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,$contentDiv,$paginationDiv,"wfs_project_action");
		$pagination = $wfs_pg->getPagination();
		}
	}//end of xml url if
	else {
		$status = false;
		}
echo json_encode(array('data'=>$output,'status'=>$status,'pagination'=>$pagination));
exit;
}
/*
* Listing the selectors for ajax call
*/
function wfs_selector_list(){
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	global $wp_wfs_configure_table;
	global $wpdb;
	$output = "";
	$data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wp_wfs_configure_table." WHERE wfs_configure_id = %d ",$_POST['pid']));
	$pageStart= 0;

	//fetchin xml data from fonts
	$apiurl = "xml/Selectors/?wfspstart=0&wfsplimit=".SELECTOR_LIMIT."&wfspid=".$data->project_key;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$xmlUrl = $wfs_api->addSelector($_POST['selectorname']);
	if($xmlUrl!=""){
	//creating a DOM object
	
	$doc = new DOMDocument();
	$doc->loadXML($xmlUrl);
	$messages = $doc->getElementsByTagName( "Message" );
	$message = $messages->item(0)->nodeValue;
	$count = 1;
	
	if($message == "Success"){
	//fetching XML data
	$selectors = $doc->getElementsByTagName( "Selector" );
	
	$output.= '<table cellspacing="0" cellpadding="0" border="0" class="widefat" style="margin-top:20px;" ><tbody>';
	foreach( $selectors as $selector )
	{
			$SelectorTags = $selector->getElementsByTagName("SelectorTag");	
			$SelectorTag = $SelectorTags->item(0)->nodeValue;
									
			$SelectorIDs = $selector->getElementsByTagName("SelectorID");	
			$SelectorID = $SelectorIDs->item(0)->nodeValue;
			
			$SelectorFontIDs = $selector->getElementsByTagName("SelectorFontID");	
			$SelectorFontID = $SelectorFontIDs->item(0)->nodeValue;
			
			$fontsArr = wfs_font_list($data->project_key,$SelectorFontID,$count);
			
			$output.='<tr style="height:40px;">
				<td>'.$SelectorTag.'</td>
				<td>'.$fontsArr[0].'</td>
				<td><span class="wfs_selectors" style="font-size:26px;font-family:'.$fontsArr[3].'" id="fontid_'.$count.'">'.$fontsArr[1].'</span></td>
				<td><a href="admin.php?page=wfs_options&func=selector_act&pid='.$_POST['pid'].'&sid='.$SelectorID.'" onclick="return confirm(\'Are you sure want to delete selector '.$SelectorTag.'?\');">Remove</a><input type="hidden" name="selector_'.$count.'"  id="selector_'.$count.'" value="'.$SelectorID.'" />
				</td>
			</tr>';
			$count++;
		}
	if($count == 1){
	     $output.='<tr style="height:40px;">
            <td colspan="4" align="center">No Selectors available.</td>
        </tr>';
	} 
	$output.='</tbody></table><div class="clear"></div>';/*<input type="submit" value="'._e('Save').'" name="submit" class="button-primary" />';*/
	$status = true;
	$totalrecords = $doc->getElementsByTagName( "TotalRecords" );
	$totalRecord =$totalrecords->item(0)->nodeValue;
							
	$pagestarts = $doc->getElementsByTagName( "PageStart" );
	$pageStart =$pagestarts->item(0)->nodeValue;
							
	$pagelimits = $doc->getElementsByTagName( "PageLimit" );
	$pageLimit =$pagelimits->item(0)->nodeValue;
	if($totalRecord!="" && $pageLimit!="" && $count != 1){
		$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,'selectors_list','selector_pagination_div',"wfs_selector_action_pagination");
		$pagination =$wfs_pg->getPagination();	
		}
	}else{
	$status = true;
		}
	}else{
		$status = false;
		}
echo json_encode(array('data'=>$output,'status'=>$status,'errMsg'=>$message,'pagination'=>$pagination));
exit;
}
/*
* Listing the selectors for ajax call for pagination
*/
function wfs_selector_list_pagination(){
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	global $wp_wfs_configure_table;
	global $wpdb;
	$output = "";
	$data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wp_wfs_configure_table." WHERE wfs_configure_id = %d ",$_POST['pid']));
	$pageStart = (!empty($_POST['pageStart']))?$_POST['pageStart']:0;
	//fetchin xml data from fonts
	$apiurl = "xml/Selectors/?wfspstart=".$pageStart."&wfsplimit=".SELECTOR_LIMIT."&wfspid=".$data->project_key;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$xmlUrl = $wfs_api->wfs_getInfo_post();
	if($xmlUrl !=""){
	//creating a DOM object
	$doc = new DOMDocument();
	$doc->loadXML($xmlUrl);
	$messages = $doc->getElementsByTagName( "Message" );
	$message = $messages->item(0)->nodeValue;
	$count = 1;
	if($message == "Success"){
	//fetching XML data
	$selectors = $doc->getElementsByTagName( "Selector" );
	$output.= '<table cellspacing="0" cellpadding="0" border="0" class="widefat" style="margin-top:20px;" ><tbody>';
	foreach( $selectors as $selector )
	{
			$SelectorTags = $selector->getElementsByTagName("SelectorTag");	
			$SelectorTag = $SelectorTags->item(0)->nodeValue;
									
			$SelectorIDs = $selector->getElementsByTagName("SelectorID");	
			$SelectorID = $SelectorIDs->item(0)->nodeValue;
			
			$SelectorFontIDs = $selector->getElementsByTagName("SelectorFontID");	
			$SelectorFontID = $SelectorFontIDs->item(0)->nodeValue;
			
			$fontsArr = wfs_font_list($data->project_key,$SelectorFontID,$count);
			
			$output.='<tr style="height:40px;">
				<td>'.$SelectorTag.'</td>
				<td>'.$fontsArr[0].'</td>
				<td><span class="wfs_selectors" style="font-size:26px;font-family:'.$fontsArr[3].'" id="fontid_'.$count.'">'.$fontsArr[1].'</span></td>
				<td><a href="admin.php?page=wfs_options&func=selector_act&pid='.$_POST['pid'].'&sid='.$SelectorID.'" onclick="return confirm(\'Are you sure want to delete selector '.$SelectorTag.'?\');">Remove</a><input type="hidden" name="selector_'.$count.'"  id="selector_'.$count.'" value="'.$SelectorID.'" />
				</td>
			</tr>';
			$count++;
		}
	if($count == 1){
	     $output.='<tr style="height:40px;">
            <td colspan="4" align="center">No Selectors available.</td>
        </tr>';
	} 
	$output.='</tbody></table><div class="clear"></div>';/*<input type="submit" value="'._e('Save').'" name="submit" class="button-primary" />';*/
	$status = true;
	$pageLimit =$_POST['pageLimit'];
	$totalRecord = $_POST['totalRecords'];
	$contentDiv = $_POST['contentDiv'];
	$paginationDiv = $_POST['paginationDiv'];
	if($pageLimit != "" && $totalRecord != ""){
		$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,'selectors_list','selector_pagination_div',"wfs_selector_action_pagination");
		$pagination =$wfs_pg->getPagination();	
		}	
	}else{
	$status = false;
		}
	} else{ $status = false; }
echo json_encode(array('data'=>$output,'status'=>$status,'errMsg'=>$message,'pagination'=>$pagination));
exit;
}
/*
* Listing the domain for ajax call
*/
function wfs_domain_list(){
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	global $wp_wfs_configure_table;
	global $wpdb;
	$output = "";
	$pid = $_POST['pid'];
	$data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wp_wfs_configure_table." WHERE wfs_configure_id = %d ",$pid));
	$pageStart = 0;

	//fetchin xml data from fonts
	$apiurl = "xml/Domains/?wfspstart=".$pageStart."&wfsplimit=".DOMAIN_LIMIT."&wfspid=".$data->project_key;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$xmlUrl = $wfs_api->addDomain($_POST['domainname']);
	//creating a DOM object
	$doc = new DOMDocument();
	$doc->loadXML($xmlUrl);
	//fetching XML data
	$count = 1;
	$messages = $doc->getElementsByTagName( "Message" );
	$message = $messages->item(0)->nodeValue;
	if(strtolower($message)=="success"){
		//fetching XML data
		$domains = $doc->getElementsByTagName( "Domain" );
		$output.= '<table cellspacing="0" cellpadding="0" border="0" class="widefat" style="margin-top:20px;" ><tbody>';
		foreach( $domains as $domain )
		{
			$domainNames = $domain->getElementsByTagName("DomainName");	
			$domainName = $domainNames->item(0)->nodeValue;
			
			$domainIDs = $domain->getElementsByTagName("DomainID");	
			$domainID = $domainIDs->item(0)->nodeValue;
			
			$output.='<tr style="height:40px;">
				<td><a href="http://'.$domainName.'" target="_blank">'.$domainName.'</a></td>
				<td><a href="admin.php?page=wfs_options&func=domain_act&pid='.$pid.'&did='.$domainID.'&dname='.$domainName.'&mode=edit"  >Edit</a>&nbsp;|&nbsp;<a href="admin.php?page=wfs_options&func=domain_act&pid='.$pid.'&did='.$domainID.'" onclick="return confirm(\'Are you sure want to delete selector '.$domainName.'?\');" >Remove</a></td>				
			</tr>';
			$count++;
		}
	if($count == 1){
	     $output.='<tr style="height:40px;">
            <td colspan="4" align="center">No domain available.</td>
        </tr>';
	} 
	$output.='</tbody></table><div class="clear"></div>';/*<input type="submit" value="'._e('Save').'" name="submit" class="button-primary" />';*/
	$status = true;
	 
	$totalrecords = $doc->getElementsByTagName( "TotalRecords" );
	$totalRecord =$totalrecords->item(0)->nodeValue;
	
	$pagestarts = $doc->getElementsByTagName( "PageStart" );
	$pageStart =$pagestarts->item(0)->nodeValue;
	
	$pagelimits = $doc->getElementsByTagName( "PageLimit" );
	$pageLimit =$pagelimits->item(0)->nodeValue;
	    
	$contentDiv = $_POST['contentDiv'];
	$paginationDiv = $_POST['paginationDiv'];
	if($totalRecord!="" && $pageLimit!="" && $count != 1){
		$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,$contentDiv,$paginationDiv,"wfs_domain_action_pagination");
		$pagination = $wfs_pg->getPagination();	
		}
	}else{
		$status = false;
	}
 
echo json_encode(array('data'=>$output,'status'=>$status,'errMsg'=>$message,'pagination'=>$pagination));
exit;
}
/*
* Listing the domain for ajax call for pagination
*/
function wfs_domain_list_pagination(){
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	global $wp_wfs_configure_table;
	global $wpdb;
	$output = "";
	$pid = $_POST['pid'];
	$data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wp_wfs_configure_table." WHERE wfs_configure_id = %d ",$pid));
	$pageStart = (!empty($_POST['pageStart']))?$_POST['pageStart']:0;
	//fetchin xml data from fonts
	$apiurl = "xml/Domains/?wfspstart=".$pageStart."&wfsplimit=".DOMAIN_LIMIT."&wfspid=".$data->project_key;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$xmlUrl = $wfs_api->wfs_getInfo_post();
	//creating a DOM object
	$doc = new DOMDocument();
	$doc->loadXML($xmlUrl);
	//fetching XML data
	$count = 1;
	$messages = $doc->getElementsByTagName( "Message" );
	$message = $messages->item(0)->nodeValue;
	if(strtolower($message)=="success"){
		//fetching XML data
		$domains = $doc->getElementsByTagName( "Domain" );
		$output.= '<table cellspacing="0" cellpadding="0" border="0" class="widefat" style="margin-top:20px;" ><tbody>';
		foreach( $domains as $domain )
		{
			$domainNames = $domain->getElementsByTagName("DomainName");	
			$domainName = $domainNames->item(0)->nodeValue;
			
			$domainIDs = $domain->getElementsByTagName("DomainID");	
			$domainID = $domainIDs->item(0)->nodeValue;
			
			$output.='<tr style="height:40px;">
				<td><a href="http://'.$domainName.'" target="_blank">'.$domainName.'</a></td>
				<td><a href="admin.php?page=wfs_options&func=domain_act&pid='.$pid.'&did='.$domainID.'&dname='.$domainName.'&mode=edit"  >Edit</a>&nbsp;|&nbsp;<a href="admin.php?page=wfs_options&func=domain_act&pid='.$pid.'&did='.$domainID.'" onclick="return confirm(\'Are you sure want to delete selector '.$domainName.'?\');" >Remove</a></td>				
			</tr>';
			$count++;
		}
	if($count == 1){
	     $output.='<tr style="height:40px;">
            <td colspan="4" align="center">No domain available.</td>
        </tr>';
	} 
	$output.='</tbody></table><div class="clear"></div>';/*<input type="submit" value="'._e('Save').'" name="submit" class="button-primary" />';*/
	$status = true;
			
	
	$pageLimit =$_POST['pageLimit'];
	$totalRecord = $_POST['totalRecords'];
	$contentDiv = $_POST['contentDiv'];
	$paginationDiv = $_POST['paginationDiv'];
	if($totalRecord!="" && $pageLimit!="" && $count != 1){
		$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,$contentDiv,$paginationDiv,"wfs_domain_action_pagination");
		$pagination = $wfs_pg->getPagination();
	}
	}else{
	$status = false;
	
		}
 
echo json_encode(array('data'=>$output,'status'=>$status,'errMsg'=>$message,'pagination'=>$pagination));
exit;
}
/*
** fetch the font list drop down in selectors tab
*/
function wfs_font_list($project_key,$defaultFont="null",$count){ 
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	$result = array();
	$options ='<select id="fonts-list@'.$count.'" class="fonts-list" name="font_list[]">';  
	$options.= '<option value="-1" >- - - - - Please select a font- - - - --</option>';  
	//fetchin xml data from fonts
	$apiurl = "xml/Fonts/?wfspid=".$project_key;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
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
/*
*Fetch all the fonts given a project key from ajax call
@pid: string
*/
function wfs_font_list_pagination(){
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	global $wpdb;
	global $wp_wfs_configure_table;
	$pageStart = (!empty($_POST['pageStart']))?$_POST['pageStart']:0;
	$pid = $_POST['pid'];
	$data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wp_wfs_configure_table." WHERE wfs_configure_id = %d ",$pid));
	
	//fetchin xml data from fonts
	$apiurl = "xml/Fonts/?wfspstart=".$pageStart."&wfsplimit=".FONT_LIMIT."&wfspid=".$data->project_key;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$xmlMsg = $wfs_api->wfs_getInfo_post();
	
	if($xmlMsg!=""){	
	$xmlDomObj = new DOMDocument();
	$xmlDomObj->loadXML($xmlMsg);
	$fonts = $xmlDomObj->getElementsByTagName( "Font" );
	$webfonts=array();
	$Messages = $xmlDomObj->getElementsByTagName( "Message" );
	$Message= $Messages->item(0)->nodeValue;
	if($Message == "Success"){
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
	for($i=0;$i< count($webfonts["FontName"]);$i++){
		if(($i%2)==0){$class = "even";}else{$class = "odd";}
								$output.= '<div class="font_sep '.$class.'">
								<div class="font_img" style="font-family:\''.$webfonts["FontCSSName"][$i].'\' !important;font-size:30px;">'.$webfonts["FontPreviewTextLong"][$i].'</div>
								<div class="fontnames"><u>'.$webfonts["FontName"][$i].'</u> | <u>'.$webfonts["FontFondryName"][$i].'</u>
								| <u>'.$webfonts["FontLanguage"][$i].'</u>
								'.$webfonts["FontSize"][$i].'
								</div>
								</div>';
		
		
	}

	
		$pageLimit =$_POST['pageLimit'];
		$totalRecord = $_POST['totalRecords'];
		$contentDiv = $_POST['contentDiv'];
		$paginationDiv = $_POST['paginationDiv'];
		if($pageLimit!="" && $totalRecord!="" && count($webfonts["FontName"])!=0){
			$wfs_pg = new wfs_pagination($totalRecord,$pageStart,$pageLimit,$contentDiv,$paginationDiv,"wfs_font_action");
			$pagination = $wfs_pg->getPagination();
		}
		 $status = true;
	}else{
		$status = false;
		}
	}else{
		$status = false;
		}
		
echo  json_encode(array('status' => $status, 'data' => $output,'pagination'=>$pagination));
exit;
}

/*
* Adding javascript to the wordpress front end
*/
function wfs_front_head(){
	$project_array = wfs_get_key();
	$key = $project_array[0];	
	if(!(wfs_visibility_checking($project_array[2]) xor $project_array[1]) ){
		if($project_array[3] == 1){
		echo '<link rel="stylesheet" href="'.FFCSSURL.$key.'.css" type="text/css" />\n';	
		}else{
		$script = '<script type="text/javascript" src="'.FFJSAPIURI.$key.'.js"></script>';
		}
		
	/********* Commented to disable the editor option - By Keshant ******/
		
	/*if(is_single()){	
	if($project_array[4]==1 && $project_array[5]==0){
		global $wfs_username;
		global $wfs_userid;
		global $wfs_public_key;
		global $wfs_private_key;
		global $wfs_usertype;
			
			//fetchin xml data from fonts
			$apiurl = "xml/Fonts/?wfspid=".$key;
			$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
			$xmlMsg = $wfs_api->wfs_getInfo_post();
				
			$xmlDomObj = new DOMDocument();
			$xmlDomObj->loadXML($xmlMsg);
			$fonts = $xmlDomObj->getElementsByTagName( "Font" );
		
			$fontsListTM="";
				foreach($fonts as $font){
					$FontNames = $font->getElementsByTagName("FontName");
					$FontName= $FontNames->item(0)->nodeValue;
					
					$FontCSSNames = $font->getElementsByTagName("FontCSSName");
					$FontCSSName= $FontCSSNames->item(0)->nodeValue;
					$fontsListTM.= $FontName.'='.$FontCSSName.'; '; 
					}
				$default_font = "Andale Mono=andale mono,times;"."Arial=arial,helvetica,sans-serif;".
		"Arial Black=arial black,avant garde;".
		"Book Antiqua=book antiqua,palatino;".
		"Comic Sans MS=comic sans ms,sans-serif;".
		"Courier New=courier new,courier;".
		"Georgia=georgia,palatino;".
		"Helvetica=helvetica;".
		"Impact=impact,chicago;".
		"Symbol=symbol;".
		"Tahoma=tahoma,arial,helvetica,sans-serif;".
		"Terminal=terminal,monaco;".
		"Times New Roman=times new roman,times;".
		"Trebuchet MS=trebuchet ms,geneva;".
		"Verdana=verdana,geneva;".
		"Webdings=webdings;".
		"Wingdings=wingdings,zapf dingbats";
		//change the source if you have tinymce js in different folder other than mention below.
		echo '<script type="text/javascript" src="'.WP_PLUGIN_URL . '/webfonts/tinymce/jscripts/tiny_mce/tiny_mce.js"></script>';
		echo '<script type="text/javascript">
		tinyMCE.init({
		mode : "textareas",
		theme : "advanced",
		theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
		theme_advanced_fonts : \''.$fontsListTM.$default_font.'\',
		content_css:	"' . WP_PLUGIN_URL . '/webfonts/font.php"
	});
		</script>';
		echo '<style>.mceEditor table, .mceEditor table tr td { margin: 0 !important; padding: 0 !important; width: auto !important; }</style>';	
	 }
	if($project_array[4]==1 && $project_array[5]==1){
		
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	global $keyTM;
	
	//fetchin xml data from fonts
	$apiurl = "xml/Fonts/?wfspid=".$key;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$xmlMsg = $wfs_api->wfs_getInfo_post();
	//create json instance
	
	$xmlDomObj = new DOMDocument();
	$xmlDomObj->loadXML($xmlMsg);
	$fonts = $xmlDomObj->getElementsByTagName( "Font" );
	$fontsList="";
			
	foreach($fonts as $font){
		$FontNames = $font->getElementsByTagName("FontName");
		$FontName= $FontNames->item(0)->nodeValue;
		
		$FontCSSNames = $font->getElementsByTagName("FontCSSName");
		$FontCSSName= $FontCSSNames->item(0)->nodeValue;
		
		$fontsList.= $FontName."/".$FontCSSName.";";
		}
	echo '<script type="text/javascript" src="'.WP_PLUGIN_URL . '/webfonts/ckeditor/ckeditor.js"></script>';
	define('SITECOOKIEPATH', preg_replace('|https?://[^/]+|i', '', get_option('siteurl') . '/' ) );
	echo '<script type="text/javascript">var wfs_info ={"ckfonts" : "'.$fontsList.'" };var userSettings = {
		\'url\': \''.SITECOOKIEPATH.'\'} </script>';
		
	echo "<script type='text/javascript' src='".SITECOOKIEPATH."/wp-admin/load-scripts.php?c=1&amp;load=jquery,utils'></script>";
	
	echo '<script type="text/javascript" src="'.WP_PLUGIN_URL . '/webfonts/js/ckeditortwp.js"></script>';
	
	
	 }
	}*/
	echo $script;
	}
}

/********* Commented to disable the editor option - By Keshant ******/

/*function wfs_editor_head(){
	$project_array = wfs_get_key();
	$key = $project_array[0];	
	if(!(wfs_visibility_checking($project_array[2]) xor $project_array[1]) ){
		  if (is_admin()) {
			switch (basename($_SERVER['SCRIPT_FILENAME'])) {
				case "post.php":
				case "post-new.php":
				case "page.php":
				case "page-new":
				case "comment.php":
					if($project_array[3] == 1){
							echo '<link rel="stylesheet" href="'.FFCSSURL.$key.'.css" type="text/css" />';	
					}else{
							$script = '<script  type="text/javascript" src="'.FFJSAPIURI.$key.'.js"></script>';
					}
					echo $script;
					
					break;
				default:
					return;
			}
		}
	}
}*/

/*
* Adding javascript to WP backend
*/ 
function wfs_admin_head() {
	wp_enqueue_script('wfscookie',WP_PLUGIN_URL . '/'.FOLDER_NAME.'/js/jquery_cookie.js','','1.0');
	wp_enqueue_script('ckeditorScript',WP_PLUGIN_URL . '/'.FOLDER_NAME.'/ckeditor/ckeditor.js','','1.0');
	wp_enqueue_script('webfontsScript',WP_PLUGIN_URL . '/'.FOLDER_NAME.'/js/webfonts.js','','1.0');
	$project_array = wfs_get_key('admin');
	echo '<script  type="text/javascript" src="'.FFJSAPIURI.$project_array[0].'.js"></script>';
	echo "<link rel='stylesheet' href='".WP_PLUGIN_URL ."/".FOLDER_NAME."/css/webfonts.css' type='text/css' />\n";
	
}

/********* Commented to disable the editor option - By Keshant ******/

/*function wfs_ckeditor_head(){
	global $wfs_userid;
	global $wfs_public_key;
	global $wfs_private_key;
	global $wfs_usertype;
	if(!empty($_GET['pid'])){
		$project_array = wfs_get_key('admin');}
	else{
	$project_array = wfs_get_key();
	}
	$key = $project_array[0];
	//fetchin xml data from fonts
	$apiurl = "xml/Fonts/?wfspid=".$key;
	$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
	$xmlMsg = $wfs_api->wfs_getInfo_post();
			
			$xmlDomObj = new DOMDocument();
			$xmlDomObj->loadXML($xmlMsg);
			$fonts = $xmlDomObj->getElementsByTagName( "Font" );
			$fontsList="";
			foreach($fonts as $font){
					$FontNames = $font->getElementsByTagName("FontName");
					$FontName= $FontNames->item(0)->nodeValue;
					
					$FontCSSNames = $font->getElementsByTagName("FontCSSName");
					$FontCSSName= $FontCSSNames->item(0)->nodeValue;
					
					$fontsList.= $FontName."/".$FontCSSName.";";
					}
	wp_enqueue_script('ckeditorScriptwp',WP_PLUGIN_URL . '/webfonts/ckeditor/ckeditor.js','','1.0');
	echo '<script type="text/javascript">var wfs_info ={"ckfonts" : "'.$fontsList.'" }</script>';
	wp_enqueue_script('ckeditortwp',WP_PLUGIN_URL . '/webfonts/js/ckeditortwp.js','','1.0',true);

	}*/
	
/*
* Checking the page condition
*/
function wfs_visibility_checking($pages){
	$pageArrDb = array();
    $pageArrDb = explode(',',$pages);
	$retval = false;
			if((in_array('1', $pageArrDb)) && is_front_page()) { 
				$retval = true;
			}

			if(in_array('2', $pageArrDb) && is_home()) { 
				$retval = true;
				
			}

			if(in_array('3', $pageArrDb) && is_page() ) { 
				$retval = true;
			}

			if(in_array('4', $pageArrDb) && is_single() ) { 
				$retval = true;
			}

			if(in_array('5', $pageArrDb) && is_archive() ) { 
				$retval = true;
			}

			if(in_array('6', $pageArrDb) && is_404() ) { 
				$retval = true;
			}
	
	return $retval;
	}

/*
Generate the key depending upon the condition
*/
function wfs_get_key($section="front"){
	global $wpdb;
	global $wp_wfs_configure_table;
	global $wfs_userid;
	$project = array();
	if($section == "admin"){
		$data = $wpdb->get_row( $wpdb->prepare("SELECT project_key,project_page_option,project_options,project_pages,project_day,wysiwyg_enabled,editor_select FROM ".$wp_wfs_configure_table." WHERE `user_id` = %d and wfs_configure_id = %d", $wfs_userid, $_GET['pid']));		
			$project[]=$data->project_key;
			$project[]=$data->project_page_option;
			$project[]=$data->project_pages;
			$project[]=$data->project_options;
			$project[]=$data->wysiwyg_enabled;
			$project[]=$data->editor_select;
	
	}else{
		$resultArr = $wpdb->get_results( $wpdb->prepare("SELECT project_key,project_page_option,project_options,project_pages,project_day,wysiwyg_enabled,editor_select FROM ".$wp_wfs_configure_table." WHERE `is_active` ='1' and `user_id` = %d ORDER BY `updated_date` desc", $wfs_userid));
	foreach($resultArr as $data)
		{
			$dayValue = $data->project_day;
			if(checkday($dayValue)){
			$project[]=$data->project_key;
			$project[]=$data->project_page_option;
			$project[]=$data->project_pages;
			$project[]=$data->project_options;
			$project[]=$data->wysiwyg_enabled;
			$project[]=$data->editor_select;
			break;
			}
			
		}
	}
	/*echo '<pre>';
	print_r($resultArr);
	*/
	
	return $project;
	}

//Declare the global variable to connect to database
global $wpdb;
//Give our table a name and use the WP prefix
global $wp_wfs_configure_table;
/*User details globalising
* @Userid = $wfs_userid
* @Username = $wfs_username
* @Password = $wfs_password
* @Usertype = $wfs_usertype
*/
global $wfs_userid;
global $wfs_public_key;
global $wfs_private_key;
global $wfs_usertype;
include( dirname(__FILE__) . '/includes/includes.php');
include( dirname(__FILE__) . '/includes/wfs_pagination.php');
include( dirname(__FILE__) . '/includes/wfsapi.class.php');

$wpdb->show_errors();
register_activation_hook( __FILE__, 'wfs_activate' ); //hook to call the function when it is activated
register_deactivation_hook( __FILE__, 'wfs_deactivate' );
/*
Setting the varible value
*/
$wp_wfs_configure_table = $wpdb->prefix. "wfs_configure"; //Wfs table name

$wfs_details = getUnPass();

$wfs_userid = $wfs_details['0'];
$wfs_public_key = $wfs_details['1'];
$wfs_private_key = $wfs_details['2'];
$wfs_usertype = $wfs_details['3'];
/*
*End of setting up variable
*/
add_action('admin_menu', 'wfs_menu');
add_action('wp_ajax_wfs_project_action', 'wfs_project_list');
add_action('wp_ajax_wfs_selector_action', 'wfs_selector_list');
add_action('wp_ajax_wfs_selector_action_pagination', 'wfs_selector_list_pagination');
add_action('wp_ajax_wfs_domain_action', 'wfs_domain_list');
add_action('wp_ajax_wfs_font_action', 'wfs_font_list_pagination');
add_action('wp_ajax_wfs_domain_action_pagination', 'wfs_domain_list_pagination');
add_action('wp_head', 'wfs_front_head');
/********* Commented to disable the editor option - By Keshant ******/
//add_action( "admin_print_scripts", 'wfs_editor_head' );

$project_array = wfs_get_key();


global $keyTM;
$keyTM = $project_array[0];

/*
*Adding fonts to the font family
*/	

/********* Commented to disable the editor option - By Keshant *******/

/*if(($project_array[4]==1 && $project_array[5]==0 )|| $_GET['page'] == 'wfs_options'){

	if ( ! function_exists('wfs_fonts_tinymce') ) {
			
			function wfs_fonts_tinymce($init){
				global $wfs_public_key;
				global $wfs_private_key;
				global $wfs_usertype;
				if(empty($_GET['pid'])){
					$project_array = wfs_get_key();
					}else{
					$project_array = wfs_get_key('admin');	
						}
				$key = $project_array[0];	
				//fetchin xml data from fonts
				$apiurl = "xml/Fonts/?wfspid=".$key;
				$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
				$xmlMsg = $wfs_api->wfs_getInfo_post();
				
				$xmlDomObj = new DOMDocument();
				$xmlDomObj->loadXML($xmlMsg);
				$fonts = $xmlDomObj->getElementsByTagName( "Font" );
				
				$fontsListTM="";
					foreach($fonts as $font){
						$FontNames = $font->getElementsByTagName("FontName");
						$FontName= $FontNames->item(0)->nodeValue;
						
						$FontCSSNames = $font->getElementsByTagName("FontCSSName");
						$FontCSSName= $FontCSSNames->item(0)->nodeValue;
						$fontsListTM.= $FontName.'='.$FontCSSName.'; '; 
						}
					$default_font = "Andale Mono=andale mono,times;"."Arial=arial,helvetica,sans-serif;".
			"Arial Black=arial black,avant garde;".
			"Book Antiqua=book antiqua,palatino;".
			"Comic Sans MS=comic sans ms,sans-serif;".
			"Courier New=courier new,courier;".
			"Georgia=georgia,palatino;".
			"Helvetica=helvetica;".
			"Impact=impact,chicago;".
			"Symbol=symbol;".
			"Tahoma=tahoma,arial,helvetica,sans-serif;".
			"Terminal=terminal,monaco;".
			"Times New Roman=times new roman,times;".
			"Trebuchet MS=trebuchet ms,geneva;".
			"Verdana=verdana,geneva;".
			"Webdings=webdings;".
			"Wingdings=wingdings,zapf dingbats";
				$init['theme_advanced_fonts'] =$fontsListTM.$default_font;
				return $init;
				}
			}	
	add_filter( 'tiny_mce_before_init', 'wfs_fonts_tinymce' );
			
	// Adding fonts face css Tiny Mce Iframe
			
	if ( ! function_exists('wfs_css_tinymce') ) {
				function wfs_css_tinymce($wp) {
					
			
					$wp .= ',' . WP_PLUGIN_URL . '/webfonts/font.php?pid='.$_GET['pid'];
					return trim($wp, ' ,');
				}
			}
	add_filter( 'mce_css', 'wfs_css_tinymce' );
			
	// Adding Font selecting drop down to Tiny Mce
			
			
	if ( ! function_exists('wfs_fontfamily_tinymce') ) {
				function wfs_fontfamily_tinymce($init) {
				
				$init['theme_advanced_buttons1'] = 'fontselect,fontsizeselect';
				return $init;
				}
			}
			
	add_filter( 'mce_buttons', 'wfs_fontfamily_tinymce', 999 );
}
	
if(($project_array[4]==1 && $project_array[5]==1 )|| $_GET['page'] == 'wfs_options'){
	add_action( "admin_print_scripts", 'wfs_ckeditor_head' );
}
*/

$wpdb->hide_errors(); 