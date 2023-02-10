<?php
if (isset($_GET["url"])) {
	// TODO: sanitize user input! This is extremely insecure
	$url = $_GET["url"];
} else {
	$url = 'https://picsum.photos/200/300';
}

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($curl, CURLOPT_HEADER, false);
curl_setopt($curl, CURLOPT_HTTPHEADER, ['user-agent: curl/7.74.0']);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
$input = curl_exec($curl);
curl_close($curl);
?>

<?php
$img_imagick = new Imagick();
$img_imagick->readImageBlob($input);

// Resize the image
if (isset($_GET['resize']) && is_numeric($_GET['resize']) && intval($_GET['resize']) > 0) {
	$img_imagick->adaptiveResizeImage(intval($_GET['resize']), intval($_GET['resize']), Imagick::INTERPOLATE_BILINEAR, 0);
}

// Convert the image to the gray colorspace
// https://imagemagick.org/script/command-line-options.php#colors 
// When converting an image from color to grayscale, it is more efficient to convert the image to the gray colorspace before reducing the number of colors.
$img_imagick->transformImageColorspace(Imagick::COLORSPACE_GRAY);

// Important: posterize must be ran before quantize
// Reduce the image to a limited number of color levels per channel (Gray colorspace only have one channel)
// https://imagemagick.org/script/command-line-options.php#posterize
$img_imagick->posterizeImage(2, FALSE);

// Prepare a 1-bit bmp image

// Reduce colors using this colorspace
// https://imagemagick.org/script/command-line-options.php#quantize
$img_imagick->quantizeImage(2, Imagick::COLORSPACE_GRAY, 0, FALSE, FALSE);

// Convert the image to a reduced color format
$img_imagick->setImageFormat('bmp');
$img_imagick->setImageType(Imagick::IMGTYPE_BILEVEL);
$img_imagick->setImageCompression(imagick::COMPRESSION_NO);

// Export the image
$img_imagick_data = $img_imagick->getImageBlob();

// Cleanup the image
$img_imagick->clear();
?>

<?php
$img_dithered_imagick = new Imagick();
$img_dithered_imagick->readImageBlob($input);
$img_dithered_imagick->quantizeImage(2, Imagick::COLORSPACE_GRAY, 1, TRUE, FALSE);
$img_dithered_imagick->setImageFormat('bmp');
$img_dithered_imagick->setImageCompression(imagick::COMPRESSION_NO);
$img_dithered_imagick_data = $img_dithered_imagick->getImageBlob();
$img_dithered_imagick->clear();
?>

<?php
$img_gd = imagecreatefromstring($input);
if (!imageistruecolor($img_gd)) imagepalettetotruecolor($img_gd);
imagefilter($img_gd, IMG_FILTER_GRAYSCALE);
imagefilter($img_gd, IMG_FILTER_CONTRAST, -100);
// imagefilter($img_gd, IMG_FILTER_BRIGHTNESS, 255 / 2);
imagetruecolortopalette($img_gd, false, 2);

ob_start();
imagebmp($img_gd, null, false);
$img_gd_data = ob_get_contents();
ob_end_clean();
imagedestroy($img_gd);
?>

<html>

<head>
</head>

<body>
	<img src="data:image;base64,<?= base64_encode($input); ?>" />
	<img src="data:image;base64,<?= base64_encode($img_dithered_imagick_data); ?>" />
	<img src="data:image;base64,<?= base64_encode($img_imagick_data); ?>" />
	<img src="data:image;base64,<?= base64_encode($img_gd_data); ?>" />
</body>

</html>