<?php 
/*
Copyright 2010 Monotype Imaging Inc. 
This program is distributed under the terms of the GNU General Public License
* A class to link to the WFS API
*/
/**
 * Root uri of api request
 */
define('ROOT_URL', "http://api.fonts.com");
define('MAIN_API_URL',"/rest/");


class Services_WFS{
	/**
    * constructs a new WFS API instance
    *
    * @param string  $publicKey
	* @param string  $privateKey
	* @param string  $uri
    *                                 
    */
	function Services_WFS($publicKey=null,$privateKey=null,$uri=null ){
		$this->public_key = $publicKey;
		$this->private_key =$privateKey;
		$this->uri = $uri;
		$this->curlPost = "";
		$this->AppKey = "cc2b2294-4ace-4e79-81de-15275642f7fd35880";
		
		
		}
	/*
	* Selector adding function
	*@param string $wfs_selector_tag
	*@return string
	*/	
	function addSelector($wfs_selector_tag)
	{	if(!empty($wfs_selector_tag)){ $this->curlPost.='&wfsselector_tag='.urlencode($wfs_selector_tag);}
		return $this->wfs_getInfo_post("create");
	}
	
	/*
	* Selector adding function
	*@param string $wfs_selector_tag
	*@return string
	*/	
	function deleteSelector()
	{	
		return $this->wfs_getInfo_post("delete");
	}
	
	/*
	* Selector saving function
	*@param string $wfs_selector_tag
	*@return string
	*/	
	function saveSelector($wfs_font_ids,$wfs_selector_ids)
	{	if(!empty($wfs_font_ids)){ $this->curlPost.='&wfsfont_ids='.urlencode($wfs_font_ids);}
		if(!empty($wfs_selector_ids)){ $this->curlPost.='&wfsselector_ids='.urlencode($wfs_selector_ids);}
		return $this->wfs_getInfo_post("update");
	}
	
	
	/*
	* Domain adding function
	*@param string $wfs_selector_tag
	*@return string
	*/	
	function addDomain($wfs_domain_name)
	{	if(!empty($wfs_domain_name)){ $this->curlPost.='&wfsdomain_name='.$wfs_domain_name;}
		return $this->wfs_getInfo_post("create");
	}
	
	/*
	* Domain deleting function
	*@return string
	*/	
	function deleteDomain()
	{	
		return $this->wfs_getInfo_post("delete");
	}
	
	/*
	* Domain editing function
	*@param string $wfs_selector_tag
	*@return string
	*/	
	function editDomain($wfs_domain_name)
	{	
		if(!empty($wfs_domain_name)){ $this->curlPost.='wfsdomain_name='.$wfs_domain_name;}
		return $this->wfs_getInfo_post("update");
	}
	
	
	/*
	*core function for communication with api
	*/
	function wfs_getInfo_post($method = ""){
		$curlurl = ROOT_URL.MAIN_API_URL.$this->uri;
		$data="";
		
		$finalHeader = $this->public_key.":".$this->sign(MAIN_API_URL.$this->uri, $this->public_key, $this->private_key);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $curlurl);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: " . urlencode($finalHeader), "AppKey: ".$this->AppKey));
		switch($method){
			case "create":
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				break;
			case "update":
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
				break;
			case "delete":
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
				break;
			default:
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
				break;
			}
		
		if(!empty($this->curlPost)){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->curlPost);
			unset($this->curlPost);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$data=curl_exec($ch);
		curl_close($ch);
		if(trim($data)==""){
			$data='<Message>Could not connect to wfs server.</Message>';
		}
		return $data;
		
	}
	/*end curl*/
	
	function sign($message, $publicKey, $privateKey)
	{
		return base64_encode(hash_hmac('md5', $publicKey."|".$message, $privateKey, true));
	}
}
?>