<?php
        /**
                This project is based on grintor/freepbx-grandstream-phonebook
                I aim to add support for publishing the FOP2 (Flash Operator Panel) address book (contacts) to Grandstream handsets
        **/
        //Some people wish to generate a PBX user list and some wish to create a phonebook so we will give them both options
        $tbl_users = false;
        $tbl_visualphonebook = false;
        //Some people wish to echo straight to stdout and others may wish to write to a web cache or TFTP directory. Ensure you have write permissions
        $print = false;
        $write = false;
        $writepath = '/tftpboot/phonebook.xml';
        require_once('/etc/freepbx.conf');
        if ( !$tbl_users && !$tbl_visualphonebook || !$print && !$write ) {
                trigger_error('Phonebook script has not been configured');
                echo    'Phonebook script has not been configured',PHP_EOL,
                        'Please modify this file and enable which tables and output methods',PHP_EOL,
                        'you would like to use for your phonebook.',PHP_EOL;
                exit;
        }
        $mysqli = new mysqli($amp_conf['AMPDBHOST'], $amp_conf['AMPDBUSER'], $amp_conf['AMPDBPASS'], $amp_conf['AMPDBNAME']);
        if ( $mysqli->connect_errno ) {
            trigger_error('Connect failed: %s\n', $mysqli->connect_error);
            exit;
        }
        if ( $print ) {
                if ( !isset($_SERVER['PHP_AUTH_USER']) ) {
                        httpAuthenticate();
                } else {
                        $PHP_AUTH_USER = $mysqli->real_escape_string($mysqli, $_SERVER['PHP_AUTH_USER']);
                        $userPasswordLookupResult = DBQuery("select * from sip where id='$PHP_AUTH_USER' and keyword='secret'");
                        if (!$userPasswordLookupResult || !$userPasswordLookupResult[0]['data'] == $_SERVER['PHP_AUTH_PW']) {
                                httpAuthenticate();
                        }
                }
        }
        $xml_obj = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><AddressBook />');
        if ( $tbl_users ) {
                foreach ( DBQuery('select * from users') as $x ){
                        $name = explode(" ", $x['name']);
                        $Contact = $xml_obj->addChild('Contact');
                        $FirstName = $Contact->addChild('FirstName', $name[0]);
                        if ($name[1]){
                                $LastName = $Contact->addChild('LastName', $name[1]);
                        }
                        $Phone = $Contact->addChild('Phone');
                        $phonenumber = $Phone->addChild('phonenumber', $x['extension']);
                        $accountindex = $Phone->addChild('accountindex', 1);
                }
        }
        if ( $tbl_visualphonebook ) {
                foreach ( DBQuery('select * from visual_phonebook') as $x ){
                        //Since this data is input by the dreaded end user, we should do some sort of sanity checking
                        $firstname = strlen(preg_replace('/\s+/', '', $x['firstname']))>0;
                        $lastname = strlen(preg_replace('/\s+/', '', $x['lastname']))>0;
                        $company = strlen(preg_replace('/\s+/', '', $x['company']))>0;
                        $phone1 = strlen(preg_replace('/\s+/', '', $x['phone1']))>0;
                        $phone2 = strlen(preg_replace('/\s+/', '', $x['phone2']))>0;
                        $Contact = $xml_obj->addChild('Contact');
                        if ( $firstname ) {
                                $FirstName = $Contact->addChild('FirstName', htmlspecialchars($x['firstname']));
                        }
                        if ( $lastname ) {
                                $LastName = $Contact->addChild('LastName', htmlspecialchars($x['lastname']));
                        }
                        if ( $company ) {
                                $Contact->addChild('Company', htmlspecialchars($x['company']));
                        }
                        if ( $phone1 ) {
                                $Phone1 = $Contact->addChild('Phone');
                                $Phone1->addAttribute('type', 'Work');
                                $phonenumber = $Phone1->addChild('phonenumber', htmlspecialchars($x['phone1']));
                                $accountindex = $Phone1->addChild('accountindex', 0);
                        }
                        if ( $phone2 ) {
                                $Phone2 = $Contact->addChild('Phone');
                                $Phone2->addAttribute('type', 'Cell');
                                $phonenumber = $Phone2->addChild('phonenumber', htmlspecialchars($x['phone2']));
                                $accountindex = $Phone2->addChild('accountindex', 0);
                        }
                        $Group = $Contact->addChild('Groups');
                        $Group->addChild('groupid', 2);
                }
        }
        //Now lets format and output our data as per our configuration
        $xmldata = $xml_obj->asXML();
        if ( $print ) {
                header('Content-type: application/xml');
                print formatXML($xmldata);
        }
        if ( $write ) {
                if ( !file_put_contents ( $writepath, $xmldata, LOCK_EX ) ) {
                        trigger_error("Unable to write file $writepath");
                }
        }
        function DBQuery ( $query ) {
                global $mysqli;
                if ( !$sqlResult = $mysqli->query($query) ) {
                        trigger_error('DB query failed: ' . $mysqli->error . "\nquery: " . $query);
                        return false;
                } else {
                        $all_rows = array();
                        while ($row = $sqlResult->fetch_assoc()) {
                                $all_rows[] = $row;
                        }
                        return $all_rows;
                }
        }

        function formatXML ( $xml ) {
                $dom = new DOMDocument;
                $dom->preserveWhiteSpace = FALSE;
                $dom->loadXML($xml);
                $dom->formatOutput = TRUE;
                return $dom->saveXml();
        }
        function httpAuthenticate() {
                global $print;
                header('WWW-Authenticate: Basic realm="My Realm"');
                header('HTTP/1.0 401 Unauthorized');
                echo '401 Unauthorized';
                $print = false;
        }
