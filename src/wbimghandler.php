<?php

/**
 * Configuration
 * @var array
 */
  $cfg = array(
    'cache_active' => true,
    'cache_path'   => '.cache',
    'file_octal'   => 0644,
    'folder_octal' => 0755,
    'max_width'    => null,
    'min_width'    => null,
    'max_height'   => null,
    'min_height'   => null,
    'extensions_allowed' => array(
      'jpg' => array(
        'extensions' => array('jpg', 'jpeg'),
        'content_type' => 'image/jpeg'
        ),
      'gif' => array(
        'extensions' => array('gif'),
        'content_type' => 'image/gif'
        ),
      'png' => array(
        'extensions' => array('png'),
        'content_type' => 'image/png'
        ),
      'bmp' => array(
        'extensions' => array('bmp'),
        'content_type' => 'image/bmp'
        ),
      ),
    /** TODO: Implement request filters
    'request_filter' => array(
      array(
        'width' => array('min' => 100, 'max' => 500),
        'height' => 360
        ),
      array(
        'crop' => true,
        ),
      array(
        'scale' => 100,
        ),
      ),
      */
    );

/**
 * Initialize Location
 */
  define('APP_DIR', substr($_SERVER['SCRIPT_FILENAME'], 0, strlen($_SERVER['SCRIPT_FILENAME']) - strlen(basename(__FILE__))));

/**
 * Include Libraries
 * https://github.com/eventviva/php-image-resize
 */
  require_once '../vendor/autoload.php';

/**
 * Debug Script
 */
  if( !function_exists('inspect') ){
    function inspect(){
      echo '<pre>' . print_r(func_get_args(), true) . '</pre>';
    }
  }

/**
 * Stage Request
 */
  $width  = (int)@$_REQUEST['w'] ?: (int)@$_REQUEST['width'];
  $height = (int)@$_REQUEST['h'] ?: (int)@$_REQUEST['height'];
  $crop   = array_key_exists('c', $_REQUEST) ?: array_key_exists('crop', $_REQUEST);
  $scale  = (real)$_REQUEST['s'] ?: (real)$_REQUEST['scale'];
  $file   = pathinfo(preg_replace('/\.+\//','', (string)$_REQUEST['f'] ?: (string)$_REQUEST['file']));

/**
 * Initialize Location
 */
  if (
    !$file
    || !is_dir($file['dirname'])
    || !is_file($file['dirname'] . DIRECTORY_SEPARATOR . $file['basename'])
    || !is_readable($file['dirname'] . DIRECTORY_SEPARATOR . $file['basename'])
    || strpos($file['dirname'], APP_DIR) !== 0
    ) {
    die(__LINE__.': '.__FILE__);
    exit;
  }
  $file['path'] = $file['dirname'] . DIRECTORY_SEPARATOR . $file['basename'];

// Validate Extension (by filename)
// Push Header
  $content_type = null;
  foreach ($cfg['extensions_allowed'] AS $extension_allowed) {
    if (in_array($file['extension'], $extension_allowed['extensions'])) {
      $content_type = $extension_allowed['content_type'];
    }
  }
  if (!$content_type) {
    exit;
  }
  header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($file['path'])).' GMT', true, 200);
  header('Content-type: ' . $content_type);

// Passthru
  if (
    !$width
    && !$height
    && !$scale
    ) {
    echo file_get_contents($file['path']);
  }

// Proxy Modifications
  else {

    // Prepare Cache Target
      $cache_target = null;
      if ($cfg['cache_active']) {
        if ($cfg['cache_path']) {
          $file['cache_path'] = $cfg['cache_path'] . DIRECTORY_SEPARATOR . substr($file['dirname'], strlen(APP_DIR));
          if (!is_dir($file['cache_path'])) {
            @mkdir($file['cache_path'], $cfg['folder_octal'], true);
          }
        }
        else {
          $file['cache_path'] = $file['dirname'];
        }
        if (is_dir($file['cache_path'])) {
          $cache_file = array($file['filename']);
          if ($scale)
            $cache_file[] = 'scaled-'.$scale;
          if ($width && $height && $crop)
            $cache_file[] = 'cropped-w'.$width.'-h'.$height;
          else if ($width && $height)
            $cache_file[] = 'resized-w'.$width.'-h'.$height;
          else if ($width)
            $cache_file[] = 'resized-w'.$width;
          else if ($height)
            $cache_file[] = 'resized-h'.$height;
          $cache_file[] = $file['extension'];
          $file['cache_file'] = implode('.', $cache_file);
          $cache_target = $file['cache_path'] . DIRECTORY_SEPARATOR . $file['cache_file'];
          if (
            !is_writable($file['cache_path'])
            || is_file($cache_target) && !is_writable($cache_target)
            ) {
            $cache_target = null;
          }
        }
      }

    // Read Cache
      if (
        $cache_target
        && is_readable($cache_target)
        && filemtime($cache_target) > filemtime($file['path'])
        ){
        echo file_get_contents($cache_target);
      }

    // Process Image
      else {

        // Create and Modify image
          $image = new \Eventviva\ImageResize($file['path']);
          if ($scale)
            $image->scale($scale);
          if ($width && $height && $crop)
            $image->crop($width, $height);
          else if ($width && $height)
            $image->resizeToBestFit($width, $height);
          else if($width)
            $image->resizeToWidth($width);
          else if($height)
            $image->resizeToHeight($height);

        // Cache
          if ($cache_target && $fh = fopen($cache_target, 'w+')) {
            fwrite($fh, (string)$image);
            fclose($fh);
            @chmod($cache_target, $cfg['file_octal']);
          }

        // Dump
          echo (string)$image;

      }
  }
