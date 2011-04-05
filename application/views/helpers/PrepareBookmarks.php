<?php
/**
 * Purpose: Contains prepare bookmarks helper and filed bookmarks class
 *
 * @copyright Jesse Clark 2010
 * @author Jesse Clark <mail@jesseclark.me>
 * @version 1.0
 */ 
class Zend_View_Helper_prepareBookmarks extends Zend_View_Helper_Abstract
{
    /**
	 * takes bookmarks orders them, and returns an html string prepared for display
	 *
	 * this function is a handler for the function orderBookmarks, which has a recursive element that needs 
	 * more parameters than the programmer needs. after this it takes the bookmarks and turns them into html
	 *
	 * @param array $bookmarks_to_prepare
	 * @return string	html bookmarks ready for display
	 */
	public function prepareBookmarks($bookmarks_to_prepare)
	{
    	$bookmarks = $this->orderBookmarks($bookmarks_to_prepare);
		return $this->prepareForDisplay($bookmarks);
    }

	/**
	 * a recursive function that takes unordered bookmarks, orders them, and returns an array of filed_bookmarks objects
	 *
	 * @param array $unordered_bookmarks    public use - arrays of bookmark data
	 * @param array $ordered_bookmarks 		internal use - bookmarks that are ordered 
	 * @param int $starting_orphan_count	internal use - incomming orphan count
	 * @param array $link_list	internal use - links to filed bookmarks objects
	 * @return array	[ordered_bookmarks] - contains all bookmarks even orphans, [orphan_bookmarks] - internal use, contains orphans
	 */
	private function orderBookmarks($unordered_bookmarks, $ordered_bookmarks = array(), $starting_orphan_count = 0, $link_list = array()) 
	{
		$orphan_bookmarks = array();

		foreach($unordered_bookmarks as $bookmark)
		{
			$id = (int)$bookmark['link_id'];
			$pid = (int)$bookmark['parent_id'];
			if($pid == 0){//pid of zero means its part of the root (gets its own section in the main page)
				$ordered_bookmarks[$id] = new filed_bookmarks($bookmark);
				$link_list[$id] = $ordered_bookmarks[$id];
				continue;
			}
			if(isset($link_list[$pid])){//if its parent is in the link
				$link_list[$id] = $link_list[$pid]->add($bookmark); //add child to parent, save location in the link list
				continue;
			}
			$orphan_bookmarks[] = $bookmark;//parent not in linked list, currently its an orphan
		}
		$ending_orphan_count = sizeof($orphan_bookmarks);
		if(($ending_orphan_count > 0 ) && ( $starting_orphan_count != $ending_orphan_count)){//continuing condition for recursion
			return $this->orderBookmarks($orphan_bookmarks,$ordered_bookmarks,$ending_orphan_count,$link_list);//continue to sort
		}else {//stop condition for recursion (ending orphan count 0 or starting and ending orphan count equal)
			if($ending_orphan_count > 0){//orphans are links with parents that don't exist or doesn't have an ancestor of 0
				$ordered_bookmarks['orphans'] = new filed_bookmarks(array("metadata"=>"orphan links","link"=>''));
				foreach($orphan_bookmarks as $orphan){
					$ordered_bookmarks['orphans']->add($orphan);
				}
			}
		}
		return array('ordered_bookmarks'=>$ordered_bookmarks,'orphan_bookmarks'=>$orphan_bookmarks);
	}

	/**
	 * takes an array of filed_bookmarks and adds their content into a html string
	 *
	 * @param array $bookmarks	takes an array of filed_bookmarks objects
	 * @return string	contains html for display
	 */
	private function prepareForDisplay($bookmarks) 
	{
		$return_string = "<section id='bookmarks'>";
		$i=0; //used to keep track of the number of columns per row,
		foreach($bookmarks['ordered_bookmarks'] as $filed_bookmark){
			$links = $filed_bookmark->retrieve();
			$i++;
			if($i%4 == 1){//every 1st (out of 4) bookmark starts on a new row
				$return_string .= '<div id=\'row\'>';
			}
			$return_string .= '<article><ul>' . $links . '</ul></article>';
			if($i % 4 == 0)
				$return_string .= '</div>';
		}
		if($i % 4 != 0){ //close the div incase it wasn't closed above.
		    $return_string .= '</div>';
		}
		$return_string .= '</section>';
		return $return_string;
	} 


}

class filed_bookmarks
{
	private $children = array();//array of filed_bookmarks objects
	private $bookmark_data = array();//contains bookmark data, ie.. link, metadata

	/**
	 * constructor, saves an array of bookmark data into the object
	 *
	 * @param array $bookmark
	 */
	public function filed_bookmarks($bookmark){
		$this->bookmark_data = $bookmark;
	}

	/**
	 * adds a child filed_bookmarks object to the parent that calls this function
	 *
	 * create a new child, add the child to the children array
	 *
	 * @param array $bookmark  	bookmark data
	 * @return object	returns a filed_bookmarks object
	 */
	public function add($bookmark) {
		$child = new filed_bookmarks($bookmark);
		$this->children[] = $child;
		return $child;
	}

	/**
	 * builds an html list item from the object and includes an unordered list from its children inside the list item
	 *
	 * @return string 	an html tagged string
	 */
	public function retrieve() {
		$link = $this->bookmark_data['link'];
		$metadata = $this->bookmark_data['metadata'];
		$retrieve = '<li>';

		//every link has a form that submits to the forwarder action
		$retrieve .= '<form action="' . Zend_View_Helper_Url::url(array('controller'=>'index','action'=>'forwarder')) . '" method="post">'; 
		if($link != ''){ //if link is empty, the bookmark is a folder
			$retrieve .= '<a href="' . $this->linkCleanup($link) .'">';//make sure http starts the url
		} else {
			$retrieve .= '<h1><a>';
		}
		$retrieve .= $metadata . '</a>';
		if(isset($this->bookmark_data['link_id'])) {//the orphan links fall into a folder with no link_id (this folder is not in the db)
			$link_id = $this->bookmark_data['link_id'];
			$retrieve .= 	'<input type="hidden" name="id" value="' . $link_id . '" />';

			//class imageButton in the css adds images to these inputs and removes their text, so that just an icon appears
			$retrieve .= 	'<input type="submit" name="send_to" id="add"  class="imageButton"  title="add" value="add" />';
			$retrieve .= 	'<input type="submit" name="send_to" id="delete" class="imageButton" title="delete" value="delete" />';
			$retrieve .= 	'<input type="submit" name="send_to" id="edit" class="imageButton" title="edit" value="edit" />'; 
		}
		
		if( $link == '' ) {//close the h1 tag for folders
			$retrieve .= '</h1>';
		}
		$retrieve .= '</form>'; 
		if(!empty($this->children)){ //make sure there are children so foreach doesnt throw an error about empty arrays
			foreach($this->children as $child)
			{
				//each child is in an unordered list (this call gets all the children, grandchildren, etc of this parent)
				$retrieve .= '<ul>' . $child->retrieve() . '</ul>';
			}
		}
		$retrieve .= '</li>';
		return $retrieve;
	}

	/**
	 * adds "http://" to strings that are missing it
	 *
	 * @param string $link
	 * @return string
	 */
	private function linkCleanup($link)
	{
		$needle = strtolower(substr($link,0,7));
		$haystack = array('http://', 'https:/');
    	if(!in_array($needle,$haystack)) {
			return 'http://'.$link;
		} else {
			return $link;
		}
	}
}  
