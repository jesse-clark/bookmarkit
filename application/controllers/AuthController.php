<?php
/**
 * contains Login(indexAction), Logout, and Signup Actions.
 * 
 * @copyright Jesse Clark 2010
 * @author Jesse Clark <mail@jesseclark.me>
 * @version 1.0
 */
class AuthController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    /**
	 * indexAction loads the Login form to the screen.
	 *
	 * If a user is signed in it will redirect them to the indexAction of the Index Controller.  
	 */
    public function indexAction()
    {
        $this->_redirectIfLoggedIn();
        $form = new Application_Form_Login();
        $request = $this->getRequest();
		$auth = new Application_Model_DbTable_Auth();
        if($request->isPost() && $form->isValid($request->getPost()) && $auth->_process($form->getValues()) ){
            $this->_helper->redirector('index','index');
        }
        $this->view->form = $form;
    }

	/**
	 * logoutAction logs out the logged in user then redirects to the login(indexAction) of Auth
	 */
    public function logoutAction()
    {
        Zend_Auth::getInstance()->clearIdentity();
        $this->_helper->redirector('index');
    }

	/**
	 * signupAction loads the Signup form to the screen.
	 *
	 * If the user is logged in it will redirect to the indexAction of the index controller.
	 * If the form is set to '' (empty string) the /view/scripts/singup.phtml will display 
	 * the successful sign up message.
	 */
    public function signupAction()
    {
        $this->_redirectIfLoggedIn();
        $form = new Application_Form_Signup();
        $request = $this->getRequest();
        if($request->isPost() && $form->isValid($request->getPost()))
        {
            $username = $request->getPost('username');
            $password = $request->getPost('password');
            $bookmarks = new Application_Model_DbTable_Auth();
            $bookmarks->addUser($username,$password);
            $this->view->form = ''; //forms value to blank  
        }else {
            $this->view->form = $form;
        }
    }

	/**
	 * Checks to see if a user is logged in, if so, it redirects them to the indexAction of the Index Controller
	 */
    private function _redirectIfLoggedIn()
    {
        $auth = Zend_Auth::getInstance();
        if($auth->hasIdentity()) {
            $this->_helper->redirector('index','index');
        }
    }


}

