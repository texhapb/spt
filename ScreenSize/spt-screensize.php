#!/usr/bin/php
<?php
if (count($argv)<7) {
	die("Necessary parameters omitted.\n".
		"Usage: ".basename($argv[0])." min_width max_width step_width min_height max_height step_height [output_format]\n");
}
$min_w = intval($argv[1]);
$max_w = intval($argv[2]);
$step_w = intval($argv[3]);
$min_h = intval($argv[4]);
$max_h = intval($argv[5]);
$step_h = intval($argv[6]);
$format = isset($argv[7])?$argv[7]:'gif';
if (!function_exists('image'.$format)) {
	echo("Format '".$format."' not supported. Used 'gif' instead.\n");
	$format = 'gif';
}

$imgsave = 'image'.$format;

$colors_array = array(
	array(0, 0, 0),
	array(255, 0, 0),
	array(0, 255, 0),
	array(0, 0, 255),
);

for($width = $min_w; $width <= $max_w; $width += $step_w) {
	for($height = $min_h; $height <= $max_h; $height += $step_h) {
		$im_dst = imagecreatetruecolor($width, $height);
		
		$colors = array();
		foreach($colors_array as $item) {
			$colors[] = imagecolorallocate($im_dst, $item[0], $item[1], $item[2]);
		}
		$bg = imagecolorallocate($im_dst, 255, 255, 255);
		imagefill($im_dst, 0, 0, $bg);
		for($x = 1; $x < $width; $x+=2) {
			$color_index = 0;
			if (($x+1) % 10 == 0) {
				$color_index = 1;
			}
			if (($x+1) % 50 == 0) {
				$color_index = 2;
			}
			if (($x+1) % 100 == 0) {
				$color_index = 3;
			}
			imageline($im_dst, $x, 0, $x, $height - 1, $colors[$color_index]);
		}
		for($y = 1; $y < $height; $y+=2) {
			$color_index = 0;
			if (($y+1) % 10 == 0) {
				$color_index = 1;
			}
			if (($y+1) % 50 == 0) {
				$color_index = 2;
			}
			if (($y+1) % 100 == 0) {
				$color_index = 3;
			}
			imageline($im_dst, 0, $y, $width - 1, $y, $colors[$color_index]);
		}
		$imgsave($im_dst, sprintf('img%03dx%03d.'.$format, $width, $height));
		imagedestroy($im_dst);
	}
}
?>