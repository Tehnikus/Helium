<?php
class ModelToolImage extends Model {
	public function resize($filename, $width, $height) {
		if (!is_file(DIR_IMAGE . $filename) || substr(str_replace('\\', '/', realpath(DIR_IMAGE . $filename)), 0, strlen(DIR_IMAGE)) != str_replace('\\', '/', DIR_IMAGE)) {
			echo(DIR_IMAGE . $filename. '<br/>');
			$filename = 'no_image.webp';
		}

		// Check if GD library is installed
		$webp_supports = false;
		$gd = gd_info();
		if (isset($gd['WebP Support']) && $gd['WebP Support'] && (isset($this->request->server['HTTP_ACCEPT']) && strpos($this->request->server['HTTP_ACCEPT'], 'webp'))) {
			$webp_supports = true;
		}


		if ($webp_supports) {
			$image = $this->createWebpImage($filename, $width, $height);
		} else {
			$image = $this->createJpgImage($filename, $width, $height);
		}
		



		
		// fix bug when attach image on email (gmail.com). it is automatic changing space " " to +
		$image = str_replace(' ', '%20', $image);  

		if ($this->request->server['HTTPS']) {
			return $this->config->get('config_ssl') . 'image/' . $image;
		} else {
			return $this->config->get('config_url') . 'image/' . $image;
		}

		
		

	}

	public function createWebpImage($filename, $width, $height) {

		// Default way
		$webp_image = 'cache/webp/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.webp';
		
		// Check if file not exisits 
		// Or if original file is newer then created
		// Create WEBP image if so
		if (!is_file(DIR_IMAGE . $webp_image) || (filemtime(DIR_IMAGE . $filename) > filemtime(DIR_IMAGE . $webp_image))) {

			// Create directories for image
			$path = '';
			$extension = pathinfo($filename, PATHINFO_EXTENSION);
			$directories = explode('/', dirname($webp_image));
	
			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;
				if (!is_dir(DIR_IMAGE . $path)) {
					@mkdir(DIR_IMAGE . $path, 0777);
				}
			}
	
	
			// $jpg=imagecreatefromjpeg('filename.jpg');
			// $w=imagesx($jpg);
			// $h=imagesy($jpg);
			// $webp=imagecreatetruecolor($width, $height);
			// imagecopy($webp,$jpg,0,0,0,0,$width,$height);
			// imagewebp($webp, 'filename.webp', 80);
			// imagedestroy($jpg);
			// imagedestroy($webp);
	
	
			// if ((strtolower($extension) == 'jpg') || (strtolower($extension) == 'jpeg')) {
			// 	$image_original = imagecreatefromjpeg(DIR_IMAGE . $filename);
			// } elseif (strtolower($extension) == 'png') {
			// 	$image_original = imagecreatefrompng(DIR_IMAGE . $filename);
			// } elseif (strtolower($extension) == 'gif') {
			// 	$image_original = imagecreatefromgif(DIR_IMAGE . $filename);
			// }
	
			// if (isset($image_original)) {

				$image = new Image(DIR_IMAGE . $filename);
				$image->resize($width, $height);
				$resized = $image->getImage();

				imagewebp($resized, DIR_IMAGE . $webp_image, 85);
				// Free memory
				// imagedestroy($image_original);
	
				if (filesize(DIR_IMAGE . $webp_image) % 2 == 1) {
					file_put_contents(DIR_IMAGE . $webp_image, "\0", FILE_APPEND);
				}
			// }
		}

		return $webp_image;
	}

	public function createJpgImage($filename, $width, $height) {
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
		$jpg_image = 'cache/jpg' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;
		
		if (!is_file(DIR_IMAGE . $jpg_image) || (filemtime(DIR_IMAGE . $filename) > filemtime(DIR_IMAGE . $jpg_image))) {

			list($width_orig, $height_orig, $image_type) = getimagesize(DIR_IMAGE . $filename);
			// If image is not one of the supported formats - return original
			if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_WEBP))) { 
				return DIR_IMAGE . $filename;
			}

			if ($width_orig != $width || $height_orig != $height) {
						
				$path = '';

				$directories = explode('/', dirname($filename));

				foreach ($directories as $directory) {
					$path = $path . '/' . $directory;

					if (!is_dir(DIR_IMAGE . $path)) {
						@mkdir(DIR_IMAGE . $path, 0777);
					}
				}
				$image = new Image(DIR_IMAGE . $filename);
				$image->resize($width, $height);
				$image->save(DIR_IMAGE . $jpg_image);
			} else {
				copy(DIR_IMAGE . $filename, DIR_IMAGE . $filename);
			}
		}

		return $jpg_image;

		// if ($this->request->server['HTTPS']) {
		// 	return $this->config->get('config_ssl') . 'image/' . $jpg_image;
		// } else {
		// 	return $this->config->get('config_url') . 'image/' . $jpg_image;
		// }
	}
}
