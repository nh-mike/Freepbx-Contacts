<?php
	/**
	 * You can call this file from the CLI in order to perform export functions
	 * manually. This may be useful in instances such as running the export
	 * function on a cron job, or if you are afraid of web interfaces.
	 */

	require_once('/etc/freepbx.conf');
	require_once(__DIR__.'/xmlExport.php');
	require_once(__DIR__.'/ldapExport.php');

	$exportOptions = [
		'xml' => true,
		'ldap' => false
	];

	foreach($exportOptions as $option => $value) {
		if (!$value) {
			continue;
		}
		switch($option) {
			case 'xml':
				$xml = new \FreePBX\modules\contacts\xmlExport();
				if ($xml->use) {
					$xml->create();
				}
				break;
			case 'ldap':
				$ldap = new \FreePBX\modules\contacts\ldapExport();
				if ($ldap->use) {
					$ldap->create();
				}
				break;
		}
	}
