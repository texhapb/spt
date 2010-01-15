<?php	/* hypher.php -- hyphenation using Liang-Knuth algorithm.
	 * Copyright (C) 2008 Sergey Kurakin (sergeykurakin@gmail.com)
	 *
	 * This program is free software; you can redistribute it and/or modify
	 * it under the terms of the GNU Lesser General Public License as
	 * published by the Free Software Foundation; either version 3
	 * of the License, or (at your option) any later version.
	 *
	 * This program is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU Lesser General Public License for more details.
	 */

// hypher_load:	load hyphenation configuration file and ruleset,
// 		recompile ruleset from rules files if needed.
// $conffile:	filename of config file.
// $recompile:	this flag indicates necessity to recompile ruleset
// returns:	descriptor of the compiled ruleset

mb_internal_encoding('UTF-8');
define('AUTO_RECOMPILE', 0);
define('NEVER_RECOMPILE', 1);
define('ALWAYS_RECOMPILE', 2);

// converts dos linefeeds (\r\n) or mac ones (\r) to unix format (\n)
function sk_unix_linefeeds($str) {
	return preg_replace('/\r\n?/', "\n", $str);
}

// returns value of array by key
function sk_array_value($arr, $k) {
	return (isset($arr[$k])) ? $arr[$k] : false;
}

// remove comments and empty lines
function sk_clean_config($instr) {
	$patt[] = '/\/\/.*$/m';		$repl[] = '';
	$patt[] = '/^\s*/m';		$repl[] = '';
	$patt[] = '/\s*$/m';		$repl[] = '';
	$patt[] = '/(?<=\n)\n+/';	$repl[] = '';
	$patt[] = '/\n$/';		$repl[] = '';
	$patt[] = '/^\n/';		$repl[] = '';
	return preg_replace($patt, $repl, sk_unix_linefeeds($instr));
}

// parse config file
function sk_parse_config($conffile) {
	$patt[] = '/&SCREENEDSPACE&/';	$repl[] = ' ';
	$patt[] = '/&SCREENEDLFEED&/';	$repl[] = "\n";
	$patt[] = '/&SCREENSNQUOTE&/';	$repl[] = '\'';
	$patt[] = '/&SCREENDBQUOTE&/';	$repl[] = '"';
	$in_file = file_get_contents($conffile);
	if (!$in_file) return false;
	$in_file = sk_unix_linefeeds($in_file);
	$in_file = preg_replace('/(?<!\x5C)\'(.*[^\x5C])\'/Ues', 'sl_screenspecial(\'$1\')', $in_file);
//	$in_file = preg_replace('/"([^"]*)"/Ues', 'sl_screenspecial(\'$1\')', $in_file);
	$in_file = sk_clean_config($in_file);
	$strings = explode("\n", $in_file);
	foreach ($strings as $val) {
		$pair = explode('=', $val);
		if (isset($pair[0])) $ret[trim($pair[0])][] = (isset($pair[1])) ? preg_replace($patt, $repl, trim($pair[1])) : true;
	}
	return $ret;
}

function hypher_load($conffile, $recompile = AUTO_RECOMPILE) {

	do {
		$dname = 'hy'. rand(100000, 999999);
	} while (isset($$dname));

	if (!is_file($conffile)) return false;
	$conf = sk_parse_config($conffile);
	if (!$conf) return false;

	$path = dirname($conffile);

	if (isset($conf['compiled'][0])) $conf['compiled'][0] = $path. '/'. $conf['compiled'][0];
	else return false;

	if (!is_file($conf['compiled'][0])) $recompile = ALWAYS_RECOMPILE;

	if (isset($conf['rules'])) foreach ($conf['rules'] as $key => $val) $conf['rules'][$key] = $path. '/'. $conf['rules'][$key];

	// define the necessety to remake dictionary
	if ($recompile == AUTO_RECOMPILE) {
		$date_out = filemtime($conf['compiled'][0]);
		$date_in = filemtime($conffile);
		foreach ($conf['rules'] as $val) $date_in = max($date_in, filemtime($val));
		if ($date_in > $date_out) $recompile = ALWAYS_RECOMPILE;
	}

	if ($recompile == ALWAYS_RECOMPILE) {

		// make alphabet string and translation table
		$ret['alph'] = preg_replace('/\((.+)\>(.+)\)/Ueu', '$ret[\'trans\'][\'$2\'] = \'$1\'', $conf['alphabet'][0]);
		if (!isset($ret['trans'])) $ret['trans'] = array();
		$ret['alphUC'] = $conf['alphabetUC'][0];

		$ret['ll'] = $conf['left_limit'][0];
		$ret['rl'] = $conf['right_limit'][0];
		$ret['enc'] = $conf['internal_encoding'][0];

		foreach ($conf['rules'] as $fnm) if (is_file($fnm)) {
			$in_file = explode("\n", sk_clean_config(file_get_contents($fnm)));

			// first string of the rules file is the encoding of this file
			$encoding = $in_file[0];
			unset($in_file[0]);

			// create rules array: keys -- letters combinations; values -- digital masks
			foreach ( $in_file as $val ) {

				// insert zero between the letters
				$str = preg_replace('/([^0-9])([^0-9])/u', '${1}0\2', $val);
				$str = preg_replace('/([^0-9])([^0-9])/u', '${1}0\2', $str);

				// insert zero on beginning and on the end
				if (preg_match('/^[^0-9]/', $str)) $str = '0'. $str;
				if (preg_match('/[^0-9]$/', $str)) $str .= '0';

				// make array
				$ind = preg_replace('/[\p{Nd}\n\s]/u', '', $str);
				$vl = preg_replace('/[\P{Nd}]/u', '', $str);
				if ($ind != '' && $vl != '') {
					$ret['dict'][$ind] = $vl;

					// optimize: if there is, for example, "abcde" letters combication
					// then we need "abcd", "abc", "ab" and "a" letters combinations
					// to be presented
					$sb = $ind;
					do {
						$sb = mb_substr($sb, 0, mb_strlen($sb) - 1);
						if (!isset($ret['dict'][$sb])) $ret['dict'][$sb] = 0;
						else break;
					} while (mb_strlen($sb) > 1);
				}
			}
		}
		
		$fh = fopen($conf['compiled'][0], 'w');
		fwrite($fh, serialize($ret));
		fclose($fh);
		$GLOBALS[$dname] = $ret;

	} else $GLOBALS[$dname] = unserialize(file_get_contents($conf['compiled'][0]));
	return $dname;
}


