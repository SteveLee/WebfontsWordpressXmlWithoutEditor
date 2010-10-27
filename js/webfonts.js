/*
Copyright 2010 Monotype Imaging Inc.  
This program is distributed under the terms of the GNU General Public License
*/
var loadingimage = document.createElement('img');	
loadingimage.src="images/loading.gif";
var selectorname;
jQuery(document).ready(function(){
/* Select all checkbox when clicked on select all link*/	
jQuery(".selectAll").click(function(){
	jQuery(".plist").attr("checked","true");

});

/* Deselect all checkbox when clicked on select all link*/	  
jQuery(".selectNone").click(function(){
	jQuery(".plist").removeAttr("checked");

});

/*Display the more action dropdown on hover*/		  
jQuery("#wfs-actions").hover(function(){
	var moreActinDiv = jQuery("#wfs-first");
	if(moreActinDiv.attr("class") != "slide-down" ){
		jQuery("#wfs-first").addClass("slide-down"); 
	} else{
	jQuery("#wfs-first").removeClass("slide-down"); 
	}
	jQuery("#wfs-inside").toggle();
	});

/*Toggle the import project toolbar*/
jQuery("#wfs_importProject").click(function(){
	if(jQuery(this).parent().attr('class') == 'postbox')
	{	
	jQuery(this).parent().addClass('closed');
	}else{
	jQuery(this).parent().removeClass('closed');
	}
	});
/*Display the arrowhead in import project toolbar on hover*/
jQuery("#wfs_importProject").hover(function(){

	jQuery(this).children().next().addClass("wfs_arrowhead");
	},function(){
	jQuery(this).children().next().removeClass("wfs_arrowhead");
	});
/*Display the arrowhead in import project toolbar on hover*/
jQuery("#add_project").click(function(){
	if(jQuery(".import_project:checked").length == 0){
		alert('Please select the projects to be added.');
		return false;	
		}			
		
	});

/* Refresh the project list in the import projects*/
jQuery("#refresh_project").click(function(){
	jQuery("#add_project").attr('disabled','disabled')
	jQuery('#imp_prj').html(loadingimage);
	var pageLimit = jQuery("#prj_page_limit").val();
	var pageStart = jQuery("#prj_page_start").val();;
	var totalRecords = jQuery("#prj_total_record").val();;
	var data = {action: 'wfs_project_action',pageLimit:pageLimit,pageStart:pageStart,totalRecords:totalRecords,currentpage:1,contentDiv:"imp_prj",paginationDiv:"project_pagination_div"};
	 	
	jQuery.post(ajaxurl, data, function(response) {
		if(response.status){
		jQuery('#imp_prj').html(response.data);	
		if(response.pagination){
			document.getElementById("project_pagination_div").innerHTML=response.pagination;
		}
		jQuery("#add_project").removeAttr('disabled')
		}
	},'json');
  });	

jQuery('ul.wfs_tabNav a, ul.wfs_tabNav2 a').click(function() {
	var curChildIndex = jQuery(this).parent().prevAll().length + 1;

		jQuery(this).parent().parent().children('.wfs_current').removeClass('wfs_current');
		jQuery(this).parent().addClass('wfs_current');
		jQuery(this).parent().parent().next().next().children().hide().parent().children('div:nth-child('+curChildIndex+')').show();
		jQuery.cookie("wfstabindex", this.id);
	return false;        
});

jQuery('#edit-days').blur(function(e){
			var wfsdd = jQuery("input[name=days]").val();
			wfsdd = wfsdd.replace(/\d/g, "");
			wfsdd = wfsdd.replace(/-/g, "");
			wfsdd = wfsdd.replace(/,/g, "");
			wfsdd = wfsdd.replace(/ /g, "");
			if (wfsdd!='') {
				alert("Invalid display day");
				setTimeout(function(){jQuery("#edit-days").focus();jQuery("#edit-days").select();}, 10);
				
		}
		return false;
	 });

jQuery("#add_selector_btn").click(function(){
	selectorname = jQuery('#add_selector_text').val();
	if(jQuery('#add_selector_text').val() == "")
			{
				alert('Please enter the selector name.');
				return false;
			}
	addSelectors(selectorname)
	return false;
	});

jQuery('#add_selector_text').keypress(function(e) {
	if(e.keyCode == 13)
		{
			selectorname = jQuery('#add_selector_text').val();
			if(jQuery('#add_selector_text').val() == "")
			{
				alert('Please enter the selector name.');
				return false;
			}
			addSelectors(selectorname)
			return false;
		}
	});

jQuery(".addtowebfonts").click(function(){
	jQuery('html, body').animate({scrollTop:0}, 'fast');										
	selectorname = jQuery(this).attr('id')
	addSelectors(selectorname);
	//setTimeout("jQuery(this).parent().html('Added to Webfonts')", 5000);
	jQuery(this).parent().html('Added to Webfonts');
	return false;
	});

jQuery("#add_domain_btn").click(function(){

	addDomain()
	return false;
	});

jQuery('#add_domain_text').keypress(function(e) {
	if(e.keyCode == 13)
		{
			
			addDomain()
			return false;
		}
	});
jQuery('#editor_sample').click(function(){
		if(jQuery('#editor_sample').text() == "See online editor with webfonts"){
			jQuery('#wfs_display_editor').show();
			jQuery('#editor_sample').text("Hide online editor") 
			}else{
				jQuery('#editor_sample').text("See online editor with webfonts") 
				jQuery('#wfs_display_editor').hide();
				}
	});

initBinding();

if(document.getElementById("wfs_sample_ckeditor")){CKEDITOR.replace("wfs_sample_ckeditor");}
	if(typeof( CKEDITOR ) != 'undefined'){
			if(document.getElementById("project_id")){
			var pid= jQuery("#project_id").val();}
			CKEDITOR.config.font_names = wfs_info['ckfonts'] + 'Arial/Arial, Helvetica, sans-serif;' +
	'Comic Sans MS/Comic Sans MS, cursive;' +
	'Courier New/Courier New, Courier, monospace;' +
	'Georgia/Georgia, serif;' +
	'Lucida Sans Unicode/Lucida Sans Unicode, Lucida Grande, sans-serif;' +
	'Tahoma/Tahoma, Geneva, sans-serif;' +
	'Times New Roman/Times New Roman, Times, serif;' +
	'Trebuchet MS/Trebuchet MS, Helvetica, sans-serif;' +
	'Verdana/Verdana, Geneva, sans-serif';			
CKEDITOR.config.contentsCss = userSettings['url']+ 'wp-content/plugins/webfonts/font.php?pid='+pid,
			CKEDITOR.on( 'instanceReady', function(e){
						addStyle();
						return true;
		 		});
 				}

if(jQuery.cookie("wfstabindex")){
	var referrer = document.referrer
	var checkURL = location.href;
	if(checkURL != referrer){
		wfscookiefn(document.getElementById(jQuery.cookie("wfstabindex")));
	}
	}
	
jQuery("#webfonts_api_token_option").click(function(){
		
			jQuery("#token_div").toggle();
		
													   });
});
function wfscookiefn(button){
		var curChildIndex = jQuery(button).parent().prevAll().length + 1;
		jQuery(button).parent().parent().children('.wfs_current').removeClass('wfs_current');
		jQuery(button).parent().addClass('wfs_current');
		jQuery(button).parent().parent().next().next().children().hide().parent().children('div:nth-child('+curChildIndex+')').show();
			return false;
						}
