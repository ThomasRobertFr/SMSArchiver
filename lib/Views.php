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

namespace ThomasR\SMSArchiver\View;

require_once './lib/Model.php';
require_once './lib/Import.php';
use ThomasR\SMSArchiver\Model\MySQL;
use ThomasR\SMSArchiver\Import\NewImportTools;

/**
 * Class that handles the web interface. It actually does way way more that the views in MVC / MVT...
 */
class MessagesView {

	private static $MONTH_LONG  = array('january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'décember');
	private static $MONTH_SHORT = array('jan.', 'feb.', 'mar.', 'apr.', 'may', 'june', 'juily', 'aug.', 'sep.', 'oct.', 'nov.', 'dec.');
	const MY_NAME = 'Me';
	const MY_NUMBER = 210; // H in HSL btw 0 and 360

	const SMS_PER_PAGE = 500;
	const SMS_SUPPL = 3; // SMS that appears on 2 pages (to help user follow the stream of messages)

	private $page;
	private $currentContact;
	private $me;

	/********** INIT *********/

	public function __construct($contact, $page = null) {

		$this->me = array(
			'id'      => self::MY_NAME,
			'name'    => self::MY_NAME,
			'initial' => self::getInitial(self::MY_NAME),
			'number'  => self::MY_NUMBER
		);

		$this->currentContact = array(
			'id'      => $contact,
			'name'    => self::ucname($contact),
			'initial' => self::getInitial($contact),
			'number'  => self::stringToIntegerHash($contact, 360)
		);

		$this->getInfos();

		$maxPage = $this->getMaxPage();

		if ($page === null || $page < 1 || $page > $maxPage)
			$this->page = $maxPage;
		else
			$this->page = $page;

	}

	public static function main() {

		// process post file
		$popup = '';
		if (!empty($_FILES['file'])) {
			if ($_FILES['file']['type'] == 'text/xml')
				$popup = NewImportTools::importNewFile($_FILES['file']['tmp_name'], 'BackupRestore');
			elseif ($_FILES['file']['type'] == 'application/octet-stream')
				$popup = NewImportTools::importNewFile($_FILES['file']['tmp_name'], 'Menue');
			else
				$popup = 'Unknown file';
		}

		// get data for page
		$contacts = self::getContacts();
		$currentContact = (!empty($_GET['contact'])) ? $_GET['contact'] : $contacts[0]['name'];
		$currentPage = (isset($_GET['page'])) ? $_GET['page'] : null;

		$view = new self($currentContact, $currentPage);
		$me = $view->getMe();
		$currentContact = $view->getContact();
		$messages = $view->getMessages();

		$pages = self::listPages($view->getPage(), $view->getMaxPage(), '?contact='.urlencode($currentContact['id']).'&amp;page=');

		return array(
			'popup'          => $popup,
			'contacts'       => $contacts,
			'view'           => $view,
			'me'             => $me,
			'currentContact' => $currentContact,
			'messages'       => $messages,
			'pages'          => $pages
		);
	}

	/********** DB DATA *********/

	private function getInfos() {
		$d = MySQL::getContactNbAndLastSMS($this->currentContact['id']);

		$this->currentContact['nb'] = $d['nb'];
		$this->currentContact['lastsms'] = $d['lastsms'];

		$phones = array();
		foreach(MySQL::getPhoneNumbers($this->currentContact['id']) as $phone) {
			$phones[] = $phone;
		}
		$this->currentContact['phones'] = $phones;
	}

	public static function getContacts() {

		$out = array();
		foreach (MySQL::getContacts() as $c) {
			$out[] = self::preprocessContact($c);
		}
		return $out;
	}

	public function getMessages() {
		return MySQL::getMessages($this->currentContact['id'], $this->page, self::SMS_PER_PAGE, self::SMS_SUPPL);
	}

	/******** GETTERS ************/

	public function getMaxPage() {
		return ceil($this->currentContact['nb'] / self::SMS_PER_PAGE);
	}

	public function getPage() {
		return $this->page;
	}

	public function getContact() {
		return $this->currentContact;
	}

	public function getMe() {
		return $this->me;
	}

	/******** PREPROCESS DATA ************/

	function isHighlight($i) {
		return ($this->getPage() > 1                   && $i <  self::SMS_SUPPL ||
			    $this->getPage() < $this->getMaxPage() && $i >= self::SMS_PER_PAGE);
	}


	public static function preprocessContact($c) {

		return array(
			'id'      => $c['name'],
			'name'    => self::ucname($c['name']),
			'initial' => self::getInitial($c['name']),
			'nb'      => $c['nb'],
			'lastsms' => self::formatDate($c['lastsms']),
			'number'  => self::stringToIntegerHash($c['name'], 360)
		);
	}


	public function preprocessMessage($m) {

		$user = ($m['direction'] == 'in') ? $this->currentContact : $this->me;

		return array(
			'direction' => $m['direction'],
			'name'      => $user['name'],
			'initial'   => $user['initial'],
			'dateShort' => self::formatDate($m['timestamp'], 'hide').'<br/>'.date('G\hi', $m['timestamp']),
			'dateLong'  => self::formatDate($m['timestamp'], 'big').' '.date('H:i:s', $m['timestamp']),
			'day'       => self::formatDate($m['timestamp'], 'big', 'big'),
			'message'   => $m['message']
		);
	}

	/*********** TOOLS ************/

	public static function stringToIntegerHash($str, $mod) {
		$out = 140;
		foreach(unpack('C*', sha1($str, true)) as $i) {
			$out += $i;
			$out %= $mod;
		}
		return $out;
	}

	public static function formatDate($time, $yearType = 'smart', $monthType = 'small') {
		if ($monthType == 'big') 
			$conv = self::$MONTH_LONG;
		else
			$conv = self::$MONTH_SHORT;

		$day = date('j', $time);
		$month = $conv[date('n', $time) - 1];
		if ($yearType == 'small' || $yearType == 'smart' && date('y', $time) != date('y'))
			$year = date('y', $time);
		elseif ($yearType == 'big')
			$year = date('Y', $time);
		else
			$year = '';

		return $day.' '.$month.' '.$year;
	}

	public static function getInitial($str) {
		return mb_strtoupper(mb_substr($str, 0, 1));
	}

	public static function ucname($string) {
	  $string =ucwords(strtolower($string));

	  foreach (array('-', '\'') as $delimiter) {
	    if (strpos($string, $delimiter)!==false) {
	      $string =implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
	    }
	  }
	  return $string;
	}


	// DISCLAIMER: this is an old function that I wrote, recycled here...
	public static function listPages($num_page, $nbr_pages, $url_before_num, $nbr_a_afficher = 4, $show_prev_next = true)
	{
		$output = '';
		
		if ($nbr_pages <= 1) return '<li class="active"><span>1</span></li>';
		
		// start / end limits
		$page_start = $num_page - $nbr_a_afficher;
		$page_end   = $num_page + $nbr_a_afficher;
		$before_num = $num_page - 1;
		
		// pages limits
		if ($page_start < 1) $page_start = 1;
		if ($page_end > $nbr_pages) $page_end = $nbr_pages;
		
		// [< Prev]
		if ($num_page != 1 && $show_prev_next)
			$output .= '<li><a href="'.$url_before_num.$before_num.'#bottom">&laquo;</a></li> ';
		
		// "[1]" if stats at 2
		if ($page_start == 2)
			$output .= '<li><a href="'.$url_before_num.'1#bottom">1</a></li> ';
		
		// "[1] [...]" if gap btw 1 and 1st page
		elseif ($page_start != 1)
			$output .= '<li><a href="'.$url_before_num.'1#bottom">1</a></li> <li class="disabled"><span>...</span></li> ';
		
		// pages
		for ($i=$page_start; $i <= $page_end; $i++) {
			if ($i != $num_page)
				$output .= '<li><a href="'.$url_before_num.$i.($i < $num_page ? '#bottom' : '#top').'">'.$i.'</a></li> ';
			else
				$output .= '<li class="active"><span>'.$i.'</span></li> ';
		}
		
		// " [End]" if stoped just before end
		if ($page_end == $nbr_pages - 1)
			$output .= '<li><a href="'.$url_before_num.$nbr_pages.'#top">'.$nbr_pages.'</a></li> ';
		
		// "[...] [End]" if not at end
		elseif ($page_end != $nbr_pages)
			$output .= '<li class="disabled"><span>...</span></li> <li><a href="'.$url_before_num.$nbr_pages.'#top">'.$nbr_pages.'</a></li> ';
		
		// [Next >]
		if ($num_page != $nbr_pages && $show_prev_next)
			$output .= '<li><a href="'.$url_before_num.($num_page + 1).'#top">&raquo;</a></li> ';
		
		return $output;
	}

}
