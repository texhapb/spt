#!/usr/bin/php
<?php
	include('fileparser.php');
	class Utf72Utf8_FileParser extends FileParser {
		protected function convert($str) {
			return trim(strtr(iconv('CP1251', 'UTF-8', $str), ';', ' '));
		}
		protected function parseCard(&$card) {
			$card->_map['N'][0]->value = $this->convert($card->_map['N'][0]->value);
			if (isset($card->_map['ORG'])) { //!!!!!
				$card->_map['ORG'][0]->value = $this->convert($card->_map['ORG'][0]->value);
			}
			if (isset($card->_map['UID'])) {
				unset($card->_map['UID']);
			}
			foreach($card->_map as $properties) {
				foreach($properties as $property) {
					if (isset($property->params['ENCODING']) && in_array('QUOTED-PRINTABLE', $property->params['ENCODING']) && !isset($property->params['CHARSET'])) {
						$property->params['CHARSET'][] = 'UTF-8';
					}
				}
			}
			if (!isset($card->_map['N'][0]->params['CHARSET'])) {
				$card->_map['N'][0]->params['CHARSET'][] = 'UTF-8';
			}
//			var_dump($card);
		}
// 		protected function saveFile($cards, $fname) {
// 		}
	}
	$obj = new Utf72Utf8_FileParser();
	$obj->run();
?>