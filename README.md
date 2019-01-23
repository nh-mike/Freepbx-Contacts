# freepbx-contacts

An address book for Free PBX with XML and LDAP powers!

This module utilises the FOP2 address book table installed in the asterisk
database with FOP2's installation.

Simply upload the contacts directory to your FreePbx modules directory, usually
located at /var/www/html/admin/modules/ and then enable the module in the Module
Admin panel in the Applications group.

Some configuration will be required to enable the various export options, XML or
LDAP. The tested environment exported the XML file to /tftpboot/phonebook.xml
and to a Red Hat 389 LDAP server running locally on the FreePbx server.

Currently, configuration is done by modifying the export.php file (for XML) or
inside the __construct function (for LDAP). In the future, I intend to present
the system administrator with a settings page where these options may be
inserted for the sanity of the system administrator and such as to keep the
code base unmodified (much easier for upgrades).

If you wish to export to XML, configure the export.php file and then set it up
to run on cron. This will be changed in a future update to update the XML file
upon every update to the address book.

If you wish to export to LDAP, configure inside the __construct function. All
LDAP updates will be performed upon every update of the address book.

Future updates will include:
* Integration of the XML export implementation
* A settings page for system administrators
* Module Signing? Maybe.
