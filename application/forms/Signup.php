<?php
/**
 * Purpose: form for signup
 *
 * @copyright Jesse Clark 2010
 * @author Jesse Clark <mail@jesseclark.me>
 * @version 1.0
 */ 
class Application_Form_Signup extends Zend_Form
{

    public function init()
    {
    	$this->setName('signup');
        
		//db_validator used in username to make sure the username doesnt already exist
        $db_validator = new Zend_Validate_Db_NoRecordExists(array('table'=>'users',
																'field'=>'username'));
		$db_validator->setMessage('User name is already in use. Please select another user name');

		$username = new Zend_Form_Element_Text('username');
		$username->setLabel('username')
				 ->setRequired(true)
				 ->addFilters(array('StringTrim','StringToLower'))
				 ->addValidator('StringLength', false, array(0,50))
				 ->addValidator($db_validator);

        $password = new Zend_Form_Element_Password('password');
		$password->setLabel('password')
				 ->setRequired(true)
				 ->addFilter('StringTrim')  //if you decide to change this requirement, remove the requirement from login
				 ->addValidator('StringLength',false,array(0,50));

        $password2 = new Zend_Form_Element_Password('password2');
		$password2->setLabel('re-enter password')
				 ->setRequired(true)
				 ->addFilter('StringTrim')
				 ->addValidator('StringLength',true,array(0,50))
				 ->addValidator('identical',true, array('token'=>'password',
				 									    'messages'=>'Passwords do not match, please re-enter your password'));
				 //addValidator method for customizing error message displays only for this error, not for others

		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('Join')
			   ->setRequired(false)
			   ->setIgnore(true);
		
		$this->addElements(array($username,$password,$password2,$submit)); 
    }


}

