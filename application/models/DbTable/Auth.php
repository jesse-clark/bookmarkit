<?php
/**
 * Purpose: model for User Authorization, includes addUser and _process
 *
 * @copyright Jesse Clark 2010
 * @author Jesse Clark <mail@jesseclark.me>
 * @version 1.0
 */ 
class Application_Model_DbTable_Auth extends Zend_Db_Table_Abstract
{

    protected $_name = 'users'; //table name

	/**
	 * add a user to the users database
	 *
	 * create a data array with username, 
	 * salt (sha1 of username, all salts will be unique but not super strong), 
	 * password hash of password with added salt. The database will be doing the SHA1 calculations.
	 */
	public function addUser($username,$password)
	{
    	$data = array(
					'username' => $username,
					'salt' => new Zend_Db_Expr("SHA1('$username')"),
					'passhash' => new Zend_Db_Expr("SHA1(CONCAT('$password',salt))"));
		$this->insert($data);
	}

	/**
	 * _process validates username password combo, if combo is valid  
	 *
	 * @param array $values - form values [username],[password]
	 * @return bool
	 */
    public function _process($values)
    {
        $adapter = $this->_getAuthAdapter();
        $adapter->setIdentity($values['username']);
        $adapter->setCredential($values['password']);

        $auth = Zend_Auth::getInstance();
        $result = $auth->authenticate($adapter);
        if($result->isValid()) {
            $user = $adapter->getResultRowObject();
            $auth->getStorage()->write($user);
            return true;
        }
        return false;
    }

	/**
	 * get and setup a Zend_Auth_Adapter
	 *
	 * @return Zend_Auth_Adapter_DbTable
	 */
    private function _getAuthAdapter()
    {
        $dbAdapter = Zend_Db_Table::getDefaultAdapter();
        $authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);

        $authAdapter->setTableName('users')
        			->setIdentityColumn('username')
					->setCredentialColumn('passhash')
					->setCredentialTreatment('SHA1(CONCAT(?,salt))');

        return $authAdapter;
    }  
}

