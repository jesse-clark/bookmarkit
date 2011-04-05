<?php
/**
 * Purpose: model for bookmarks, includes getBookmark, get all, add, update (edit), delete, and cedeCustodyToParent
 *
 * @copyright Jesse Clark 2010
 * @author Jesse Clark <mail@jesseclark.me>
 * @version 1.0
 */
class Application_Model_DbTable_Bookmarks extends Zend_Db_Table_Abstract
{

    protected $_name = 'bookmarks'; //table bookmarks

	/**
	 * retrieves a bookmark via link_id
	 *
	 * @param int $link_id
	 * @return array
	 */
    public function getBookmark($link_id)
	{
		$link_id = (int)$link_id; //type cast as int incase bad data is passed to us
		$row = $this->fetchRow('link_id = ' . $link_id);
		if (!$row) {
			throw new Exception("Could not find link id: $link_id"); //might remove this and let the controller deal with it.
		}
		return $row->toArray();
	}

	/**
	 * gets all of a users bookmarks
	 *
	 * @param int $user_id
	 * @return Zend_Db_Adapter fetch mode (array)
	 */
	public function getAllBookmarks($user_id)
	{
    	$sql = $this->select()
		 		   	->where('user_id = ?', $user_id)
					->where('visible = ?', true)  //only want those bookmarks that are visible to the user (delete makes them invisible)
					->order('link_id');
   		return $this->fetchAll($sql); 	
	}

	/**
	 * add bookmark to database
	 *
	 * @param int $user_id
	 * @param string $link
	 * @param string $metadata
	 * @param int $parent_id
	 */
	public function addBookmark($user_id, $link, $metadata, $parent_id)
	{
    	$data = array(
					'user_id'=>$user_id,
					'link'=>$link,
					'metadata'=>$metadata,
					'parent_id'=>$parent_id);
		$this->insert($data);
	}

	/**
	 * Update a bookmark
	 *
	 * Used to update a bookmark after a user edits a bookmark
	 *
	 * @param int $link_id
	 * @param int $user_id
	 * @param string $link
	 * @param string $metadata
	 * @param int parent_id
	 */
	public function updateBookmark($link_id, $user_id, $link, $metadata, $parent_id)
	{
    	$data = array(
					'link'=>$link,
					'metadata'=>$metadata,
					'parent_id'=>$parent_id);
		$this->update($data, array('link_id =' . (int)$link_id,'user_id = ' . (int)$user_id));
	}

	/**
	 * removes a bookmarks (from user visiblity)
	 *
	 * Users sometimes say they want to delete something, then they comeback and say "I oopsed and want my info back"
	 * Because of this we don't actually delete the record, we keep it and make it invisible to the user.
	 *
	 * @param int $link_id
	 * @todo add $user_id to params and update with params
	 */
	public function deleteBookmark($link_id)
	{
    	//$this->delete('link_id =' . (int)$link_id);   //this is how you would delete a field if you need to use it
		$data = array('visible'=>false);
		$this->update($data, 'link_id = '.(int)$link_id);
	}

	/**
	 * Child links get adopted by their grandparent.
	 *
	 * Look for children of link_id, update the childs parent_id to that of the grandparent.
	 * This should be used right before a delete action.
	 *
	 * @param int $link_id 		the parent
	 * @param int $parent_id 	the grandparent
	 */
	public function cedeCustodyToParent($link_id,$parent_id)
	{
		$data = array('parent_id'=>$parent_id);
		$this->update($data, "parent_id = " . (int)$link_id);
	}
}

