<?php

/**
 * I aim to add support for publishing the FOP2 (Flash Operator Panel) address
 * book (contacts) to Grandstream handsets. Integrated into the FreePbx web UI
 * with LDAP generation performed upon modification to the address book
 */

namespace FreePBX\modules\contacts;

use \Exception;

class ldapExport
{
    public $use;

    protected $user;
    protected $pass;
    protected $host;
    protected $port;
    protected $root;
    protected $link;
    protected $map;
	protected $protocolversion;

    public function __construct($freepbx = null)
    {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->FreePBX = $freepbx;

    	$this->use = true;
		$this->user = 'cn=ldapuser,dc=ldapsvr,dc=domain,dc=local';
		$this->pass = 'password';
		$this->host = 'localhost';
		$this->port = 389;
		$this->root = 'ou=contacts,dc=ldapsvr,dc=domain,dc=local';
		$this->protocolversion = 3;
        $this->link = null;
		$this->map = [
			'id' => 'uid',
			'firstname' => 'givenname',
			'lastname' => 'sn',
			'fullname' => 'cn',
			'organisation' => 'o',
			'landline' => 'homephone',
			'mobile' => 'mobile',
			'email' => 'mail',
            'objectClass' => [
                'top',
                'person',
                'organizationalPerson',
                'inetorgperson'
            ]
		];

        if ($this->use) {
            $this->connect();
        }
    }

    public function __destruct()
    {
        if ($this->use) {
            ldap_close($this->link);
        }
    }

    /**
	 * Separated from the constructor for the sake of tidiness, initiates and
	 * binds to LDAP. Also creates the endpoint if it does not exist.
	 */
	public function connect()
    {
        if (!$this->use) {
            return false;
        }

		$this->link = ldap_connect($this->host, $this->port);

		if ($this->link === false) {
			throw new Exception(sprintf(
				'LDAP connection details incorrect %s:%i',
				$this->host, $this->port
			));
		}

		ldap_set_option(
            $this->link, LDAP_OPT_PROTOCOL_VERSION, $this->protocolversion);

		$ldapBind = ldap_bind($this->link, $this->user, $this->pass);

		if ($ldapBind === false) {
			if (
				!ldap_get_option(
					$this->link,
					LDAP_OPT_DIAGNOSTIC_MESSAGE,
					$error)
			) {
				$error = 'No additional information is available';
			}
			throw new Exception(sprintg(
				'Unable to bind to LDAP %s:%i with username %s\n%s',
				$this->host,
				$this->port,
				$this->user,
				$error
			));
		}

		$rootParent = trim(
            substr($this->root, strpos($this->root, ',')+1));

        $rootChild = explode('=', trim(substr(
            $this->root, 0, strpos($this->root, ','))));

        $list = ldap_list($this->link, $rootParent,
            $rootChild[0].'='.$rootChild[1], [$rootChild[0]]);

        $entries = ldap_get_entries($this->link, $list);
        if ($entries['count'] === 0) {
            $contactsOu = [
                'objectclass' => [
                    'top',
                    'organizationalunit'
                ],
                $rootChild[0] => $rootChild[1]
            ];
            $add = ldap_add(
                $this->link, $this->root, $contactsOu);

            if (!$add) {
                throw new Exception(sprintf(
                    'Root path %s does not exist and could not be created.',
                    $this->root
                ));
            }
        }
	}

    /**
	 * Add a contact to LDAP
	 *
	 * @param array $contact The data for the contact we wish to add
	 * @param string $id The ID number which will be associated with the contact
	 * @throws Exception
	 */
	public function addToLdap($contact, $id) {
        $location = sprintf(
            '%s=%s, %s',
            $this->map['id'],
            $id,
            $this->root
        );

        $info = $this->getLdapDataArray($contact);

        return ldap_add(
            $this->link,
            $location,
            $info
        );
    }

    /**
	 * Clears the LDAP directory
	 */
	protected function deleteAllFromLdap() {
        $list = ldap_list($this->link, $this->root, "ObjectClass=*",array(""));
        $entries = ldap_get_entries($this->link, $list);
        unset($entries['count']);
        foreach ($entries as $entry) {
            ldap_delete($this->link, $entry['dn']);
        }
	}

