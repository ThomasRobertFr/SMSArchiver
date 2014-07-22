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

require_once './lib/Model.php';
use ThomasR\SMSArchiver\Model\MySQL;

/**
 * Simple tools class
 */
class Tools {

	public static function formatPhoneNumber($str) {

		$str = preg_replace('/[^0-9+]/', '', $str);
		// $str = preg_replace('/^0/', '+<YOUR INNATIONAL PREFIX>', $str);

		return $str;
	}

}

class NewImportTools {

	public static function getMinMaxTimestamp($data) {
		$min = INF;
		$max = 0;
		foreach ($data as $d) {
			if ($d['timestamp'] < $min)
				$min = $d['timestamp'];
			if ($d['timestamp'] > $max)
				$max = $d['timestamp'];
		}
		return array('min' => $min, 'max' => $max);
	}

	public static function importNewFile($filepath, $type) {

		if ($type == 'BackupRestore')
			$importer = new BackupRestore();
		elseif ($type == 'Menue')
			$importer = new Menue();
		else
			return 'Bad file';

		$out = '';

		$dumpMessages = $importer->getDataFromFile($filepath);

		$minmax = self::getMinMaxTimestamp($dumpMessages);

		$dbMessages = MySQL::getMessagesBtwTimeBounds($minmax['min'] - 300, $minmax['max'] + 300);

		$cleaner = new DBCleaning();
		$newMessages = $cleaner->substractDatasets($dumpMessages, $dbMessages);

		if (empty($newMessages)) {
			return 'No new messages in dump';
		}
		else {
			$phones = MySQL::getPhoneNumbers();

			foreach($newMessages as $sms) {
				if (!in_array($sms['phone'], $phones)) {
					MySQL::insertContact(array('phone' => $sms['phone']));
					$phones[] = $sms['phone'];
					$out .= 'New contact ('.$sms['phone'].'), please set name in database'."\n";
				}
			}

			if (MySQL::insertMessages($newMessages)) {
				$out .= count($newMessages).' messages inserted in database';
			}
			else {
				$out .= 'New contact ('.$sms['phone'].'), please set name in database';
			}

			return $out;
		}

	}

}

abstract class Importer {

	private $insertInto;
	private $query;

	public function __construct($insertInto = null) {
		$this->insertInto = $insertInto;
	}

	abstract protected function processSMS($sms);

	protected function insertRecord($sms) {
		if ($this->insertInto) {
			$sms = $this->processSMS($sms);
			return MySQL::insertMessage($sms, $this->insertInto);
		}
		else
			return false;
	}

	abstract public function importFile($path);
	abstract public function importDirectory($dir);
	abstract public function getDataFromFile($path);

}

/**
 * Class to import dumps from SMS Backup & Restore
 * https://play.google.com/store/apps/details?id=com.riteshsahu.SMSBackupRestore
 */
class BackupRestore extends Importer {

	protected function processSMS($sms) {
		$num = Tools::formatPhoneNumber($sms['address']);
		$dir = $sms['type'] == 2 ? 'out' : 'in';
		$timestamp = substr($sms['date'], 0, -3);
		$message = $sms['body'];
		
		return array(
				'phone' => $num,
				'timestamp' => $timestamp,
				'direction' => $dir,
				'message' => $message
			);
	}

	private function createXMLObject($data) {
		return new \SimpleXMLElement($data);
	}

	private function importXML($xml) {

		foreach ($xml->sms as $sms)
			if (!$this->insertRecord($sms))
				return false;

		return true;

	}

	public function getDataFromFile($path) {
		if (is_file($path)) {
			$out = array();
			$xml = $this->createXMLObject(file_get_contents($path));
			foreach ($xml->sms as $sms)
				$out[] = self::processSMS($sms);
			return $out;
		}
		
		return false;
	}

	public function importFile($path) {
		if (is_file($path)) {
			$xml = $this->createXMLObject(file_get_contents($path));
			return $this->importXML($xml);
		}
		
		return false;
	}

	public function importDirectory($dir) {

		foreach (new \DirectoryIterator($dir) as $file) {
			if($file->getExtension() != 'xml')
				continue;
			$res = $this->importFile($file->getPathname());
			if ($res == false)
				return false;
		}

		return true;
	}

}

/**
 * Class to import dumps from SMS Backup (dumps in the Menue folder of the phone)
 * https://play.google.com/store/apps/details?id=cn.menue.smsbackup.international
 */
class Menue extends Importer {

	protected function processSMS($sms) {
		$num = Tools::formatPhoneNumber($sms['sms_address']);
		$dir = $sms['sms_type'] == 2 ? 'out' : 'in';
		$timestamp = substr($sms['sms_date'], 0, -3);
		$message = $sms['sms_body'];
		
		return array(
			'phone' => $num,
			'timestamp' => $timestamp,
			'direction' => $dir,
			'message' => $message
		);
	}

