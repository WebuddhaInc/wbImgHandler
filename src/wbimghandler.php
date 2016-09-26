<?php

require_once '../vendor/autoload.php';

if( !function_exists('inspect') ){
  function inspect(){
    echo '<pre>' . print_r(func_get_args(), true) . '</pre>';
  }
}

$cache  = true;
$path   = preg_replace('/\.+\//','',$_REQUEST['f']);
$width  = (int)$_REQUEST['w'];
$height = (int)$_REQUEST['h'];

define('DIR', substr($_SERVER['SCRIPT_FILENAME'], 0, strlen($_SERVER['SCRIPT_FILENAME']) - strlen(basename(__FILE__))));
if (strpos($path, DIR) !== 0) {
  exit;
}

$file_extension = strtolower(substr(strrchr($path,"."),1));
switch( $file_extension ) {
  case "gif": $ctype="image/gif"; break;
  case "png": $ctype="image/png"; break;
  case "jpeg":
  case "jpg": $ctype="image/jpeg"; break;
  case "bmp": $ctype="image/bmp"; break;
  default: die('Invalid type');
}
header('Content-type: ' . $ctype);

if (!$width && !$height) {
  echo file_get_contents($path);
}

else if (is_readable($path)){
  if ($width && $height)
    $cache_file = substr($path, 0, strlen($path)-strlen($file_extension)).'resized-w'.$width.'h'.$height.'.'.$file_extension;
  else if ($width)
    $cache_file = substr($path, 0, strlen($path)-strlen($file_extension)).'resized-w'.$width.'.'.$file_extension;
  else
    $cache_file = substr($path, 0, strlen($path)-strlen($file_extension)).'resized-h'.$height.'.'.$file_extension;
  if ($cache && is_readable($cache_file)){
    echo file_get_contents($cache_file);
  }
  else {
    $image = new \Eventviva\ImageResize( $path );
    if ($width && $height)
      $image->resizeToBestFit($width, $height);
    else if($width)
      $image->resizeToWidth($width);
    else
      $image->resizeToHeight($height);
    if ($cache && $fh = fopen($cache_file, 'w+')) {
      fwrite($fh, (string)$image);
      fclose($fh);
    }
    echo (string)$image;
  }
}
