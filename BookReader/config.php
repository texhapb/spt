<?php
	include('fly_ds400.cfg.php');
	include('iriver_U10.cfg.php');
	include('font.php');
	$fnt = FontFactory::Create($config['font']);
	$config['mlpl'] = (int)floor($config['screen_width']/$fnt->char_width);
	$config['mlpp'] = (int)floor($config['screen_height']/$fnt->char_height);
?>