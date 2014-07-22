<?php

/*  Author: Thomas Robert - thomas-robert.fr - Github @ThomasRobertFr
    
    This file is part SMSArchiver.

    SMSArchiver is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    SMSArchiver is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along SMSArchiver.  If not, see <http://www.gnu.org/licenses/> */

namespace ThomasR\SMSArchiver\Import;

mb_internal_encoding("UTF-8");

require_once 'lib/Import.php';

define('DB_IMPORT_TO', 'messagesraw');
define('DB_CLEAN_TO', 'messages');
define('DB_CONTACTS', 'contacts');

try {
	// Import SMS Menue dumps
	$importer = new Menue(DB_IMPORT_TO);
	echo 'SMS Menue: ' . $importer->importDirectory('dumps/Sms Menue'). '<br/>';
	
	// Import SMS Backup & Restore dumps
	$importer = new BackupRestore(DB_IMPORT_TO);
	echo 'SMS Backup&Restore: ' . $importer->importDirectory('dumps/SMSBackupRestore').'<br/>';

	// Remove duplicate SMSes
	$clean = new DBCleaning();
	$clean->cleanTable(DB_IMPORT_TO, DB_CLEAN_TO);
	
	// Import Google Contacts and match SMSes
	$contacts = new MatchContacts();
	$contacts->loadGoogleCSV('dumps/google.csv');
	$contacts->saveUsefulContacts(DB_CLEAN_TO, DB_CONTACTS);
}
catch(Exception $e) {
	echo 'Error: '.$e->getMessage().' (code '.$e->getCode().')';
}