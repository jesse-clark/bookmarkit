<?php
/**
 * Purpose: loggedinas builds navigation for every page via layout.phtml  
 *
 * @copyright Jesse Clark 2010
 * @author Jesse Clark <mail@jesseclark.me>
 * @version 1.0
 */
class Zend_View_Helper_LoggedInAs extends Zend_View_Helper_Abstract
{
	/**
	 * generates navagation depending on page and if a user is logged in
	 */
 	public function loggedInAs()
	{
     	$auth = Zend_Auth::getInstance();
		$request = Zend_Controller_Front::getInstance()->getRequest();
		$controller = $request->getControllerName();
		$action = $request->getActionName();

		// if the user is logged in, the following will display
		if($auth->hasIdentity()) {
         	$username = $auth->getIdentity()->username;
			$logoutUrl = $this->view->url(array('controller'=>'auth','action'=>'logout'), null, true);//url for log out
			$addUrl = $this->view->url(array('controller'=>'index','action'=>'add'),null,true); //url for main add link/folder action
			
			$return_string = '<nav><ul>';
            if($controller == 'index' && $action == 'index') {  //on the main display page add link to add link/folder
				$return_string .= '<li id="left"><a href="' . $addUrl . '">Add Link or Folder to page</a></li>';
			}
			$return_string .= '<li id="right">Welcome ' . $username . '.&nbsp;&nbsp;&nbsp;<a href="' . $logoutUrl . '">logout</a></li>';
			$return_string .= '</ul></nav>';
			return $return_string;
		}

        //if in contoller auth and on index this will be displayed
		if($controller == 'auth' && $action == 'index') {
			$signupUrl = $this->view->url(array('controller'=>'auth','action'=>'signup'),null, true);
			return '<div id="center"><a href="'.$signupUrl.'">Click here to Sign Up!</a></div>';
		}

		//if in auth and sign up dont display anything
		if($controller == 'auth' && $action == 'signup') {
			return '';
		}

		//default action
		$loginUrl = $this->view->url(array('controller'=>'auth','action'=>'index'));
		return '<a href="' . $loginUrl . '">Login</a>';
	}
}
