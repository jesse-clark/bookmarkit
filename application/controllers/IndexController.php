<?php
/**
 * Purpose: contains Display(indexAction), add, edit, delete, and forward Actions.
 *
 * @copyright Jesse Clark 2010
 * @author Jesse Clark <mail@jesseclark.me>
 * @version 1.0
 */  
class IndexController extends Zend_Controller_Action
{

    public function init()
    {
    }

	/**
	 *  loads the view with the users bookmarks if a user is logged in
	 *
	 *  Get the user id - redirected to sign in page if not logged in,
	 *  load user data from Bookmarks model into the view
	 */
    public function indexAction()
    {
		$user_id = $this->_getUserId(); //this function redirects if no user.
       	$bookmarks = new Application_Model_DbTable_Bookmarks();
	   	$this->view->bookmarks = $bookmarks->getAllBookmarks($user_id);
    }

	/**
	 *  adds new bookmark through a web form
	 *
	 *  Check user id (redirected if none), load form,
	 *  if data was posted validate it, add bookmark, redirect back to index
	 *  else add the link id to the form and display
	 */
    public function addAction()
    {
		$user_id = $this->_getUserId(); //redirects if no user. 
        $form = new Application_Form_Bookmark();
		$form->submit->SetLabel('Add');
		$this->view->form = $form;

		if($this->getRequest()->isPost()) {
			$formData = $this->getRequest()->getPost();
			if($form->isValid($formData)) {
				$parent_id = $form->getValue('parent_id');
				$link = $form->getValue('link');
				$metadata = $form->getValue('metadata');
				$bookmarks = new Application_Model_DbTable_Bookmarks();
				$bookmarks->addBookmark($user_id, $link, $metadata, $parent_id);

				$this->_helper->redirector('index'); //redirect back to index
			} else { //if data not valid
				$form->populate($formData); //load form back up for display if something wasn't correct
			}
		} else { //if request not post
			$parent_id = $this->_getLinkIdFromSession(false);//false is passed to keep it from redirecting
			if($parent_id == false) { //no link id means they added from the main page add link
				$parent_id = 0;//has no parent link, falls under root of the page.
			}
			$form_parent_id = $form->getElement('parent_id');
			$form_parent_id->setValue( (int)$parent_id);
		}
	}

	/**
	 * edit existing bookmark through a web form
	 *
	 * Check user id (redirected if none), load form,
	 * if request was post, validate posted data, update bookmark, redirect to index
	 * else get the link id (redirected if none), get bookmark info and place it into form 
	 */
    public function editAction()
    {
		$user_id = $this->_getUserId(); //redirects if no user id. 
        $form = new Application_Form_Bookmark();
		$form->submit->setLabel('Save');
		$this->view->form = $form;

		if ($this->getRequest()->isPost()) {
			$formData  = $this->getRequest()->getPost();
			if($form->isValid($formData)) {
				$link_id = (int)$form->getValue('link_id');
				$parent_id = $form->getValue('parent_id');
				$link = $form->getValue('link');
				$metadata = $form->getValue('metadata');

				$bookmarks = new Application_Model_DbTable_Bookmarks();
				$bookmarks->updateBookmark($link_id, $user_id, $link, $metadata, $parent_id);

				$this->_helper->redirector('index');
			} else {//if form not valid
				$form->populate($formData);
			}
		}//if not post 
		$link_id = $this->_getLinkIdFromSession();//will redirect if no link id,
		$bookmarks = new Application_Model_DbTable_Bookmarks();
		$form->populate($bookmarks->getBookmark($link_id));
    }