function addDomain(){
		if(jQuery('#add_domain_text').val() == "")
			{
				alert('Please enter the domain name.');
				return false;
			}
		var old_value =  jQuery('#domain_list').html();
		jQuery('#domain_list').html(loadingimage);
		var domainName = jQuery('#add_domain_text').val();
		var pid = jQuery('#project_id').val();
		var pageLimit = jQuery("#domain_page_limit").val();
		var pageStart = jQuery("#domain_page_start").val();
		var totalRecords = jQuery("#domain_total_record").val();
		var data = {action: 'wfs_domain_action', pid:pid, domainname:domainName,pageLimit:pageLimit,pageStart:pageStart,totalRecords:totalRecords,currentpage:0,contentDiv:"domain_list",paginationDiv:"domain_pagination_div"};
		
		jQuery.post(ajaxurl, data, function(response,textStatus, XMLHttpRequest) {
											
			if(response.status){
				jQuery('#succMsg').hide().html('Domain successfully added.').slideDown(700).show();
				setTimeout("jQuery('#succMsg').slideUp(1000)", 5000);
				jQuery('#domain_list').html(response.data);	
				if(response.pagination){
					jQuery('#domain_pagination_div').html(response.pagination);	
				}
			}else{
				jQuery('html, body').animate({scrollTop:0}, 'fast');
		
				jQuery('#errMsg').hide().html('Domain name already exists').slideDown(700).show();
				setTimeout("jQuery('#errMsg').slideUp(1000)", 5000);
				jQuery('#domain_list').html(old_value);
				
				return false;
				};
		},'json');
	 
	  return false;
	  
	}

