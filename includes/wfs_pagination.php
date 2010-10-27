<?php 
/*
Copyright 2010 Monotype Imaging Inc. 
This program is distributed under the terms of the GNU General Public License
*/
class wfs_pagination{
	
	var $totalRecords;
	var $pageStarts;
	var $pageLimits;
	 
	var $paging_first;
	var $paging_last;
	var $paging_next;
	var $paging_previous;
	var $totalPages;
	var $contentDiv;
	var $paginationDiv;
	var $url;
	
	function wfs_pagination($totalrecords,$start,$limit,$contentDiv,$paginationDiv,$url,$first="First", $last="Last", $next="Next", $previous="Previous") {
		$this->totalRecords = $totalrecords;
		$this->pageStarts = $start;
		$this->pageLimits = $limit;
		$this->paging_first = $first;
		$this->paging_last = $last;
		$this->paging_next = $next;
		$this->paging_previous = $previous;
		$this->totalPages;
		$this->contentDiv = $contentDiv;
		$this->paginationDiv = $paginationDiv;
		$this->url = $url;
   	}
	
	function getPagination(){
		$output = "";
			//echo $this->totalRecords;
			//echo $this->pageLimits;
			$this->totalPages =ceil($this->totalRecords/ $this->pageLimits);
			//echo $this->totalPages;
			 // get the current page or set a default
			if (isset($_POST['currentpage']) && is_numeric($_POST['currentpage'])) {
			 // cast var as int
			$currentpage = (int) $_POST['currentpage'];
			} else {
			// default page num
			$currentpage = 1;
			} // end if
			
		if ($currentpage > $this->totalPages) {
   			// set current page to last page
   			$currentpage = $this->totalPages;
			} // end if
		// if current page is less than first page...
		if ($currentpage < 1) {
 		  // set current page to first page
  		 $currentpage = 1;
		} // end if
		$offset = $this->pageStarts;
		
		/******  build the pagination links ******/
		// range of num links to show
		$range = 5;
		$ps = (int)$this->pageStarts;
		$pl = (int)$this->pageLimits;
		// if not on page 1, don't show back links
		if ($currentpage > 1) {
			// show << link to go back to page 1
			$output.= " <a href='javascript:;' class ='wfs_class' id='page_1' onclick=ajaxPage(1,0,$this->pageLimits,$this->totalRecords,'$this->contentDiv','$this->paginationDiv','$this->url')>".$this->paging_first."</a> ";
			// get previous page num
			$prevpage = $currentpage - 1;
			$offset = ($ps-$pl);
			// show < link to go back to 1 page
			$output.= " <a href='javascript:;' class ='wfs_class'  id='page_$prevpage'  onclick=\"ajaxPage($prevpage,$offset,$this->pageLimits,$this->totalRecords,'$this->contentDiv','$this->paginationDiv','$this->url')\">".$this->paging_previous."</a> ";
		
		} // end if 
		
		// loop to show links to range of pages around current page
		$offsetprev = 0;
		$offset=$ps;
		for ($x = ($currentpage - $range); $x < (($currentpage + $range) + 1); $x++) {
			// if it's a valid page number...
			if (($x > 0) && ($x <= $this->totalPages)) {
				// if we're on current page...
				if ($x == $currentpage) {
				// 'highlight' it but don't make a link
						$output.= " <a href='javascript:;' class ='currClass wfs_class'  id='page_$x' onclick='return false;' >$x</a> ";
				// if not current page...
				} else {
					if($x>$currentpage){
						
				$offset+=$pl;	
				// make it a link
					$output.= " <a href='javascript:;' class ='wfs_class' id='page_$x'  onclick=ajaxPage($x,$offset,$this->pageLimits,$this->totalRecords,'$this->contentDiv','$this->paginationDiv','$this->url') >$x</a> ";
					}else{
						
						$output.= " <a href='javascript:;' class ='wfs_class' id='page_$x'  onclick=ajaxPage($x,$offsetprev,$this->pageLimits,$this->totalRecords,'$this->contentDiv','$this->paginationDiv','$this->url') >$x</a> ";
						$offsetprev+= $this->pageLimits;
						}
				} // end else
			} // end if 
		} // end for
		
		
		// if not on last page, show forward and last page links        
		if ($currentpage != $this->totalPages) {
		// get next page
			
			$nextpage = $currentpage + 1;
			$offset = ($ps+$pl);
			// $output.= forward link for next page 
			$output.= " <a href='javascript:;' class ='wfs_class' id='page_$nextpage'  onclick=ajaxPage($nextpage,$offset,$this->pageLimits,$this->totalRecords,'$this->contentDiv','$this->paginationDiv','$this->url')>".$this->paging_next."</a> ";
			$pt = (int)$this->totalRecords;
			$offset = ceil($pt/$pl)-1;
			$offset = ($offset * $pl);
			// echo forward link for lastpage
			$output.= " <a href='javascript:;' class ='wfs_class' id='page_$this->totalPages'  onclick=ajaxPage($this->totalPages,$offset,$this->pageLimits,$this->totalRecords,'$this->contentDiv','$this->paginationDiv','$this->url')>".$this->paging_last."</a> ";
		} // end if
		/****** end build pagination links ******/

		return $output;
		}
	
}
?>