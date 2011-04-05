<?php
/**
 * Purpose: form for login
 *
 * @copyright Jesse Clark 2010
 * @author Jesse Clark <mail@jesseclark.me>
 * @version 1.0
 */ 
class Application_Form_Login extends Zend_Form
{

    public function init()
    {
    	$this->setName('login');

		$username = new Zend_Form_Element_Text('username');
		$username->setLabel('username')
				 ->setRequired(true)
				 ->addFilters(array('StringTrim','StringToLower'))
				 ->addValidator('StringLength', false, array(0,50));

        $password = new Zend_Form_Element_Password('password');
		$password->setLabel('password')
				 ->setRequired(true)
				 ->addFilter('StringTrim') //might remove this later
				 ->addValidator('StringLength',false,array(0,50));

		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('login')
			   ->setRequired(false)
			   ->setIgnore(true);//ignore the submit button
		
		$this->addElements(array($username,$password,$submit));
    }


}

