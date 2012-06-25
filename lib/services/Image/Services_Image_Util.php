<?php

class Services_Image_Util {

	/**
	 * Returns width, height, and type of an image,
	 * or 'false' when an invalid image
	 */
	function getImageDimensions($imageString) {
		/*
		 * PHP image functions require an actual file,
		 * so we create one
		 */
		$temp_file = tempnam(sys_get_temp_dir(), 'SpotWeb_');

		$fh = fopen($temp_file, 'w');
		fwrite($fh, $imageString);
		fclose($fh);

		/*
		 * Now read the file, but only if its actual any use 
		 */
		if (filesize($temp_file) < 15) {
			unlink($temp_file);
			return false;
		} # if

		$imageInfo = getimagesize($temp_file);
		if ($imageInfo == false) {
			unlink($temp_file);
			return false;
		} # if

		/*
		 * Remove the temporary file
		 */
		unlink($temp_file);

		/*
		 * If an image is illegal anyway, invalidate it
		 */
		if (($imageInfo[0] < 0) || ($imageInfo[1] < 0)) {
			return false;
		} # if

		return array('width' => $imageInfo[0],
					 'height' => $imageInfo[1],
					 'isbmp' => ($imageInfo[2] == 6),
					 'imagetype' => $imageInfo[2]);
	} # getImageDimensions

} # Services_Image_Util