function addSelectors(selectorname){
	var old_value =  jQuery('#selectors_list').html();
		jQuery('#selectors_list').html(loadingimage);
	 
		var pid = jQuery('#project_id').val();
		var pageLimit = jQuery("#selector_page_limit").val();
		var pageStart = jQuery("#selector_page_start").val();
		var totalRecords = jQuery("#selector_total_record").val();
		
		var data = {action: 'wfs_selector_action', pid:pid, selectorname:selectorname,pageLimit:pageLimit,pageStart:pageStart,totalRecords:totalRecords,currentpage:0,contentDiv:"selectors_list",paginationDiv:"selector_pagination_div"};
	    
		jQuery.post(ajaxurl, data, function(response,textStatus, XMLHttpRequest) {
			if(response.status){
				if(response.errMsg == "DuplicateSelectorName"){
					
					jQuery('html, body').animate({scrollTop:0}, 'fast');	
					
					jQuery('#errMsg').hide().html('Selector name already exists').slideDown(700).show();
					setTimeout("jQuery('#errMsg').slideUp(1000)", 5000);
					jQuery('#selectors_list').html(old_value);
					
					return false;
					
					}
				else{
				jQuery('#succMsg').hide().html('Selector successfully added.').slideDown(700).show();
				setTimeout("jQuery('#succMsg').slideUp(1000)", 5000);
				jQuery('#selectors_list').html(response.data);
				if(response.pagination){
					jQuery('#selector_pagination_div').html(response.pagination);	
				}
			
				
				}
			}initBinding();
		},'json');
	 
	  return false;
	}

function initBinding(){
	
		jQuery(".fonts-list").change(function(){
				var fontcssid = this.id;
				var fontid = fontcssid.split("@");
				var fontarr = this.value;
				var fontdata = fontarr.split("@!");
				if(fontdata == -1)
				{
				jQuery("#fontid_"+fontid[1]).text('');
				}
				else{
				jQuery("#fontid_"+fontid[1]).css("font-family","'"+fontdata[0]+"'");
				jQuery("#fontid_"+fontid[1]).text(fontdata[1]);
				}
			});	
		}
		

function addStyle(){
	
	var cnt = 0;
	var oldOnclick =jQuery("a[title='Font Name']").attr('onclick');
	jQuery("a[title='Font Name']").attr('onclick',null);
	jQuery("a[title='Font Name']").click(function(e){
		
		
		oldOnclick.call(this,e);
		setTimeout(function(){
		var iFrame =jQuery("iframe[title='Font Name']");
		var doc = iFrame.attr("contentDocument") || iFrame.attr("contentWindow").document;
		if(cnt == 0 ){
			if(document.getElementById("project_id")){
			var pid= jQuery("#project_id").val();}
			var linkNode = doc.createElement("LINK");
			linkNode.type = "text/css";
			linkNode.href = userSettings['url']+ 'wp-content/plugins/webfonts/font.php?pid='+pid;
			linkNode.rel = "stylesheet";
			linkNode.id = "mti-dropdown-css";
			var head = doc.getElementsByTagName("HEAD")[0];
			jQuerys(head).append(linkNode);
			cnt++;
			}else{
				if(IsIE8Browser() == true){
				var href = jQuery("#mti-dropdown-css", doc).attr("href");
					jQuery("#mti-dropdown-css", doc).attr("href", href);
				}
			}
			
		
		var iFrameText =jQuery("iframe[title ^='Rich text editor']");
		var docText = iFrameText.attr("contentDocument") || iFrameText.attr("contentWindow").document;
		
		jQuery(".cke_panel_list li a", doc).unbind("click.mti").bind("click.mti",function(){
	 if(IsIE8Browser() == true){	setTimeout(function(){
			jQuery("link[href=?q=admin/settings/webfonts/font]", docText).attr("href", "?q=admin/settings/webfonts/font");
								}, 1000);}
			});
		
			
		}, 200);
	})
	}

function IsIE8Browser() {

    var rv = -1;

    var ua = navigator.userAgent;

    var re = new RegExp("Trident\/([0-9]{1,}[\.0-9]{0,})");

    if (re.exec(ua) != null) {

        rv = parseFloat(RegExp.$1);

    }
return (rv == 4);

}

//Ajax based pagination code//
function ajaxPage(currPage,pageStart,pageLimit,totalRecords,contentDiv,paginationDiv,url){
	var pid ="";
	var old_value =  jQuery('#'+contentDiv).html();							
	jQuery('#'+contentDiv).html(loadingimage);
	if(jQuery('#project_id').val()!=""){
	pid= jQuery('#project_id').val();
	}
	/*alert(url+"||pl"+pageLimit+"||ps"+pageStart+"||tr"+totalRecords+"||"+currPage+"||"+contentDiv+"||"+paginationDiv+"||"+pid)*/
	var data = {action: url,pageLimit:pageLimit,pageStart:pageStart,totalRecords:totalRecords,currentpage:currPage,contentDiv:contentDiv,paginationDiv:paginationDiv,pid:pid};
	 	
	jQuery.post(ajaxurl, data, function(response) {
		
		if(response.status){
	
		jQuery('#'+contentDiv).html(response.data);	
			if(response.pagination){
				document.getElementById(paginationDiv).innerHTML=response.pagination;
			}
		if(contentDiv == "selectors_list"){
						initBinding();
						}	
		}
	},'json');
}
//end of Ajax based pagination//