	public function getDataFromFile($path) {
		$out = array();
		$db = new \PDO('sqlite:'.$path, null, null);
		$data = $db->query('SELECT sms_date, sms_address, sms_type, sms_body FROM sms');
		$data->setFetchMode(\PDO::FETCH_ASSOC);
		while($sms = $data->fetch())
			$out[] = self::processSMS($sms);
		
		return $out;
	}

	public function importFile($path) {
		$db = new \PDO('sqlite:'.$path, null, null);
		$data = $db->query('SELECT sms_date, sms_address, sms_type, sms_body FROM sms');
		$data->setFetchMode(\PDO::FETCH_ASSOC);
		while($sms = $data->fetch()) {
			$res = $this->insertRecord($sms);
			if ($res == false)
				return false;
		}
		return true;
	}

	public function importDirectory($dir) {

		foreach (new \DirectoryIterator($dir) as $file) {
			if($file->getExtension() != 'db')
				continue;
			
			$res = $this->importFile($file->getPathname());
			if ($res == false)
				return false;
		}

		return true;
	}


}

/**
 * Class to clean the database from duplicate SMS entries because dumps often overlap.
 */
class DBCleaning {

	private $maxQueueSize;
	private $queue;
	private $query;

	public function __construct($maxQueueSize = 3) {
		$this->queue = new \SplQueue();
		$this->maxQueueSize = $maxQueueSize;
	}

	private function addSMSInQueue($sms) {
		if ($this->maxQueueSize > 0 && $this->queue->count() >= $this->maxQueueSize) {
			$this->queue->dequeue();
		}
		$this->queue->enqueue($sms);
	}

	private static function compareStringsProximity($sms1, $sms2) {
		$percent;
		similar_text($sms1, $sms2, $percent);
		return $percent;
	}

	private function existSimilarInQueue($sms) {

		foreach ($this->queue as $oldSms) {
			if ($oldSms['phone'] == $sms['phone'] &&
				$oldSms['direction'] == $sms['direction'] &&
				abs($oldSms['timestamp'] - $sms['timestamp']) < 300 &&
				self::compareStringsProximity($sms['message'], $oldSms['message']) > 90) {
				return true;
			}
		}

		return false;
	}

	public function removeDuplicates($data) {
		$out = array();
		foreach($data as $sms) {
			if (!$this->existSimilarInQueue($sms))
				$out[] = $sms;
			$this->addSMSInQueue($sms);
		}
		return $out;
	}

	public function substractDatasets($data, $substract) {

		$this->maxQueueSize = 0;

		foreach($substract as $sms) {
			$this->addSMSInQueue($sms);
		}

		$out = array();
		foreach($data as $sms) {
			if (!$this->existSimilarInQueue($sms))
				$out[] = $sms;
		}
		return $out;
	}

	public function cleanTable($readFrom, $insertInto) {
		$data = MySQL::getDistinctMessages($readFrom);
		$data = $this->removeDuplicates($data);
		MySQL::insertMessages($data, $insertInto);
	}

}

/**
 * Class that list contacts from the SMS table and try to match contacts from a Gmail contacts extract.
 * It saves all contacts (matched or not) to a table
 */
class MatchContacts {

	public $phonebook;
	private $inds;
	private $query;

	public function __construct() {
		$this->phonebook = array();
	}

	private function loadGoogleContact($contact) {
		foreach(array('Phone 1 - Value', 'Phone 2 - Value', 'Phone 3 - Value') as $column) {
			$nums = explode(':::', $contact[$this->inds[$column]]);

			foreach ($nums as $num) {
				$num = Tools::formatPhoneNumber($num);
				if (!empty($num))
					$this->phonebook[$num] = $contact[$this->inds['Name']];
			}
			
		}
	}

	private function readCSVHeader($header) {
		$this->inds = array();
		for ($j = 0; $j < count($header); $j++) {
			$this->inds[$header[$j]] = $j;
		}
	}

	public function loadGoogleCSV($filepath) {

		if (!is_file($filepath))
			return false;

		$file = fopen($filepath, "r");

		if ($file === false)
			return false;

		$this->readCSVHeader(fgetcsv($file, 2000, ","));
		while (($contact = fgetcsv($file, 2000, ",")) !== FALSE)
			$this->loadGoogleContact($contact);

		fclose($file);
	}

	public function saveUsefulContacts($readFrom, $insertInto) {
		$data = MySQL::getPhones($readFrom);

		$insert = array();
		foreach($data as $phone) {
			$phone = $phone['phone'];
			if (!empty($this->phonebook[$phone])) {
				$insert[] = array('phone' => $phone, 'name' => $this->phonebook[$phone]);
			}
			else {
				$insert[] = array('phone' => $phone, 'name' => '');
				echo $phone.' not found...<br/>';
			}
		}

		MySQL::insertContacts($insert, $insertInto);
	}
}
