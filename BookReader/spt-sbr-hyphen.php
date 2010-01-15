#!/usr/bin/php
<?php

include('config.php');

require_once 'Text/TeXHyphen.php';
require_once 'Text/TeXHyphen/PatternDB.php';
require_once 'Text/TeXHyphen/WordCache.php';


/* Creating an pattern source by loading an pattern file an */

// Loading american pattern
$patternFile = 'hyph_all.dic';
$patternArr = file($patternFile, 1);

// Removing header line with source information
array_shift($patternArr);

// Setting options for the objecthash
$options = array('mode' => 'build', 'data' => &$patternArr, 'onlyKeys' => true);

$patternDB =& Text_TeXHyphen_PatternDB::factory('objecthash', $options);

if (false === $patternDB) {
	$eS =& PEAR_ErrorStack::singleton('Text_TeXHyphen');
	$e =& $eS->pop();
	die ('PatternDB: '.(PEAR_ErrorStack::getErrorMessage($eS, $e)));
}


/* Creating an cache for hyphenated words */

// Loading exceptions
$exceptionsFile = 'exceptions.dic';
$exceptionsArr = array('1');

// Removing header line with source information
array_shift($exceptionsArr);

$wordCache =& Text_TeXHyphen_WordCache::factory('simplehash');

if (false === $wordCache) {
	$eS =& PEAR_ErrorStack::singleton('Text_TeXHyphen');
	$e =& $eS->pop();
	die ('WordCache: '.(PEAR_ErrorStack::getErrorMessage($eS, $e)));
}

// Adding exceptions to word cache
foreach ($exceptionsArr as $hyphWord) {
	$hyphWord = trim($hyphWord);
	$syls = explode("-", $hyphWord);
	$wordCache->add(implode($syls), $syls);
}


/* Creating the TeXHyphen */

$hyphen =& Text_TeXHyphen::factory($patternDB, array('wordcache' => &$wordCache));

if (false === $hyphen) {
	$eS =& PEAR_ErrorStack::singleton('Text_TeXHyphen');
	$e =& $eS->pop();
	die ('TeXHyphen: '.(PEAR_ErrorStack::getErrorMessage($eS, $e)));
}

function out_paragraph($text, $flprefix, $prefix) {
	global $hyphen, $config;
	$flp = $flprefix;
	if ($config['mlpl'] >= mb_strlen($prefix.$flp.$text)) {
		echo($prefix.$flp.$text."\n");
		return;
	}
	$words = explode(' ', $text);
	$hyphLine = $prefix.$flp;
	while (!is_null($word = array_shift($words))) {
		if ($config['mlpl'] >= mb_strlen($hyphLine.$word)) {
			$hyphLine .= $word . ' ';
		} else {
			$syls = $hyphen->getSyllables($word);
			$part = '';
			while (!is_null($syl = array_shift($syls))) {
				if ($config['mlpl'] >= mb_strlen($hyphLine.$part.$syl.$config['hyphenChar'])) {
					$part .= $syl;
				} else {
					if ($part !== '') {
						$hyphLine .= $part;
						$hyphLine .= $config['hyphenChar'];
					}
					$word = $syl.(implode('', $syls));
					break;
				}
			}
			array_unshift($words, $word);
			echo(rtrim($hyphLine)."\n");
			$hyphLine = $prefix;
		}
	}
	echo(rtrim($hyphLine)."\n");
}

// Hyphenating a text.

$sampleText = $argv[1];
$lines = file($sampleText);
$lines[] = '';
//$lines = array_splice($lines, 41, 1);
define('STATE_BAD', -1);
define('STATE_SEARCH', 0);
define('STATE_PARASTART', 1);
define('STATE_PARACNT', 2);
define('STATE_QUOTE', 3);
define('STATE_PARABREAK', 4);
mb_internal_encoding('UTF-8');
$text = '';

// Здесь цифровой автомат.
// 0 - ищем текст,
// 1 - найдено начало абзаца (учесть перенос),
// 2 - найдено продолжение абзаца (учесть перенос),
// 3 - найдено начало или продолжение эпиграфа (учесть перенос)

$state = 0;
$combline = '';
foreach ($lines as $line) {
	$newstate = STATE_BAD;
//	echo('Start.');
	if (trim($line) == '') {
//		echo(' Empty line.');
		$newstate = STATE_SEARCH;
	} else {
		$i = 0;
		while (mb_strlen($line)>0 && ($line[1] == ' ')) {
			$i++;
			$line = mb_substr($line, 1);
		}
		if ($i > $config['maxParaIndent']) {
			$newstate = STATE_QUOTE;
		} elseif ($i > 0) {
			$newstate = STATE_PARASTART;
		} else {
			$newstate = STATE_PARACNT;
		}
	}
	if ($newstate === STATE_BAD) {
		die("Unknown new state! Line contents:\n\"".$line.'"');
	}
//!!!!	echo(' state='.$state.' newstate='.$newstate."\n");
	//Обработать переходы м/у состояниями
	if ($newstate === STATE_SEARCH) {
		if ($state === STATE_PARASTART || $state === STATE_PARACNT) {
			out_paragraph(trim($combline), $config['para_firstline'], $config['para_indent']);
		} elseif ($state === STATE_QUOTE) {
			out_paragraph(trim($combline), $config['quote_firstline'], $config['quote_indent']);//!!!!!
		} elseif ($state === STATE_SEARCH) {
			out_paragraph('', '', '');
			$newstate = STATE_PARABREAK;
		}
		$combline = '';
	} else {
		if (($state === STATE_PARASTART || $state === STATE_PARACNT) && ($newstate !== STATE_PARACNT)) {
			out_paragraph(trim($combline), $config['para_firstline'], $config['para_indent']);
			$combline = '';
		}
		if (($state === STATE_QUOTE) && ($newstate !== STATE_QUOTE)) {
			out_paragraph(trim($combline), $config['quote_firstline'], $config['quote_indent']);//!!!!!
			$combline = '';
		}
		$combline .= (substr($line, -1, 1) == $config['hyphenChar']) ? substr($line, 0, -1) : (trim($line) . ' '); // Учет переносов
	}
	$state = $newstate;
}
?>