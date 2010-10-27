<?php
/*
Copyright 2010 Monotype Imaging Inc.  
This program is distributed under the terms of the GNU General Public License
*/
header("content-type: text/css");	
include_once('../../../wp-load.php');
include_once('../../../wp-includes/wp-db.php');

global $wpdb;
global $wp_wfs_configure_table;
$wfs_details = getUnPass();
$wfs_public_key = $wfs_details['1'];
$wfs_private_key = $wfs_details['2'];
if(empty($_GET['pid'])){
$project_array = wfs_get_key();	
	}
else{
$project_array = wfs_get_key('admin');
	}
$key = $project_array[0];
$browser = browserName();

$apiurl = "xml/Fonts/?wfspid=".$key;
$wfs_api = new Services_WFS($wfs_public_key,$wfs_private_key,$apiurl);
$xmlMsg = $wfs_api->wfs_getInfo_post();
//Creating JSON Instance
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
		
		$fontsList.= "\"".$FontName."/'".$FontCSSName."';\" + ";
		$stylesheetcss.="@font-face{font-family:'".$FontCSSName."';src:url('".FONTFCURI.$TTF.$ext."?".$CDNKey."&projectId=".$key."');}";
}
echo $stylesheetcss;


?>