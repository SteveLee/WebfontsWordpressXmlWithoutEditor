/*
Copyright 2010 Monotype Imaging Inc.  
This program is distributed under the terms of the GNU General Public License
*/
if(document.getElementById("content")){CKEDITOR.replace("content");}
jQuery(document).ready(function(){
if(document.getElementById("comment")){CKEDITOR.replace("comment");}

jQuery('iframe').hover(function(){
	if(document.getElementById("content_parent")){
	jQuery('#content_parent').html('&nbsp');
	}
});

});
	if(typeof( CKEDITOR ) != 'undefined'){
			/*CKEDITOR.config.font_names ="Aachen Bold/Aachen W01 Bold; Andy Bold-W01/Andy W01 Bold;Nadianne Bold-W01/Nadianne W01 Bold;Windsor Elongated-W01/Windsor W01 Elongated;chispado Regular-W01/Achispado LT W01;"*/
			CKEDITOR.config.font_names =wfs_info['ckfonts']+ 'Arial/Arial, Helvetica, sans-serif;' +
	'Comic Sans MS/Comic Sans MS, cursive;' +
	'Courier New/Courier New, Courier, monospace;' +
	'Georgia/Georgia, serif;' +
	'Lucida Sans Unicode/Lucida Sans Unicode, Lucida Grande, sans-serif;' +
	'Tahoma/Tahoma, Geneva, sans-serif;' +
	'Times New Roman/Times New Roman, Times, serif;' +
	'Trebuchet MS/Trebuchet MS, Helvetica, sans-serif;' +
	'Verdana/Verdana, Geneva, sans-serif';
			CKEDITOR.config.contentsCss = userSettings['url']+ 'wp-content/plugins/webfonts/font.php',
			CKEDITOR.on( 'instanceReady', function(e){
						addStyle();
						return true;
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
			
			var linkNode = doc.createElement("LINK");
			linkNode.type = "text/css";
			linkNode.href = userSettings['url']+ 'wp-content/plugins/webfonts/font.php';
			linkNode.rel = "stylesheet";
			linkNode.id = "mti-dropdown-css";
			var head = doc.getElementsByTagName("HEAD")[0];
			jQuery(head).append(linkNode);
			cnt++;
			}else{
				if(IsIE8Browserck() == true){
				var href = jQuery("#mti-dropdown-css", doc).attr("href");
					jQuery("#mti-dropdown-css", doc).attr("href", href);
				}
			}
			
		
		var iFrameText =jQuery("iframe[title ^='Rich text editor']");
		var docText = iFrameText.attr("contentDocument") || iFrameText.attr("contentWindow").document;
		
		jQuery(".cke_panel_list li a", doc).unbind("click.mti").bind("click.mti",function(){
	 if(IsIE8Browserck() == true){	setTimeout(function(){
			jQuery("link[href=?q=admin/settings/webfonts/font]", docText).attr("href", "?q=admin/settings/webfonts/font");
								}, 1000);}
			});
		
			
		}, 200);
	})
	}
	

function IsIE8Browserck() {

    var rv = -1;

    var ua = navigator.userAgent;

    var re = new RegExp("Trident\/([0-9]{1,}[\.0-9]{0,})");

    if (re.exec(ua) != null) {

        rv = parseFloat(RegExp.$1);

    }
return (rv == 4);

}
							
						