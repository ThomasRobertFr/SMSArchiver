<?php
/*  Author: Thomas Robert - thomas-robert.fr - Github @ThomasRobertFr
    
    This file is part of SMSArchiver.

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

namespace ThomasR\SMSArchiver\Model;

class MySQL {

	const DB = 'sms';
	const USER = 'root';
	const PASS = '';
	const HOST = 'localhost';
	const TBL_MESSAGES = 'messages';
	const TBL_CONTACTS = 'contacts';

	private static $mysql;
	private static $insertMsg;
	private static $insertCtc;
	private static $tableMsg;
	private static $tableCtc;

	public static function get() {
		if (!isset(self::$mysql)) {
			self::$mysql = new \PDO('mysql:host='.self::HOST.';dbname='.self::DB, self::USER, self::PASS, array( \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
		}

		return self::$mysql;
	}

	private static function createMessagesTable($table) {
		if (!isset($tableMsg)) {
			$tableMsg = true;
			return self::get()->query('CREATE TABLE IF NOT EXISTS `'.mysql_real_escape_string($table).'` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`timestamp` int(11) NOT NULL,
					`phone` varchar(15) NOT NULL,
					`direction` enum(\'in\',\'out\') NOT NULL,
					`message` text NOT NULL,
					PRIMARY KEY (`id`)
				)');
		}
		return true;
	}

	private static function createContactsTable($table) {
		if (!isset($tableCtc)) {
			$tableCtc = true;
		self::get()->query('CREATE TABLE IF NOT EXISTS `'.mysql_real_escape_string($table).'` (
				`id` smallint(6) NOT NULL AUTO_INCREMENT,
				`name` varchar(60) NOT NULL,
				`phone` varchar(15) NOT NULL,
				PRIMARY KEY (`id`)
			)');
		}
		return true;
	}

	public static function insertMessages($data, $insertInto = self::TBL_MESSAGES) {
		
		self::createMessagesTable($insertInto);
		self::createQueryMessage($insertInto);

		foreach($data as $d) {
			if (!self::__insertMessage($d))
				return false;
		}

		return true;
	}

	public static function insertContacts($data, $insertInto = self::TBL_CONTACTS) {
		
		self::createContactsTable($insertInto);
		self::createQueryContact($insertInto);

		foreach($data as $d) {
			if (!self::__insertContact($d))
				return false;
		}

		return true;
	}

	public static function insertMessage($data, $insertInto = self::TBL_MESSAGES) {
		
		self::createMessagesTable($insertInto);
		self::createQueryMessage($insertInto);

		return self::__insertMessage($data);
	}

	public static function insertContact($data, $insertInto = self::TBL_CONTACTS) {
		
		self::createContactsTable($insertInto);
		self::createQueryContact($insertInto);

		return self::__insertContact($data);
	}

	private static function createQueryContact($insertInto = self::TBL_CONTACTS) {
		if (!isset(self::$insertCtc)) {
			self::$insertCtc = self::get()->prepare('INSERT INTO `'.mysql_real_escape_string($insertInto).'`(name, phone) VALUES (:name, :phone)');
		}
	}

	private static function createQueryMessage($insertInto = self::TBL_MESSAGES) {
		if (!isset(self::$insertMsg)) {
			self::$insertMsg = self::get()->prepare('INSERT INTO `'.mysql_real_escape_string($insertInto).'`(timestamp, phone, direction, message) VALUES (:timestamp, :phone, :direction, :message)');
		}
	}

	private static function __insertMessage($sms) {

		return self::$insertMsg->execute(array(
				':phone' => $sms['phone'],
				':timestamp' => $sms['timestamp'],
				':direction' => $sms['direction'],
				':message' => $sms['message'])
			);
	}

	private static function __insertContact($contact) {

		return self::$insertCtc->execute(array(
			':phone' => $contact['phone'],
			':name' => (isset($contact['name']) && $contact['name'] != '' ? $contact['name'] : $contact['phone'])
		));
	}

	public static function getPhones($readFrom = self::TBL_CONTACTS) {
		
		return self::get()->query('SELECT DISTINCT phone FROM `'.mysql_real_escape_string($readFrom).'`');
	}

	public static function getContacts() {
		
		return self::get()->query(
			'SELECT
				name,
				COUNT(*) AS nb,
				MAX(timestamp) AS lastsms,
				COUNT(*) - LOG(
					DATEDIFF(
						(SELECT FROM_UNIXTIME(MAX(timestamp)) FROM '.self::TBL_MESSAGES.'),
						FROM_UNIXTIME(MAX(timestamp))
					) + 1
				) * 370 AS score
			FROM '.self::TBL_CONTACTS.' as c
			LEFT JOIN '.self::TBL_MESSAGES.' AS m ON m.phone = c.phone
			GROUP BY name
			ORDER BY score DESC');
	}

	public static function getContactNbAndLastSMS($contact) {
		
		$data = self::get()->query(
			'SELECT
				COUNT(*) AS nb,
				MAX(timestamp) AS lastsms
			FROM '.self::TBL_CONTACTS.' as c
			LEFT JOIN '.self::TBL_MESSAGES.' AS m ON m.phone = c.phone
			WHERE name = "'.mysql_real_escape_string($contact).'"');
		foreach ($data as $d) break;
		return $d;
	}

	private static function getAllPhoneNumbers() {
		$data = self::get()->query('SELECT phone
			FROM '.self::TBL_CONTACTS);

		$out = array();
		foreach ($data as $d) {
			$out[] = $d['phone'];
		}
		return $out;
	}

	public static function getPhoneNumbers($contact = '') {
		
		if (empty($contact))
			return self::getAllPhoneNumbers();

		$data = self::get()->query('SELECT phone
			FROM '.self::TBL_CONTACTS.'
			WHERE name="'.mysql_real_escape_string($contact).'"');

		$out = array();
		foreach ($data as $d) {
			$out[] = $d['phone'];
		}
		return $out;
	}

	public static function getDistinctMessages($readFrom = self::TBL_MESSAGES) {
		
		return self::get()->query('SELECT MIN(id), timestamp, phone, direction, message
			FROM `'.mysql_real_escape_string($readFrom).'`
			GROUP BY timestamp, phone, direction, message
			ORDER BY phone, timestamp');
	}

	public static function getMessagesBtwTimeBounds($min, $max, $readFrom = self::TBL_MESSAGES) {
		
		return self::get()->query('SELECT *
			FROM `'.mysql_real_escape_string($readFrom).'`
			WHERE timestamp >= '.((int) $min).'
			AND timestamp <= '.((int) $max));
	}

	public static function getMessages($contact, $page, $smsPerPage, $smsSuppl, $readFrom = self::TBL_MESSAGES) {
		$begin = ($page - 1) * $smsPerPage;
		$length = $smsPerPage + $smsSuppl;

		return self::get()->query(
			'SELECT timestamp, direction, message FROM contacts AS c
			LEFT JOIN `'.mysql_real_escape_string($readFrom).'` AS m ON m.phone = c.phone
			WHERE name = "'.mysql_real_escape_string($contact).'"
			ORDER BY timestamp ASC
			LIMIT '.$begin.','.$length);
	}
}