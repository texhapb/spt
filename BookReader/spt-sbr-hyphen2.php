#!/usr/bin/php
<?php

include('config.php');

// Подключение библиотеки.
require_once 'hypher/hypher.php';

// Загрузка файла описания и набора правил.
$hy_ru = hypher_load('hypher/hyph_ru_RU.conf');

function out_paragraph($text, $flprefix, $prefix) {
	global $hy_ru, $config, $fnt;
	$flp = $flprefix;
	if ($config['mlpl'] >= $fnt->strlen($prefix.$flp.$text)) {
		echo($prefix.$flp.$text."\n");
		return;
	}
	$words = explode(' ', $text);
	$hyphLine = $prefix.$flp;
	while (!is_null($word = array_shift($words))) {
		if ($config['mlpl'] >= $fnt->strlen($hyphLine.$word)) {
			$hyphLine .= $word . ' ';
		} else {
		// Слово целиком не помещается
			// Делим на слоги
			$syls = hypher_word($hy_ru, $word, 2, 2);
			$part = '';
			// Перебираем слоги
			while (!is_null($syl = array_shift($syls))) {
				if ($config['mlpl'] >= $fnt->strlen($hyphLine.$part.$syl.$config['hyphenChar'])) {
					// Слог помещается
					$part .= $syl;
				} else {
					// Слог не помещается
					if ($part !== '') {
						// Уже выбрали часть слова
						$hyphLine .= $part;
						$hyphLine .= $config['hyphenChar'];
					} elseif ($hyphLine === '') {
						// Строка пуста
						$hyphLine = mb_substr($word, 0, $config['mlpl']);
						$word = mb_substr($word, $config['mlpl']);
						break;
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
//$text = '';

// Здесь цифровой автомат.
// 0 - ищем текст,
// 1 - найдено начало абзаца (учесть перенос),
// 2 - найдено продолжение абзаца (учесть перенос),
// 3 - найдено начало или продолжение эпиграфа (учесть перенос)

$state = 0;
$combline = '';
foreach ($lines as $line) {
	$line = str_replace('́', '', $line);
	$line = str_replace(' ', ' ', $line);
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
var_dump($fnt->unknown);
?>