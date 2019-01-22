<?php

/**
 * This project is succeeds nh-mike/freepbx-grandstream-phonebook
 * I aim to add support for publishing the FOP2 (Flash Operator Panel) address
 * book (contacts) to Grandstream handsets. Integrated into the FreePbx web UI
 * with XML generation performed upon modification to the address book
 */

namespace FreePBX\modules\contacts;

use \Exception;

class xmlExport
{
    public $use;

    //Internal flag set to true if we are operating in the CLI
    protected $is_cli;
    //For users who wish to add internal PBX contacts
    protected $tblUsers;
    //For users who wish to add Fop2 contacts
    protected $tblVisualPhonebook;
    //For users who wish to echo out (not suited for XML generation)
    protected $print;
    /* For users who wish to write to the filesystem. Only respected when the
       export file is called directly from the CLI */
    protected $write;
    //Path to write the file to
    protected $writePath;

    public function __construct($freepbx = null)
    {
        $this->is_cli = PHP_SAPI === 'cli';

		if ($freepbx != null) {
            $this->FreePBX = $freepbx;
			$this->PDO = $this->FreePBX->Database;
		} else {
            $this->PDO = new \FreePbx\Database();
        }

        $this->use = true;
        $this->tblUsers = false;
        $this->tblVisualPhonebook = true;
        $this->print = false;
        $this->write = true;
        $this->writepath = '/tftpboot/phonebook.xml';

        //Check sanity of SysOp
        if (
            (!$this->tblUsers && !$this->tblVisualPhonebook) ||
            (!$this->print && !$this->write)
        ) {
            throw new \Exception(sprintf(
                'Phonebook script has not been configured.%sPlease configure '.
                'the xmlExport.php file in %s%sand enable which tables and '.
                'output methods%syou would like to use for your phonebook.',
                PHP_EOL,
                __DIR__,
                PHP_EOL,
                PHP_EOL
            ));
        }

        //Authenticate the user
        if (!$this->is_cli && $this->print) {
            if (!isset($_SERVER['PHP_AUTH_USER'])) {
                $this->httpAuthenticate();
            } else {
                $auth = [
                    'u' => $_SERVER['PHP_AUTH_USER'],
                    'p' => $_SERVER['PHP_AUTH_PW']
                ];

                $sql = 'select * from sip where id=`:authUser` and keyword=`secret`';
    			$db = $this->PDO->prepare($sql);
    			$db->execute(array(':authUser' => $auth['u']));

                $usrPwd = $db->fetchAll(\PDO::FETCH_ASSOC);
                if (!$usrPwd || !$usrPwd[0]['data'] == $auth['p']) {
                    httpAuthenticate();
                }
            }
        }
    }

    /**
     * Generate and output the XML as per configuration
     */
    public function create()
    {
        if (!$this->use) {
            return false;
        }

        $xmlObj = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><AddressBook />');

        if ($this->tblUsers) {
            $this->createTblUsers($xmlObj);
        }

        if ($this->tblVisualPhonebook) {
            $this->createTblVisualPhonebook($xmlObj);
        }

        $xmldata = $xmlObj->asXML();

        if ($this->print) {
            if (!$this->is_cli) {
                header('Content-type: application/xml');
            }
            echo $this->formatXML($xmldata);
        }

        if ($this->write) {
            $writeResult = file_put_contents (
                $this->writepath, $xmldata, LOCK_EX);
            if (!$writeResult) {
                throw new \Exception(sprintf(
                    "Unable to write file %s",
                    $writepath
                ));
            }
        }
    }

    /**
     * Populate the XML object with users from the PBX's own internal directory
     *
     * @param object $xmlObj Our SimpleXmlElement object
     */
    protected function createTblUsers(&$xmlObj)
    {
        $sql = 'select * from users';
        $db = $this->PDO->prepare($sql);
        $db->execute();

        $users = $db->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($users as $user){
            $name = explode(" ", $user['name']);
            $Contact = $xmlObj->addChild('Contact');
            $FirstName = $Contact->addChild('FirstName', $name[0]);
            if ($name[1]){
                $LastName = $Contact->addChild('LastName', $name[1]);
            }
            $Phone = $Contact->addChild('Phone');
            $phonenumber = $Phone->addChild('phonenumber', $user['extension']);
            $accountindex = $Phone->addChild('accountindex', 1);
        }
    }

    /**
     * Populate the XML object with contacts from the Fop2 Visual Phonebook
     * table
     *
     * @param object $xmlObj Our SimpleXmlElement object
     */
    protected function createTblVisualPhonebook(&$xmlObj)
    {
        $sql = 'select * from visual_phonebook';
        $db = $this->PDO->prepare($sql);
        $db->execute();

        $contacts = $db->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($contacts as $contact) {
            /*Since this data is input by the dreaded end user, we should do
            some sort of sanity checking to ensure it isn't all whitespace */
            $firstname =
                strlen(preg_replace('/\s+/', '', $contact['firstname']))>0;

            $lastname =
                strlen(preg_replace('/\s+/', '', $contact['lastname']))>0;

            $company = strlen(preg_replace('/\s+/', '', $contact['company']))>0;
            $phone1 = strlen(preg_replace('/\s+/', '', $contact['phone1']))>0;
            $phone2 = strlen(preg_replace('/\s+/', '', $contact['phone2']))>0;
            $contactelement = $xmlObj->addChild('Contact');

            if (!$firstname && !$lastname && $company) {
                $contactelement->addChild(
                    'FirstName', htmlspecialchars($contact['company']));

                $contactelement->addChild(
                    'Company', htmlspecialchars($contact['company']));
            } else {
                if ($firstname) {
                    $firstname = $contactelement->addChild(
                        'FirstName', htmlspecialchars($contact['firstname']));
                }
                if ($lastname) {
                    $lastname = $contactelement->addChild(
                        'LastName', htmlspecialchars($contact['lastname']));
                }
                if ($company) {
                    $contactelement->addChild(
                        'Company', htmlspecialchars($contact['company']));
                }
            }
            if ($phone1) {
                $phone1 = $contactelement->addChild('Phone');
                $phone1->addAttribute('type', 'Work');
                $phonenumber = $phone1->addChild(
                    'phonenumber', htmlspecialchars($contact['phone1']));

                $accountindex = $phone1->addChild(
                    'accountindex', 0);
            }
            if ($phone2) {
                $phone2 = $contactelement->addChild('Phone');
                $phone2->addAttribute('type', 'Cell');
                $phonenumber = $phone2->addChild(
                    'phonenumber', htmlspecialchars($contact['phone2']));

                $accountindex = $phone2->addChild('accountindex', 0);
            }
            $group = $contactelement->addChild('Groups');
            $group->addChild('groupid', 2);
        }
    }

    protected function formatXML ($xml) {
        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = FALSE;
        $dom->loadXML($xml);
        $dom->formatOutput = TRUE;
        return $dom->saveXml();
    }

    protected function httpAuthenticate() {
        header('WWW-Authenticate: Basic realm="My Realm"');
        header('HTTP/1.0 401 Unauthorized');
        echo '401 Unauthorized';
        $this->print = false;
    }
}
