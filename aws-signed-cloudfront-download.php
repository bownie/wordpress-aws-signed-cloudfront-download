<?php
/*
Plugin Name: AWS Signed Cloudfront Download
Description: Generates signed urls for downloading content from Cloudfront
Version: 1.1.0
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

class AWSSignedCloudfrontDownload_Options
{

  public function __construct() {
    add_action('admin_menu', array($this, 'aws_signed_cloudfront_download_add_admin_menu'));
    add_action('admin_init', array($this, 'aws_signed_cloudfront_download_settings_init'));
  }


  function aws_signed_cloudfront_download_add_admin_menu() : void {
    add_options_page(__('AWS Signed Cloudfront Download'), __('AWS Signed Cloudfront Download'), 'manage_options', 'aws_signed_cloudfront_download', array($this,'aws_signed_cloudfront_download_options_page'));
  }


  function aws_signed_cloudfront_download_settings_init() : void {

    register_setting('aws_signed_cloudfront_download_pluginPage', 'aws_signed_cloudfront_download_settings', array($this, 'validate_input'));

    add_settings_section(
      'aws_signed_cloudfront_download_pluginPage_section',
      __('CloudFront Key Pair Details', 'wordpress'),
      array($this,'aws_signed_cloudfront_download_settings_section_callback'),
      'aws_signed_cloudfront_download_pluginPage'
    );

    add_settings_field(
      'aws_signed_cloudfront_download_key_pair_id',
      __('CloudFront Key Pair ID', 'wordpress'),
      array($this, 'aws_signed_cloudfront_download_key_pair_id_render'),
      'aws_signed_cloudfront_download_pluginPage',
      'aws_signed_cloudfront_download_pluginPage_section'
    );

    add_settings_field(
      'aws_signed_cloudfront_download_pem',
      __('Private Key PEM', 'wordpress'),
      array($this, 'aws_signed_cloudfront_download_pem_render'),
      'aws_signed_cloudfront_download_pluginPage',
      'aws_signed_cloudfront_download_pluginPage_section'
    );

    add_settings_field(
      'aws_signed_cloudfront_download_lifetime',
      __('Cloudfront Download Lifetime', 'wordpress'),
      array($this, 'aws_signed_cloudfront_download_lifetime_render'),
      'aws_signed_cloudfront_download_pluginPage',
      'aws_signed_cloudfront_download_pluginPage_section'
    );

  }


  function aws_signed_cloudfront_download_key_pair_id_render() : void {
    $options = get_option('aws_signed_cloudfront_download_settings');
    echo "<input type='text' size='25' name='aws_signed_cloudfront_download_settings[aws_signed_cloudfront_download_key_pair_id]' value='{$options['aws_signed_cloudfront_download_key_pair_id']}' />";
  }


  function aws_signed_cloudfront_download_pem_render() : void {
    $options = get_option('aws_signed_cloudfront_download_settings');
    echo "<textarea cols='65' rows='28' style='font-family:Consolas,Monaco,Lucida Console,Liberation Mono,DejaVu Sans Mono,Bitstream Vera Sans Mono,Courier New, monospaced;' name='aws_signed_cloudfront_download_settings[aws_signed_cloudfront_download_pem]'>{$options['aws_signed_cloudfront_download_pem']}</textarea>";
  }


  function aws_signed_cloudfront_download_settings_section_callback() : void {
    echo __('Set the Key Pair ID and the Private key values for creating AWS Signed Cloudfront Download');
  }

  function aws_signed_cloudfront_download_lifetime_render() : void {
    $options = get_option('aws_signed_cloudfront_download_settings');
    if (!array_key_exists('aws_signed_cloudfront_download_lifetime', $options)){
      $options['aws_signed_cloudfront_download_lifetime'] = '5';
    }
    echo "<input type='number' min='1' max='20000' name='aws_signed_cloudfront_download_settings[aws_signed_cloudfront_download_lifetime]' value='{$options['aws_signed_cloudfront_download_lifetime']}'</input> Minutes";
  }

  function aws_signed_cloudfront_download_options_page() : void {
    echo <<< START
    <form action='options.php' method='post'>
    <h2>AWS Signed Cloudfront Download</h2>
    <p>To create CloudFront signed URLs your trusted signer must have its own CloudFront key pair,
     and the key pair must be active. For details see
    <a href=http://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/PrivateContent.html>Serving Private Content through CloudFront</a>
    </p><p>To help secure your applications, AWS recommends that you change CloudFront key pairs every 90 days or more often.</p>

    <p>Use the shortcode around your Cloudfront URL <b>[wp-cloudfront-sign]wrap your URL<[\wp-cloudfront-sign]</b></p>

    <p>You can use the parameters <b>label</b> and <b>filename</b> to set the text label and download filename</p>

START;
    settings_fields('aws_signed_cloudfront_download_pluginPage');
    do_settings_sections('aws_signed_cloudfront_download_pluginPage');
    submit_button();


    echo "</form>";
  }

  function validate_input($input) {
    // Create our array for storing the validated options
    $input['aws_signed_cloudfront_download_key_pair_id'] = trim($input['aws_signed_cloudfront_download_key_pair_id']);
    $input['aws_signed_cloudfront_download_pem'] = trim($input['aws_signed_cloudfront_download_pem']);

    if (strlen($input['aws_signed_cloudfront_download_key_pair_id']) == 0) {
      add_settings_error('aws_signed_cloudfront_download_key_pair_id', '' ,'Key Pair ID must be set', 'error');
    }

    if (strlen($input['aws_signed_cloudfront_download_pem']) == 0) {
      add_settings_error('aws_signed_cloudfront_download_pem', '' ,'Private Key must be set', 'error');
    } else {
      $key = openssl_get_privatekey($input['aws_signed_cloudfront_download_pem']);
      if (!$key) {
        add_settings_error('aws_signed_cloudfront_download_pem', '', 'Cannot parse Private Key. OpenSSL error: ' . openssl_error_string(), 'error');
      }
    }
    return $input;
  }
}

