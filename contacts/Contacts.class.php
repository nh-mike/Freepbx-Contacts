<?php
namespace FreePBX\modules;

require_once(__DIR__.'/ldapExport.php');
require_once(__DIR__.'/xmlExport.php');

/**
 * Create an Contacts class for the module
 *
 * @author Michael Thompson <michaelt@citytoyota.net.au>
 */

class Contacts implements \BMO {
	public $URLs = null;
	public $ldap = null;
	public $xml = null;
	public $regexes = null;

	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new \Exception("Not given a FreePBX Object");
		}
		$this->FreePBX = $freepbx;
		$this->URLs = array(
			'addContactAjax' => 'ajax.php?module=contacts&command=addContact',
			'getContactsAjax' => 'ajax.php?module=contacts&command=getContacts',
			'updateContactAjax' => 'ajax.php?module=contacts&command=setContact',
			'deleteContactsAjax' => 'config.php?display=contacts&command=delContact',
			'displayContactsURL' => 'config.php?display=contacts',
			'editContactURL' => '?display=contacts&editId='
		);

		$this->ldap = new \FreePBX\modules\contacts\ldapExport($freepbx);
		$this->xml = new \FreePBX\modules\contacts\xmlExport($freepbx);
		$this->regexes = array(
			'landline' => '^(\({0,1}((0|\+61)(2|3|7|8|)){0,1}\){0,1}[0-9]{8}|1[3|8][0-9]{4,8}|)$',
			'mobile' => '^((0|\+61)4) {0,1}[0-9]{2} {0,1}[0-9]{3} {0,1}[0-9]{3}$',
			'email' => '^([a-zA-Z0-9_\-\.]+)@([a-zA-Z0-9_\-\.]+)\.([a-zA-Z]{2,5})$',
			'firstname' => '^[a-zA-Z0-9 ]+$',
			'lastname' => '^[a-zA-Z0-9 ]+$',
			'organisation' => '^[ -*,-;=?-Z\\_a-}]+$'
			/**For a list of all characters allowed in an organisation name, look here:
			http://www.castlecorp.com.au/faq/valid-characters-asic-allows-company-name/*/
		);
	}
	//Install method. use this or install.php using both may cause weird behavior
	public function install() {
		$db = $this->FreePBX->Database->prepare(
			"CREATE TABLE IF NOT EXISTS `visual_phonebook` (\n".
			"  `id` int(11) NOT NULL,\n".
			"  `firstname` varchar(50) DEFAULT NULL,\n".
			"  `lastname` varchar(50) DEFAULT NULL,\n".
			"  `company` varchar(100) DEFAULT NULL,\n".
			"  `phone1` varchar(50) DEFAULT NULL,\n".
			"  `phone2` varchar(50) DEFAULT NULL,\n".
			"  `owner` varchar(50) DEFAULT '',\n".
			"  `private` enum('yes','no') DEFAULT 'no',\n".
			"  `picture` varchar(100) DEFAULT NULL,\n".
			"  `email` varchar(150) DEFAULT '',\n".
			"  `address` varchar(150) DEFAULT '',\n".
			"  `context` varchar(150) DEFAULT ''\n".
			") ENGINE=MyISAM AUTO_INCREMENT=247 DEFAULT CHARSET=utf8;"
		);
		$db->execute();
	}
	//Uninstall method. use this or install.php using both may cause weird behavior
	public function uninstall() {}
	//Not yet implemented
	public function backup() {}
	//not yet implimented
	public function restore($backup) {}
	//process form
	public function doConfigPageInit($page) {}
	//This shows the submit buttons
	public function getActionBar($request) {
		$buttons = array();
		if ( isset($_GET['editId']) ) {
			$buttons = array(
				'delete' => array(
					'name' => 'delete',
					'id' => 'delete',
					'value' => _('Delete')
				),
				'reset' => array(
					'name' => 'reset',
					'id' => 'reset',
					'value' => _('Reset')
				),
				'submit' => array(
					'name' => 'submit',
					'id' => 'submit',
					'value' => _('Submit')
				)
			);
		} elseif (
			(isset($_GET['command']) &&
			$_GET['command'] == 'addContact')
		) {
			$buttons = array(
				'reset' => array(
					'name' => 'reset',
					'id' => 'reset',
					'value' => _('Reset')
				),
				'submit' => array(
					'name' => 'submit',
					'id' => 'submit',
					'value' => _('Submit')
				)
			);
		}
		return $buttons;
	}
	public function showPage() {
		if (isset($_GET['command'])) {
			switch($_GET['command']) {
				case 'addContact':
					$vars = array(
						'contact' => $this->getContact(-1),
						'urls' => $this->URLs,
						'regexes' => $this->regexes
					);
					return load_view(__dir__.'/views/editcontact.php', $vars);
					break;
				case 'delContact':
					if ( !isset($_GET['id']) ) {
						throw new \Exception('ID not defined');
					}
					$this->deleteContact($_GET['id']);
					header('Location: '.$this->URLs['displayContactsURL']);
					break;
				case 'reloadLdap':
					$phonebookData = $this->getPhonebookDataFromDatabase();
					$this->ldap->populateLdap($phonebookData);
					return $this->loadMainView();
					break;
				case 'recreateXml':
					if ($this->xml->use) {
						$this->xml->create();
					}
					return $this->loadMainView();
					break;
			}
		} elseif ( isset($_GET['editId']) ) {
			$vars = array(
				'contact' => $this->getContact((int) $_GET['editId']),
			 	'urls' => $this->URLs,
				'regexes' => $this->regexes
			);
			return load_view(__dir__.'/views/editcontact.php', $vars);
		} else {
			return $this->loadMainView();
		}
	}
	public function ajaxRequest($req, &$setting) {
		switch ($req) {
			case 'addContact':
				return true;
				break;
			case 'getContacts':
				return true;
				break;
			case 'setContact':
				return true;
				break;
			case 'delContact':
				return true;
				break;
		}
		return false;
	}
	public function ajaxHandler(){
		switch ($_REQUEST['command'] ) {
			case 'addContact':
				$ret = json_encode($this->addContact($_POST['contact']));
				return $ret;
				break;
			case 'getContacts':
				$ret = json_encode($this->getPhonebookDataFromDatabase());
				return $ret;
				break;
			case 'setContact':
				return json_encode($this->updateContact($_POST['contact']));
				break;
			case 'delContact':
				if ( !isset($_GET['id']) ) {
					return json_encode(false);
				}
				return json_encode($this->deleteContact($_GET['id']));
				break;
		}
		return false;
	}
	//You can return HTML from here to be displayed in the right popout navigation
	public function getRightNav($request) {
		if ( file_exists(__DIR__.'/views/rightnav.php') ) {
			$vars = array('urls' => $this->URLs);
			return load_view(__DIR__.'/views/rightnav.php', $array);
		}
		return false;
	}
	/**
	* The main view from which we can see what contacts are added and perform
	* basic modification tasks
	*/
	protected function loadMainView() {
		$vars = array(
			'rawcontacts' => $this->getPhonebookDataFromDatabase(),
			'urls' => $this->URLs
		);
		return load_view(__DIR__.'/views/contacts.php',$vars);
	}

	/**
	* Create a contact in the database given the information
	*
	* @param array $contact
	*/
	private function addContact($contact) {
		if (
			strlen($contact['landline'])>0 &&
			preg_match('/'.$this->regexes['landline'].'/', $contact['landline']) !== 1
		) {
			return [false, 'Failed to add contact because the landline number is invalid'];
		} elseif (
			strlen($contact['mobile'])>0 &&
			preg_match('/'.$this->regexes['mobile'].'/', $contact['mobile']) !== 1
		) {
			return [false, 'Failed to add contact because the mobile number is invalid'];
		} elseif (
			strlen($contact['email'])>0 &&
			preg_match('/'.$this->regex['email'].'/', $contact['email']) !== 1
		) {
			return [false, 'Failed to add contact because the email address is invalid'];
		} elseif (
			(
				preg_match('/'.$this->regexes['firstname'].'/', $contact['firstname']) !==1 ||
				preg_match('/'.$this->regexes['lastname'].'/', $contact['lastname']) !==1
			) &&
			preg_match('/'.$this->regexes['organisation'].'/', $contact['organisation']) !==1
		) {
			return [false, 'Full name or Organisation name is required'];
		}

		$sql = 'INSERT INTO `visual_phonebook` (`firstname`, `lastname`, `company`, `phone1`, `phone2`, `email`)'.
		'VALUES (:fname, :lname, :org, :lphone, :mphone, :email);';
		$db = $this->FreePBX->Database->prepare($sql);

		$result = $db->execute(array(
			':fname'	=> $contact['firstname'],
			':lname'	=> $contact['lastname'],
			':org'		=> $contact['organisation'],
			':lphone'	=> $contact['landline'],
			':mphone'	=> $contact['mobile'],
			':email'	=> $contact['email']
		));

		if ($this->ldap->use) {
			$ldapAdd = $this->ldap->addToLdap($contact, $this->FreePBX->Database->lastInsertId());
			if (!$ldapAdd) {
				return [$ldapAdd, 'Failed to add to LDAP'];
			}
		}

		if ($this->xml->use) {
			$this->xml->create();
		}

		return [$result, ''];
	}
	/**
	* Update a contact in the database given the provided id
	*
	* @param array $contact
	*/
	private function updateContact($contact) {
		if (
			strlen($contact['landline'])>0 &&
			preg_match('/'.$this->regexes['landline'].'/', $contact['landline']) !== 1
		) {
			return [false, 'Failed to add contact because the landline number is invalid'];
		} elseif (
			strlen($contact['mobile'])>0 &&
			preg_match('/'.$this->regexes['mobile'].'/', $contact['mobile']) !== 1
		) {
			return [false, 'Failed to add contact because the mobile number is invalid'];
		} elseif (
			strlen($contact['email'])>0 &&
			preg_match('/'.$this->contact['email'].'/', $contact['email']) !== 1
		) {
			return [false, 'Failed to add contact because the email address is invalid'];
		} elseif (
			(
				preg_match('/'.$this->regexes['firstname'].'/', $contact['firstname']) !==1 ||
				preg_match('/'.$this->regexes['lastname'].'/', $contact['lastname']) !==1
			) &&
			preg_match('/'.$this->regexes['organisation'].'/', $contact['organisation']) !==1
		) {
			return [false, 'Full name or Organisation name is required'];
		}

		$sql = 'UPDATE `visual_phonebook` SET `firstname`=:fname,`lastname`=:lname,`company`=:org,`phone1`=:lphone,`phone2`=:mphone,`email`=:email WHERE id = :idnum';
		$db = $this->FreePBX->Database->prepare($sql);

		$result = $db->execute(array(
			':idnum'	=> $contact['id'],
			':fname'	=> $contact['firstname'],
			':lname'	=> $contact['lastname'],
			':org'		=> $contact['organisation'],
			':lphone'	=> $contact['landline'],
			':mphone'	=> $contact['mobile'],
			':email'	=> $contact['email']
		));

		if ($this->ldap->use) {
			$delete = $this->ldap->deleteFromLdap($contact['id']);
			if (!$delete) {
				return $delete;
			}
			$add = $this->ldap->addToLdap($contact, $contact['id']);
			if (!$add) {
				return $add;
			}
		}

		if ($this->xml->use) {
			$this->xml->create();
		}

		return [$result,''];
	}
	/**
	* Pull a contact from the database given the provided id
	*
	* @param int $id
	*
	* @return array
	*/
	private function getContact($id) {
		if ($id == -1) {
			$data = array(
				'id' => -1,
				'FirstName' => '',
				'LastName' => '',
				'Organisation' => '',
				'Landline' => '',
				'MobilePhone' => '',
				'Email' => ''
			);
		} else {
			$sql = 'SELECT * FROM `visual_phonebook` WHERE id = :idnum';
			$db = $this->FreePBX->Database->prepare($sql);
			$db->execute(array(':idnum' => $id));
			$dbdata = $db->fetchAll(\PDO::FETCH_ASSOC);
			$data = array(
				'id' => $dbdata[0]['id'],
				'FirstName' => $dbdata[0]['firstname'],
				'LastName' => $dbdata[0]['lastname'],
				'Organisation' => $dbdata[0]['company'],
				'Landline' => $dbdata[0]['phone1'],
				'MobilePhone' => $dbdata[0]['phone2'],
				'Email' => $dbdata[0]['email']
			);
		}
		return $data;
	}
	/**
	* Pull an array of all contacts from the database
	*
	* @return array
	*/
	private function getPhonebookDataFromDatabase() {
		$sql = 'SELECT * FROM `visual_phonebook`';
		$db = $this->FreePBX->Database->prepare($sql);
		$db->execute();
		$dbdata = $db->fetchAll(\PDO::FETCH_ASSOC);
		$data = array();
		foreach ( $dbdata as $i => $datum ) {
			if (
			 	substr($datum['phone1'], 0, 1) == '9' ||
				substr($datum['phone1'], 0, 1) == '6'
			) {
				$datum['phone1'] = '08'.$datum['phone1'];
				$sql = 'UPDATE `visual_phonebook` SET `phone1` = "'.$datum['phone1'].'" WHERE `visual_phonebook`.`id` = '.$datum['id'];
				$this->FreePBX->Database->prepare($sql)->execute();
			}
			$data[$i] = array(
				'id' => $datum['id'],
				'FirstName' => (isset($datum['firstname']) ? $datum['firstname'] : ''),
				'LastName' => (isset($datum['lastname']) ? $datum['lastname'] : ''),
				'Organisation' => (isset($datum['company']) ? $datum['company'] : ''),
				'Landline' => (isset($datum['phone1']) ? $datum['phone1'] : ''),
				'Mobile' => (isset($datum['phone2']) ? $datum['phone2'] : ''),
				'Email' => (isset($datum['email']) ? $datum['email'] : '')
			);
		}
		return $data;
	}
	/**
	* Delete a contact from the database given the provided id
	*
	* @return bool
	*/
	private function deleteContact($id) {
		$sql = 'DELETE from `visual_phonebook` WHERE id = :idnum';
		$db = $this->FreePBX->Database->prepare($sql);
		if ( $db->execute(array(':idnum' => $id)) ) {
			return true;
		} else {
			return false;
		}
	}
}
