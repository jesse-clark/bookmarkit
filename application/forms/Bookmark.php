<?php
/**
 * Purpose: form for bookmark submission
 *
 * @copyright Jesse Clark 2010
 * @author Jesse Clark <mail@jesseclark.me>
 * @version 1.0
 */ 
class Application_Form_Bookmark extends Zend_Form
{

    public function init()
    {
    	$this->setName('bookmark');

 		//hiddens should only be ints, if not someone is probably trying to inject sql 
		$link_id = new Zend_Form_Element_Hidden('link_id');
		$link_id->addFilter('Int'); 

		$parent_id = new Zend_Form_Element_Hidden('parent_id');
		$parent_id->addFilter('Int');

		$user_id = new Zend_Form_Element_Hidden('user_id');
		$user_id->addFilter('Int');

		$link = new Zend_Form_Element_Text('link');
		$link->setLabel('URL (leave blank to create folder)')
			 ->setRequired(false) //its ok for link to be blank, it will just make a folder
			 ->addFilter('StripTags')
			 ->addFilter('StringTrim');
		
		$metadata = new Zend_Form_Element_Text('metadata');
		$metadata->setLabel('Title')
				 ->setRequired(true) //there has to be some sort of title
				 ->addFilter('StripTags')
				 ->addFilter('StringTrim')
				 ->addValidator('NotEmpty');

		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setAttrib('link_id', 'submitbutton');

		$this->addElements(array($link_id,$parent_id,$user_id,$link,$metadata,$submit));
    }


}

