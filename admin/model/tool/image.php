<?php
class ModelToolImage extends Model {
	public function resize($filename, $width, $height) {
		if (!is_file(DIR_IMAGE . $filename) || substr(str_replace('\\', '/', realpath(DIR_IMAGE . $filename)), 0, strlen(DIR_IMAGE)) != str_replace('\\', '/', DIR_IMAGE)) {
			$filename = 'no_image.webp';
		}

		$extension = pathinfo($filename, PATHINFO_EXTENSION);

		$webp_image = false;
		$webp_supports = false;

		$gd = gd_info();

		if (isset($gd['WebP Support']) && $gd['WebP Support'] && (isset($this->request->server['HTTP_ACCEPT']) && strpos($this->request->server['HTTP_ACCEPT'], 'webp'))) {
			$webp_supports = true;
		}

		$image_old = $filename;
		$image_new = 'cache/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;

		if (!is_file(DIR_IMAGE . $image_new) || (filemtime(DIR_IMAGE . $image_old) > filemtime(DIR_IMAGE . $image_new))) {
			list($width_orig, $height_orig, $image_type) = getimagesize(DIR_IMAGE . $image_old);
				 
			if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_WEBP))) { 
				return DIR_IMAGE . $image_old;
			}
 
			$path = '';

			$directories = explode('/', dirname($image_new));

			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;

				if (!is_dir(DIR_IMAGE . $path)) {
					@mkdir(DIR_IMAGE . $path, 0777);
				}
			}

			if ($width_orig != $width || $height_orig != $height) {
				$image = new Image(DIR_IMAGE . $image_old);
				$image->resize($width, $height);
				$image->save(DIR_IMAGE . $image_new);
			} else {
				copy(DIR_IMAGE . $image_old, DIR_IMAGE . $image_new);
			}
		}
		


		if ($webp_supports && $extension != 'gif') {
			$webp_image = 'cache/webp/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.webp';

			if (!is_file(DIR_IMAGE . $webp_image) || (filemtime(DIR_IMAGE . $image_new) > filemtime(DIR_IMAGE . $webp_image))) {
				$path = '';
				$directories = explode('/', dirname($webp_image));

				foreach ($directories as $directory) {
					$path = $path . '/' . $directory;

					if (!is_dir(DIR_IMAGE . $path)) {
						@mkdir(DIR_IMAGE . $path, 0777);
					}
				}

				if (strtolower($extension) == 'jpg') {
					$image_original = imagecreatefromjpeg(DIR_IMAGE . $image_new);
				} elseif (strtolower($extension) == 'png') {
					$image_original = imagecreatefrompng(DIR_IMAGE . $image_new);
				}

				if (isset($image_original)) {
					imagewebp($image_original, DIR_IMAGE . $webp_image, 85);
					imagedestroy($image_original);

					if (filesize(DIR_IMAGE . $webp_image) % 2 == 1) {
						file_put_contents(DIR_IMAGE . $webp_image, "\0", FILE_APPEND);
					}
				} else {
					$webp_image = false;
				}
			}
		}
		
		if ($this->request->server['HTTPS']) {
			return HTTPS_CATALOG . 'image/' . ((isset($webp_image) && $webp_image) ? $webp_image : $image_new);
		} else {
			return HTTP_CATALOG . 'image/' . ((isset($webp_image) && $webp_image) ? $webp_image : $image_new);
		}
	}
}