	/**
	 * prompt user if they really want to delete link, delete if yes
	 *
	 * Check user id (redirected if none),
	 * if request is post, and value del is yes, link child nodes to deleted link's parent, then delete bookmark, redirect to index
	 * else get linkid (redirect if none), get bookmark data and display it to page
	 */
    public function deleteAction()
    {
		$user_id = $this->_getUserId(); //redirects if no user id
        if($this->getRequest()->isPost()) {
			$del = $this->getRequest()->getPost('del');
			if($del == 'yes') {
				$link_id = (int)$this->getRequest()->getPost('link_id');
				$parent_id = (int)$this->getRequest()->getPost('parent_id');
				$bookmarks = new Application_Model_DbTable_Bookmarks();
				$bookmarks->cedeCustodyToParent($link_id,$parent_id);//child nodes adopt grandparent as parent, must happen before delete incase of error of delete
				$bookmarks->deleteBookmark($link_id);//doesn't actually delete, updates visible field, check model for details
			}
			$this->_helper->redirector('index');//yes and no responces get redirected to the index 
		}
		$link_id = $this->_getLinkIdFromSession();//redirects if no link id
		$bookmarks = new Application_Model_DbTable_Bookmarks();
		$this->view->bookmark = $bookmarks->getBookmark($link_id);  
    }

	/**
	 * pulls Posted data, puts it into a session, and redirects to the correct Action
     *
     * This forwards request from the main page. Every bookmark's Add, Delete, and Edit link is part of a form that posts data to this action. 
	 * By doing this I was able to reduce the number of forms to one form per link on the main page. This function checks the user id,
	 * then checks to make sure this is a post, the POST data is placed into the session namespace "links" and has a life of one hop (lasts to the next redirect)
	 * after this the function switchs on the send_to value from POST and is redirected to the correct action.
	 */
    public function forwarderAction()
    {
		$user_id = $this->_getUserId();//redirects if none 
        if($this->getRequest()->isPost()) {
			$session = new Zend_Session_Namespace("links");
			$session->setExpirationHops(1);//Session will only last to the next redirect
   			$session->link_id = $this->getRequest()->getPost('id');  			
			switch($this->getRequest()->getPost('send_to')) {
				case 'add':
					$this->_helper->redirector('add');
					break;
				case 'edit':
					$this->_helper->redirector('edit');
					break;
				case 'delete':
					$this->_helper->redirector('delete');
					break; 					
			} //no default case, as the following redirect will catch it.
		}
        $this->_helper->redirector('index');
    }

 
    /**
	 * returns the user id of current user
	 *
	 * Gets an instance of the Zend_Auth, if there is an identity, it returns the current user's id
	 * else it will either redirect the current user or return false depending on the value of redirect_if_none.
	 * By default the function redirects (you want this action if no user is logged in and someone navigates to the addAction)
	 * there are other times you want to override this option and just return false (for no value)
	 *
	 * @param bool $redirect_if_none 	default is true, sets option if you don't want it to redirect
	 * @return int 		returns user_id if it exists, else redirects to index or returns false depending on $redirect_if_none
	 */
	protected function _getUserId($redirect_if_none = true)
	{
    	$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()) {
			return $auth->getIdentity()->user_id;
		} elseif($redirect_if_none) {
			$this->_helper->redirector('index','auth');
		} else {
			return false;
		} 	
	}

	/**
	 * Returns the Link_id that is stored in the session
	 *
	 * Starts Zend_Session, checks to see if "links" exists, if so returns the link_id
	 * if there is no "links" session it will either redirect to index or return false, depending on $redirect_if_none setting
	 *
	 * @param bool $redirect_if_none 	default is true, used if you do not want to redirect if there is no "links" session
	 * @return int		returns link_id if it exists, else it either redirects or returns bool false depending on $redirect_if_none setting
	 */
	protected function _getLinkIdFromSession($redirect_if_none = true)
	{
		Zend_Session::start();
		if(Zend_Session::namespaceIsset("links")) {
			$session = new Zend_Session_Namespace("links");
			return $session->link_id;
		}elseif($redirect_if_none){
        	$this->_helper->redirector('index');
		}else {
        	return false;
		}
	}
}

