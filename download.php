<?php

  $downloadUrl = $_GET['downloadUrl'];
  $downloadFilename = $_GET['filename'];

  //echo "File {$file}";
  // Enable Error Reporting and Display:
  error_reporting(~0);
  ini_set('display_errors', 1);
 
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $downloadUrl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  
  $tmp_file = "/tmp/".$downloadFilename.getmypid();
  $st = curl_exec($ch);
  $fd = fopen($tmp_file, 'w');
  fwrite($fd, $st);
  fclose($fd);

  curl_close($ch);

  echo "File downloaded and written successfully";
  header('Content-type: application/pdf');
  header('Content-Disposition: attachment; filename='.$downloadFilename);
  readfile($tmp_file);

  unlink($tmp_file);
?>
