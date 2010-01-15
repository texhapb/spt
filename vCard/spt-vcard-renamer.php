#!/usr/bin/php
<?php
	include('fileparser.php');
	class Renamer_FileParser extends FileParser {
		protected function parseCard(&$card) {
		}
		protected function saveFile($cards, $fname) {
			$tmp = pathinfo($fname);
			rename($fname, $tmp['dirname'].'/'.$cards[0]->_map['N'][0]->value.'.'.$tmp['extension']);
		}
	}
	$obj = new Renamer_FileParser();
	$obj->run();
?>