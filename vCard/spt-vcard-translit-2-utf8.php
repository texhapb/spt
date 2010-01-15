#!/usr/bin/php
<?php
	include('fileparser.php');
	class Translit2Utf8_FileParser extends FileParser {
		static $NpjLetters = array( "a" => "а", "b" => "б", "v" => "в", "g" => "г", "d" => "д", "e" => "е", "z" => "з", "i" => "и", "k" => "к", "l" => "л", "m" => "м", "n" => "н", "o" => "о", "p" => "п", "r" => "р", "s" => "с", "t" => "т", "u" => "у", "f" => "ф", "h" => "х", "c" => "ц", "j" => "й", "y" => "ы" );
		static $NpjBiLetters = array( "jo" => "ё", "zh" => "ж", "ch" => "ч", "sh" => "ш", "shh" => "щ", "je" => "э", "ju" => "ю", "ja" => "я", "ь" => "'");
		static $NpjBLetters = array( "A" => "А", "B" => "Б", "V" => "В", "G" => "Г", "D" => "Д", "E" => "Е", "Z" => "З", "I" => "И", "K" => "К", "L" => "Л", "M" => "М", "N" => "Н", "O" => "О", "P" => "П", "R" => "Р", "S" => "С", "T" => "Т", "U" => "У", "F" => "Ф", "H" => "Х", "C" => "Ц", "J" => "Й", "Y" => "Ы" );
		static $NpjBiGLetters = array( "JO" => "Ё", "ZH" => "Ж", "CH" => "Ч", "SH" => "Ш", "SHH" => "Щ", "JE" => "Э", "JU" => "Ю", "JA" => "Я", "Ь" => "'" );

		public function parseCard(&$card) {
			$var = $card->_map['N'][0]->value;
			$var = strtr( $var, self::$NpjBiGLetters );
			$var = strtr( $var, self::$NpjBiLetters );
			$var = strtr( $var, self::$NpjBLetters );
			$var = strtr( $var, self::$NpjLetters );
			$card->_map['N'][0]->value = $var;
		}
	}
	$obj = new Translit2Utf8_FileParser();
	$obj->run();
?>