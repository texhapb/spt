<?php
	function __autoload($name) {
		include(strtolower($name).'.php');
	}
	abstract class FileParser {
		protected $files;
		public function __construct() {
			$this->files = $GLOBALS['argv'];
			array_shift($this->files);
		}
		public function run() {
			foreach($this->files as $fname) {
				$this->parseFile($fname);
			}
		}
		protected function parseFile($fname) {
		//Read cards
			$lines = file($fname);
			$cards = array();
			$card = new VCard();
			while ($card->parse($lines)) {
				$property = $card->getProperty('N');
				if (!$property) {
					break;
				}
				$cards[] = $card;
				// MDH: Create new VCard to prevent overwriting previous one (PHP5)
				$card = new VCard();
			}
		//Process cards
			foreach($cards as $id => $card) {
				$this->parseCard($card);
			}
		//Save cards
			$this->saveFile($cards, $fname);
		}
		protected function parseFName($fname) {
			return $fname;
		}
		protected function saveFile($cards, $fname) {
			$res = '';
			foreach($cards as $id => $card) {
				$res .= $card->ToString();
			}
			file_put_contents($this->parseFName($fname), $res);
		}
		protected abstract function parseCard(&$card);
	}
?>