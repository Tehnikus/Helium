<?php
class ModelToolImage extends Model {
	public function resize($filename, $width, $height) {
		if (!is_file(DIR_IMAGE . $filename) || substr(str_replace('\\', '/', realpath(DIR_IMAGE . $filename)), 0, strlen(DIR_IMAGE)) != str_replace('\\', '/', DIR_IMAGE)) {
			$filename = 'no_image.webp';
		}

		// Check if GD library is installed
		$webp_supports = false;
		$gd = gd_info();
		if (isset($gd['WebP Support']) 
			&& $gd['WebP Support'] 
			// && (isset($this->request->server['HTTP_ACCEPT']) && strpos($this->request->server['HTTP_ACCEPT'], 'webp'))
		) {
			$webp_supports = true;
		}

		// list($width_orig, $height_orig, $image_type) = getimagesize(DIR_IMAGE . $filename);
		// // If image is not one of the supported formats - return original
		// if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_WEBP, 'svg'))) { 
		// 	return DIR_IMAGE . $filename;
		// }

		// Allowed file mime types
		$allowed_mime_type = array(
			'image/jpeg',
			'image/pjpeg',
			'image/png',
			'image/x-png',
			'image/webp',
		);
		if (in_array(mime_content_type(DIR_IMAGE . $filename), $allowed_mime_type)) {
			if ($webp_supports) {
				$image = $this->createWebpImage($filename, $width, $height);
			} else {
				$image = $this->createJpgImage($filename, $width, $height);
			}
		} elseif (preg_match("/SVG/i", mime_content_type(DIR_IMAGE . $filename))) {
			$image = $this->createSvgImage($filename, $width, $height);
		} else {
			$image = DIR_IMAGE . $filename;
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

		// Default file path
		$webp_image = 'cache/webp/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.webp';
		
		// Check if file not exisits 
		// Or if original file is newer then created
		// Create WEBP image if so
		if (!is_file(DIR_IMAGE . $webp_image) || (filemtime(DIR_IMAGE . $filename) > filemtime(DIR_IMAGE . $webp_image))) {

			// Create directories for image
			$path = '';
			$directories = explode('/', dirname($webp_image));
	
			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;
				if (!is_dir(DIR_IMAGE . $path)) {
					@mkdir(DIR_IMAGE . $path, 0777);
				}
			}
	
			// Resize
			$image = new Image(DIR_IMAGE . $filename);
			// Set default image sizes so if something went wrong when getting settings, library doesn't crash
			$image->resize($width = 400, $height = 400);
			$resized = $image->getImage();

			// Create WEBP image and save
			imagewebp($resized, DIR_IMAGE . $webp_image, 85);
			file_put_contents(DIR_IMAGE . $webp_image, "\0", FILE_APPEND);

		}

		return $webp_image;
	}

	public function createJpgImage($filename, $width, $height) {
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
		$jpg_image = 'cache/jpg/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;
		
		if (!is_file(DIR_IMAGE . $jpg_image) || (filemtime(DIR_IMAGE . $filename) > filemtime(DIR_IMAGE . $jpg_image))) {		
			$path = '';
			$directories = explode('/', dirname($jpg_image));

			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;

				if (!is_dir(DIR_IMAGE . $path)) {
					@mkdir(DIR_IMAGE . $path, 0777);
				}
			}

			$image = new Image(DIR_IMAGE . $filename);
			// Set default image sizes so if something went wrong when getting settings, library doesn't crash
			$image->resize($width = 400, $height = 400);
			$image->save(DIR_IMAGE . $jpg_image);
		}

		return $jpg_image;
	}

	public function createSvgImage($filename, $width, $height) {
		$svg_image = 'cache/svg/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.svg';
		if (!is_file(DIR_IMAGE . $svg_image) || (filemtime(DIR_IMAGE . $filename) > filemtime(DIR_IMAGE . $svg_image))) {
			$path = '';
			$directories = explode('/', dirname($svg_image));

			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;

				if (!is_dir(DIR_IMAGE . $path)) {
					@mkdir(DIR_IMAGE . $path, 0777);
				}
			}

			$svg = file_get_contents(DIR_IMAGE . $filename);

			// I prefer to use DOM, because it's safer and easier as to use preg_match
			$svg_dom = new DOMDocument();

			libxml_use_internal_errors(true);
			$svg_dom->loadXML($svg);
			libxml_use_internal_errors(false);


			//get width and height values from your svg
			$tmp_obj = $svg_dom->getElementsByTagName('svg')->item(0);
			// set width and height of your svg to preferred dimensions
			$tmp_obj->setAttribute('width', $width);
			$tmp_obj->setAttribute('height', $height);

			// // Calculate translate(x, y) values
			// $svg_width = floatval($tmp_obj->getAttribute('width'));
			// $svg_height = floatval($tmp_obj->getAttribute('height'));
			
			// // check if width and height of your svg is smaller than the width and 
			// // height you set above => no down scaling is needed
			// if ($svg_width < $width && $svg_height < $height) {
			// 	//center your svg content in new box
			// 	$x = abs($svg_width - $width) / 2;
			// 	$y = abs($svg_height - $height) / 2;
			// 	$tmp_obj->getElementsByTagName('g')->item(0)->setAttribute('transform', "translate($x,$y)");
			// } else {
			// 	// scale down your svg content and center it in new box
			// 	$scale = 1;

			// 	// set padding to 0 if no gaps are desired
			// 	$padding = 2;

			// 	// get scale factor
			// 	if ($svg_width > $svg_height) {
			// 		$scale = ($width - $padding) / $svg_width;
			// 	} else {
			// 		$scale = ($height - $padding) / $svg_height;
			// 	}

			// 	$x = abs(($scale * $svg_width) - $width) / 2;
			// 	$y = abs(($scale * $svg_height) - $height) / 2;
			// 	$tmp_obj->getElementsByTagName('g')->item(0)->setAttribute('transform', "translate($x,$y) scale($scale,$scale)");

			// }
			fopen(DIR_IMAGE . $svg_image, 'w');
			file_put_contents(DIR_IMAGE . $svg_image, $svg_dom->saveXML());
		}

		return $svg_image;
	}
}
