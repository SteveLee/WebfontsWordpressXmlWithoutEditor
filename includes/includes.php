<?php
/*
Copyright 2010 Monotype Imaging Inc. 
This program is distributed under the terms of the GNU General Public License

start defining global variables*/

define("TBLCONFIG","wfs_configure");
if(is_dir(WP_PLUGIN_DIR.'/webfontswordpressxmlwithouteditor'))
	{ 
	define("FOLDER_NAME","webfontswordpressxmlwithouteditor");
	}
else if(is_dir(WP_PLUGIN_DIR.'/WebfontsWordpressXmlWithoutEditor'))
	{
	define("FOLDER_NAME","WebfontsWordpressXmlWithoutEditor");
	}
//define("FOLDER_NAME","WebfontsWordpressXmlWithoutEditor");
define("REQESTURI","http://api.fonts.com");
define("FFCSSHDLRURI",REQESTURI."/api/FontFaceCssHandler.axd?ProjectId=");
//define("GETFONTFAMILIESURI",REQESTURI."/api/GetFontFamilies.axd?projectid=");

define("FASTFONTURI","http://fast.fonts.com");
define("FONTFCURI",FASTFONTURI."/d/");

define("FFJSAPIURI",FASTFONTURI."/jsapi/");
define("FFCSSURL",FASTFONTURI."/cssapi/");

define("SIGNUPURI","https://webfonts.fonts.com/en-US/Subscription/SelectSubscription");
define("GETKEYURI","https://webfonts.fonts.com/en-US/Account/AccountInformation");

//RESPONSE LIMIT DEFINING

define("DOMAIN_LIMIT",10);

define("SELECTOR_LIMIT",10);

define("FONT_LIMIT",10);

define("PROJECT_LIMIT",10);

/*end defining global variables*/



/*
* getting user details
*/
function getUnPass(){
	$webfonts_userid = get_option('webfonts_userid','');
	$webfonts_public_key = get_option('webfonts_public_key','');
	$webfonts_private_key = get_option('webfonts_private_key','');
	$webfonts_usertype = get_option('webfonts_usertype','');
	return array($webfonts_userid,$webfonts_public_key,$webfonts_private_key,$webfonts_usertype);
}

/*begin for selectors*/
function getAllActiveSelectors(){
	$styleadd=get_stylesheet_directory().'/style.css';;
	$new_arr=getFileContent($styleadd);
	$wfs_selector=array_unique($new_arr);
	return $wfs_selector;
}
/*
* getting the file content from theme files
*/
function getFileContent($filename){
	$handle=fopen($filename,'r');
	$fileCont = fread($handle, filesize($filename));
	fclose($handle);
	$fileCont = preg_replace('/\/\\*.*?\\*\//s', '', $fileCont);
	$fileCont = preg_replace('/{.*?}/s', '::', $fileCont);
	$fileCont = preg_split('/::/',$fileCont,0,PREG_SPLIT_NO_EMPTY);
	foreach($fileCont as $value){
		$trmval=trim(preg_replace('/\\r\\n/', '', $value));
		if($trmval!=''){
			if($pos = strpos($trmval,",")){
				$further_exploded=explode(",",$trmval);//for exploding all the comma separated values
				foreach($further_exploded as $fexp){
					$newarr[]=trim($fexp);
				}
			}else{
				$newarr[]=$trmval;
			}			
		}
	}
	return $newarr;
}

 /*
 * Checking the day for which the current project should be fetched
 */ 
function checkday($projectDay){
	$today=date("w");
	//checkforminus sign
	$checkme="-";
	$returnValue=false;
	
	$pos = strpos($projectDay,$checkme);
	if($pos === false){
		$pos1=strpos($projectDay,",");
		if($pos1===false){
			if($today==$projectDay){
				$returnValue=true;
			}
		}else{
			$days=explode(",",$projectDay);
			foreach($days as $day){
				//checkif the "-" exists further...
				if(strpos($day,$checkme)){
					$day12=explode($checkme,$day);
					for($j=$day12[0];$j<=$day12[1];$j++){
						if($j>6){
							break;
						}
						if($j==$today){
							$returnValue=true;
						}
					}
				}else if($day==$today){
					$returnValue=true;
				}
			}
		}
	}else{
		$days=explode($checkme,$projectDay);
		for($i=$days[0];$i<=$days[1];$i++){
			if($i>6){
				break;
			}
			if($i==$today){
				$returnValue=true;
			}
		}
	}
	return $returnValue;
}

/**
Browser checking function
*/
function browserName(){
if ( strpos($_SERVER['HTTP_USER_AGENT'], 'Gecko') )
	{
		if ( strpos($_SERVER['HTTP_USER_AGENT'], 'Netscape') )
   			{
     			$browser = 'Netscape (Gecko/Netscape)';
   			}
		else if ( strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') )
   			{
     		$browser = 'Mozilla Firefox (Gecko/Firefox)';
   			}
   		else
   			{
     		$browser = 'Mozilla (Gecko/Mozilla)';
   			}
		}
else if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') )
		{
   		if ( strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') )
   			{
     			$browser = 'Opera (MSIE/Opera/Compatible)';
   			}
   		else
  		 	{
     		$browser = 'Internet Explorer (MSIE/Compatible)';
   			}
		}
	else
	{
   		$browser = 'Others browsers';
	}
return $browser;
}


/*
**load the individual projects details
@project id int
@key: condition to be searched
*/
function webfonts_project_profile_load($project_id, $key="wfs_configure_id"){
	global $wp_wfs_configure_table;
	global $wpdb;
	$myrows = $wpdb->get_results("SELECT * FROM `$wp_wfs_configure_table` WHERE `$key`='$project_id'");
	return $myrows[0];
}
