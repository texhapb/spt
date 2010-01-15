#!/usr/bin/php
<?php
	include('config.php');

	function savePage($img, $page) {
		global $config, $filename, $pathcounters;
		$path = $filename;
		for ($i = count($pathcounters) - 1; $i > 0 ; $i--) {
			$path .= sprintf('/%03d', $pathcounters[$i]);
			if ($pathcounters[$i-1] === 0) {
				@mkdir($path);//!!!!!!
			}
		}
		imagepng($img, $path.sprintf('/%03d.png', $pathcounters[0]));
		$p = 1;
		for ($i = 0; $i < count($pathcounters) - 1; $i++) {
			$pathcounters[$i] += $p;
			$p = floor($pathcounters[$i] / $config['quantity'][$i]);
			$pathcounters[$i] = $pathcounters[$i] % $config['quantity'][$i];
		}
		$pathcounters[$i] += $p;
	}

	$data = file($argv[1]);
	$filename = pathinfo($argv[1]);
	$filename = $filename['filename'];
	@mkdir($filename);//!!!!!
	$lpp = 0;
	$page = 0;
	$pathcounters = array();
	for ($i = 0; $i <= count($config['quantity']); $i++) {
		$pathcounters[] = 0;
	}
	mb_internal_encoding('UTF-8');
	foreach($data as $line) {
		if ($lpp === 0) {
			$img = imagecreatetruecolor($config['mlpl']*$fnt->char_width, $config['top_shift']+$config['bottom_shift']+$config['mlpp']*$fnt->char_height);
		}
		$_line = rtrim($line).str_repeat(' ', $config['mlpl']);
		for($i=0; $i<mb_strlen($_line); $i++) {
			$fnt->output($img, $lpp, $i, mb_substr($_line, $i, 1), $config['top_shift']);
		}
		$lpp++;
		if ($lpp === $config['mlpp']) {
			$lpp = 0;
			savePage($img, $page);
			imagedestroy($img);
			$img = NULL;
			$page++;
		}
	}
	if (!is_null($img)) {
		savePage($img, $page);
	}
?>