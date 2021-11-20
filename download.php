<?php
//
  // Enable Error Reporting and Display:
  error_reporting(~0);
  ini_set('display_errors', 1);

  $downloadUrl = $_GET['downloadUrl'];
  $downloadFilename = $_GET['filename'];

  // Load for options
  //
  include '/var/www/wordpress/wp-load.php';

  $options = get_option('aws_signed_cloudfront_download_settings', array() );
  //print "Options DDL = ".$options['aws_signed_cloudfront_download_domain_list'];

  // Ensure that the domain we're requesting the domain from is allowed
  // 
  $downloadAllowed = false;
  $variableAry=explode(",", $options['aws_signed_cloudfront_download_domain_list']);

  foreach($variableAry as $var)
  {
    if (strpos($downloadUrl, $var) !== false) {
      $downloadAllowed = true;
      print("MATCHED DOMAIN");
    }
  }

  if ($downloadAllowed === false) {
    die("Not authorised domain");
  }

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
