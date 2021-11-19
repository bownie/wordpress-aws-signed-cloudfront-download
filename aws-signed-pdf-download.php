<?php
defined( 'ABSPATH' ) OR exit;
/*
Plugin Name: AWS Signed PDF Download
Description: Generates signed urls for downloading a PDF from Cloudfront
Version: 1.0.0
Author: Richard Bown

Copyright 2021 Tulipesque 

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

register_activation_hook(   __FILE__, array( 'AWSSignedPDFDownload', 'aws_signed_pdf_download_activation' ) );
register_deactivation_hook(   __FILE__, array( 'AWSSignedPDFDownload', 'aws_signed_pdf_download_deactivation' ) );
register_uninstall_hook(    __FILE__, array( 'AWSSignedPDFDownload', 'aws_signed_pdf_download_uninstall' ) );

add_action( 'plugins_loaded', array( 'AWSSignedPDFDownload', 'init' ) );

add_shortcode('wp-pdf-sign', array ('AWSSignedPDFDownload', 'get_signed_PDF_from_shortcode' ) );

class AWSSignedPDFDownload
{

  protected static $instance;

  public static function init()
  {
      is_null( self::$instance ) AND self::$instance = new self;
      return self::$instance;
  }

  public function __construct()
  {
    require_once(plugin_dir_path(__FILE__) . '/aws-signed-pdf-download-options.php');
    new AWSSignedPDFDownload_Options();

    add_filter('wp_get_attachment_url', array($this,'get_signed_PDF_Download', "Empty Label", "file.txt"),100);
  }

  function get_signed_PDF_from_shortcode($atts = array(), $content = null) 
  {
    $label = "Download Link";
    $downloadFile = "download.txt";

    if (array_key_exists("label", $atts)) {
      $label = $atts['label'];
    }

    if (array_key_exists("filename", $atts)) {
      $downloadFile = $atts['filename'];
    }

    return self::get_signed_PDF_Download($content, $label, $downloadFile);
  }

  // Create a Signed PDF_Download label for media assets stored on S3 and served up via CloudFront
  //
  function get_signed_PDF_Download($resource, $label, $downloadFile)
  {
    $options = get_option('aws_signed_pdf_download_settings');

    $expires = time() + $options['aws_signed_pdf_download_lifetime'] * 60; // Convert timeout to seconds
    $json = '{"Statement":[{"Resource":"'.$resource.'","Condition":{"DateLessThan":{"AWS:EpochTime":'.$expires.'}}}]}';

    // Read the private key
    $key = openssl_get_privatekey($options['aws_signed_pdf_download_pem']);
    if(!$key)
    {
      error_log( 'Failed to read private key: '.openssl_error_string() );
      return $resource;
    }

    // Sign the policy with the private key
    if(!openssl_sign($json, $signed_policy, $key, OPENSSL_ALGO_SHA1))
    {
      error_log( 'Failed to sign url: '.openssl_error_string());
      return $resource;
    }

    // Create signature
    //
    $base64_signed_policy = base64_encode($signed_policy);
    $signature = str_replace(array('+','=','/'), array('-','_','~'), $base64_signed_policy);

    // Construct the return
    // 
    //
    //header("Content-type:application/pdf");

    $url = $resource.'?Expires='.$expires.'&Signature='.$signature.'&Key-Pair-Id='.$options['aws_signed_pdf_download_key_pair_id'];

    //header("Location: " + url);
    // Use basename() function to return the base name of file
    //$file_name = basename($url);
    
    // Use file_get_contents() function to get the file
    // from url and use file_put_contents() function to
    // save the file by using base name
    //if(file_put_contents( $file_name,file_get_contents($url))) {
        //echo "File downloaded successfully";
    //}
    //else {
        //echo "File downloading failed.";
    //}
    //
    //$pluginDir = plugin_dir_path(__FILE__) . '/'; //'./'; //dirname(__FILE__);

    if ($label === "") {
      $label = "Download Button";
    }

    $encodeUrl = urlencode($url);
    $pluginDir = '/wp-content/plugins/wordpress-aws-signed-pdf-download';
    //$button_string = "<p><a href='{$pluginDir}/download.php?file={$url}' target='_blank'>Download PDF</a></p>";
    $button_string = "<p><a href='{$pluginDir}/download.php?filename={$downloadFile}&downloadUrl={$encodeUrl}'>{$label}</a></p>";

    return $button_string;
  }

  public static function aws_signed_pdf_download_activation() 
  {
    if ( ! current_user_can( 'activate_plugins' ) )
        return;
    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
    check_admin_referer( "activate-plugin_{$plugin}" );

    // Uncomment the following line to see the function in action
    // exit( var_dump( $_GET ) );
  }

  public static function aws_signed_pdf_download_deactivation() {
    if ( ! current_user_can( 'activate_plugins' ) )
        return;
    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
    check_admin_referer( "deactivate-plugin_{$plugin}" );

    // Uncomment the following line to see the function in action
    // exit( var_dump( $_GET ) );
  }

  public static function aws_signed_pdf_download_uninstall() {
    if ( ! current_user_can( 'activate_plugins' ) )
        return;
    check_admin_referer( 'bulk-plugins' );

    // Important: Check if the file is the one
    // that was registered during the uninstall hook.
    if ( __FILE__ != WP_UNINSTALL_PLUGIN )
        return;

    // Uncomment the following line to see the function in action
    // exit( var_dump( $_GET ) );
  }

}
