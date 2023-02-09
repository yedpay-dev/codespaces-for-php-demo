<?php
if (isset($_GET["url"])){
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
$img_imagick->quantizeImage(2, Imagick::COLORSPACE_GRAY, 1, FALSE, FALSE);
$img_imagick->setImageFormat('bmp');
$img_imagick->setImageCompression(imagick::COMPRESSION_NO);
$img_imagick_data = $img_imagick->getImageBlob();
$img_imagick->clear();
?>

<?php
$img_gd = imagecreatefromstring($input);
if(!imageistruecolor($img_gd)) imagepalettetotruecolor($img_gd);
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
	<img src="data:image/jpg;base64, <?=base64_encode($input);?>"/>
	<img src="data:image/bmp;base64, <?=base64_encode($img_imagick_data);?>"/>
	<img src="data:image/bmp;base64, <?=base64_encode($img_gd_data);?>"/>
	</body>
</html>