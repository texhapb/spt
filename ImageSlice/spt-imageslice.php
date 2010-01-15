#!/usr/bin/php
<?php
if (count($argv)<5) {
	die("Necessary parameters omitted.\n".
		"Usage: ".basename($argv[0])." source_image output_width step_width output_height step_height [output_format]\n");
}

$img_size = getimagesize($argv[1]);
$src_width = $img_size[0];
$src_height = $img_size[1];
$src_format = str_replace('image/', '', $img_size['mime']);

$imgcreate = 'imagecreatefrom'.$src_format;

if (!function_exists($imgcreate)) {
	die("Source image format '".$src_format."' not supported.\n");
}

$im_src = $imgcreate($argv[1]);
$output_width = intval($argv[2]);
$step_width = intval($argv[3]);
$output_height = intval($argv[4]);
$step_height = intval($argv[5]);
$format = isset($argv[6])?$argv[6]:'gif';

if (!function_exists('image'.$format)) {
	echo("Format '".$format."' not supported. Used 'gif' instead.\n");
	$format = 'gif';
}

$imgsave = 'image'.$format;
$fname = pathinfo($argv[1]);
$fname = $fname['filename'];

for ($y=0; $y+$step_height<=$src_height; $y+=$step_height) {
	for ($x=0; $x+$step_width<=$src_width; $x+=$step_width) {
		$im_dst = imagecreatetruecolor($output_width, $output_height);
		imagecopy($im_dst, $im_src, 0, 0, $x, $y, $output_width, $output_height);
		$imgsave($im_dst, sprintf('%s%04d%04d.%s',$fname, $y, $x, $format));
		imagedestroy($im_dst);
	}
}
?>