// hypher_word:	hyphenates one word. You don't need to call it directly.
// $dname:	descriptor of the compiled ruleset, returned by hypher_load()
// $instr:	input string
// $ll:		minimum of characters before the first hyphen in the word
// $rl:		minimum of characters after the last hyphen in the word
// $wl:		minimum length of the word to hyphenate
// $Uc_ll:	minimum of characters before the first hyphen in the word,
// 		beginning with uppercase letter
// $par_rl:	minimum of characters after the last hyphen
// 		in the last word of paragraph
function hypher_word($dname, $instr, $ll = 3, $rl = 3) {
	// if dictionary is not loaded, cannot proceed
	if (!isset($GLOBALS[$dname])) return $instr;
	$instr = preg_replace('/\./u', "\x07", $instr);

	$word_lower = '.'. mb_strtolower($instr). '.';
	$instr = '.'. $instr. '.';
	$len = mb_strlen($instr);
	foreach ($GLOBALS[$dname]['trans'] as $key => $val) $word_lower = str_replace($val, $key, $word_lower);
	$word_splitted = preg_split('//u', $word_lower);
	$word_mask = preg_split('//u', str_repeat('0', $len + 1));

	for ($i = 0; $i < $len; $i++) {
		for ($k = 1; $k < 100; $k++) {
			$ind = mb_substr($word_lower, $i, $k);
			if (mb_strlen($ind) < $k) break;
			if (!isset($GLOBALS[$dname]['dict'][$ind])) break;
			$val = $GLOBALS[$dname]['dict'][$ind];
			if ($val !== 0)
				for ($j = 0; $j <= $k ; $j++) $word_mask[$i + $j] = max($word_mask[$i + $j], $val[$j]);
		}
	}

	$tmp = preg_split('//u', $instr, -1, PREG_SPLIT_NO_EMPTY);

	$ret = array();
	$tmp2 = '';
	foreach ($tmp as $key => $val) if ( $val != '.') {
		$tmp2 .= $val;
		if ($key > $ll - 1 && $key < $len - $rl - 1 && $word_mask[$key + 1] % 2 ) {
			$ret[] = $tmp2;
			$tmp2 = '';
		}
	}

	if ($tmp2 !== '') {
		$ret[] = $tmp2;
	}

	foreach($ret as $k=>$v){
		$ret[$k] = preg_replace('/\x07/u', '.', $v);
	}

	return $ret;
}

// hypher:	the main hyphenation function
// $dname:	descriptor of the compiled ruleset, returned by hypher_load()
// $instr:	input string
// $ll:		minimum of characters before the first hyphen in the word
// $rl:		minimum of characters after the last hyphen in the word
// $wl:		minimum length of the word to hyphenate
// $Uc_ll:	minimum of characters before the first hyphen in the word,
// 		beginning with uppercase letter
// $par_rl:	minimum of characters after the last hyphen
// 		in the last word of paragraph
// $encoding:	encoding of the input string (and output string)
//!!!!!!!!!!!!!!!!!!!!
function hypher($dname, $instr, $ll = 0, $rl = 0, $wl = 0, $par_rl = 0, $Uc_ll = 0, $encoding = '', $shy = '&shy;') {

	// if dictionary is not loaded, cannot proceed
	if (!isset($GLOBALS[$dname])) return $instr;

	$alph = $GLOBALS[$dname]['alph']. $GLOBALS[$dname]['alphUC'];

	$ll = max($ll, $GLOBALS[$dname]['ll']);
	$rl = max($rl, $GLOBALS[$dname]['rl']);
	$wl = max($wl, $ll + $rl);
	$Uc_ll = max($ll, $Uc_ll);
	if ($par_rl > $rl) $instr = preg_replace('/(['. $alph.'\x5C]{'. $wl. ',})(?=\W*(\n|$|d\x5C?i\x5C?v\x5C?>))/iesu',
		'hypher_word(\''. $dname. '\', \'$1\', '. $ll. ', '. $par_rl. ', '. $Uc_ll. ', \''. $encoding. '\', \''. $shy. '\'). "\x07"', $instr);
	return preg_replace('/(['. $alph.'\x5C\x07]{'. $wl. ',})/iesu',
		'hypher_word(\''. $dname. '\', \'$1\', '. $ll. ', '. $rl. ', '. $Uc_ll. ', \''. $encoding. '\', \''. $shy. '\')', $instr);
}

?>