    /**
	 * Delete a single entry from LDAP by the given ID
	 *
	 * @param string $id
     * @return bool Whether the function suceeded
	 */
	public function deleteFromLdap($id) {
		$location = sprintf(
			'%s=%s,%s',
			$this->map['id'],
			$id,
			$this->root
		);

		$filter = '(|(uid='.$id.'))';
		$attrs = ['inetorgperson'];
		$search = ldap_search(
			$this->link, $this->root, $filter, $attrs);

		$entries = ldap_get_entries($this->link, $search);

		if ($entries['count'] !== 0) {
			return ldap_delete($this->link, $location);
		}
		return true;
	}

    /**
     * Takes an input contact and returns an array fit for LDAP
     *
     * @param array $contact
     * @return array
     */
    protected function getLdapDataArray($contact) {
        $c = [];
        foreach ($contact as $key => $data) {
            $c[strtolower($key)] = $contact[$key];
        }
        $contact = $c;

        $info = [
            'objectclass' => $this->map['objectClass']
        ];

        $keys = [
            'firstname',
            'lastname',
            'organisation',
            'landline',
            'mobile',
            'email'
        ];

        foreach ($keys as $key) {
            if (!empty($contact[$key])) {
                $info[$this->map[$key]] = $contact[$key];
            }
        }

        if (
            empty($contact['firstname']) &&
            empty($contact['lastname']) &&
            empty($contact['organisation'])
        ) {
            throw new Exception('No first name, last name or organisation provided');
        } elseif (
            empty($contact['firstname']) &&
            empty($contact['lastname'])
        ) {
            $info[$this->map['fullname']] = $contact['organisation'];
            $info[$this->map['lastname']] = ' ';
            $info[$this->map['firstname']] = $contact['organisation'];
        } elseif (empty($contact['firstname'])) {
            $info[$this->map['firstname']] = ' ';
            $info[$this->map['fullname']] = $contact['organisation'];
        } elseif (empty($contact['lastname'])) {
            $info[$this->map['lastname']] = ' ';
            $info[$this->map['fullname']] = $contact['organisation'];
        } else {
            $info[$this->map['fullname']] =
                $contact['firstname'].' '.$contact['lastname'];
        }

        $info['description'] = $this->stringToDialpad(
            $contact['firstname'].' '.
            $contact['lastname'].' '.
            $contact['organisation']
        );

        return $info;
    }

	/**
	 * Clears LDAP database and then populates with all the current entries from
	 * FreePBX database
	 */
	public function populateLdap($data) {
		$this->deleteAllFromLdap();

		foreach ($data as $contact) {
			$this->addToLdap($contact, $contact['id']);
		}
	}

    /**
	* Convert a string to the numbers required when searching on a dial pad
	* This will create a copy of the string with spaces and then a copy appended
	* to the end without spaces
	*
	* @param string $string The input string to convert
	* @return string The converted string
	*/
	private function stringToDialpad($string) {
        $map = [
            ['0'],
            ['1',' ','.','/','\'','`','@','*','-','=',',','|','&','?','!','%','(',')','~','_','<','>','{','}','[',']','^',],
            ['2','a','b','c','A','B','C'],
            ['3','d','e','f','D','E','F'],
            ['4','g','h','i','G','H','I'],
            ['5','j','k','l','J','K','L'],
            ['6','m','n','o','M','N','O'],
            ['7','p','q','r','s','P','Q','R','S'],
            ['8','t','u','v','T','U','V'],
            ['9','w','x','y','z','W','X','Y','Z']
        ];
		$string = $string.str_replace(' ', '', $string);
        $dialpadString = '';
        $stringArr = str_split($string);
        foreach($stringArr as $char) {
            foreach($map as $index => $value) {
                if (in_array($char, $value, true)) {
                    $dialpadString .= $index;
                }
            }
        }
        return $dialpadString;
    }
}
