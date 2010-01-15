<?php
	abstract class Font {
		protected $chars;
		protected $fontdir = './fonts/';

		public $FontImg;
		public $char_width;
		public $char_height;
		public function __construct($fontname) {
			$data = explode("\r\n", file_get_contents($this->fontdir.$fontname.'.chr'));
			$this->chars = preg_split('//u', $data[1]);
			unset($this->chars[count($this->chars)-1]);//!!!!!
			$this->chars = array_flip($this->chars);
			$data = explode(' ', $data[0]);
			$this->char_width = intval($data[0]);
			$this->char_height = intval($data[1]);
			$this->FontImg =  imagecreatetruecolor($this->char_width * 256, $this->char_height);
			$bg = imagecolorclosest($this->FontImg, $data[2], $data[3], $data[4]);
			$fg = imagecolorclosest($this->FontImg, $data[5], $data[6], $data[7]);
			imagefill($this->FontImg, 0, 0, $bg);
			$this->Load($fontname);
		}
		abstract function Load($fontname);
		abstract function strlen($str);
		public function output($img, $line, $pos, $chr, $top_shift) {
			if (isset($this->chars[$chr])) {
				$idx = $this->chars[$chr];
			} else {
				echo($chr);
				$idx = 1;
			}
			imagecopy($img, $this->FontImg, $pos*$this->char_width, $top_shift + $line*$this->char_height, ($idx-1)*$this->char_width, 0, $this->char_width, $this->char_height);
		}
	}

	class FntFont extends Font {
		public function Load($fontname) {
			$data = file_get_contents($this->fontdir.$fontname.'.fnt');
			$size = strlen($data);
			for ($i=0; $i<$size; $i++) {
				$x = $this->char_width*(floor($i/$this->char_height) + 1);
				$y = $i % $this->char_height;
				$byte = ord($data[$i]);
				for ($j=0; $j<$this->char_width; $j++) {
					$dx = -$j;
					$pixel = $byte & 0x01;
					$byte = $byte >> 1;
					if ($pixel) {
						imageline($this->FontImg, $x+$dx, $y, $x+$dx, $y, $fg);
					}
				}
			}
		}
		public function strlen($str) {
			return mb_strlen($str);
		}
	}

	class IncFont extends Font {
		public function Load($fontname) {
			$data = file($this->fontdir.$fontname.'.inc');
			$i = 0;
			foreach($data as $line) {
				$x = $this->char_width*($i+1);
				$tmp = explode(';', $line);
				$tmp = explode('db ', ' '.$tmp[0]);
				$tmp = explode(',', $tmp[1]);
				$y = 0;
				foreach($tmp as $subline) {
					$base = 10;
					if (substr($subline, -1, 1)==='h') {
						$subline = '0x'.substr($subline, 1, -1);
						$base = 16;
					}
					$byte = intval($subline, $base);
					for ($j=0; $j<$this->char_width; $j++) {
						$dx = -$j;
						$pixel = $byte & 0x01;
						$byte = $byte >> 1;
						if ($pixel) {
							imageline($this->FontImg, $x+$dx, $y, $x+$dx, $y, $fg);
						}
					}
					$y++;
				}
				$i++;
			}
		}
		public function strlen($str) {
			return mb_strlen($str);
		}
	}

	class PngFont extends Font {
		public function Load($fontname) {
			$this->FontImg = imagecreatefrompng($this->fontdir.$fontname.'.png');
		}
		public function strlen($str) {
			return mb_strlen($str);
		}
	}

	class iRiverFont extends Font {
		protected $data;
		public $unknown;
		public function __construct($fontname) {
			mb_internal_encoding('UTF-8');
			//Первый символ,его количество, кол-во точек. Первая строка - отдельно.
			$src = explode("\n", file_get_contents($this->fontdir.$fontname.'.irvf'));
			$extra = mb_substr($src[0], 0, 1);
			$this->data = array($extra=>1/mb_strlen($src[0]));
			array_shift($src);
			foreach($src as $line) {
				$letter = mb_substr($line, 0, 1);
				$this->data[$letter] = (1-$this->data[$extra]*mb_substr_count($line, $extra))/mb_substr_count($line, $letter);
			}
			$this->char_width = 1;
			$this->char_height = 1;
			$this->unknown = array();
		}
		public function Load($fontname) {
		}
		public function strlen($str) {
			$tmp = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
			$result = 0;
			foreach($tmp as $letter) {
				if (isset($this->data[$letter])) {
					$result += $this->data[$letter];
				} else {
					$this->unknown[$letter] = $letter;
				}
			}
			return $result;
		}
	}

	class FontFactory {
		private static $fonts = array('fnt'=>'FntFont', 'inc'=>'IncFont', 'png'=>'PngFont', 'irvf'=>'iRiverFont');
		static function Create($name) {
			$parts = explode('.', $name);
			if (!isset(self::$fonts[$parts[1]])) {
				die("Unknown font type.\n");
			}
			return new self::$fonts[$parts[1]]($parts[0]);
		}
	}

?>