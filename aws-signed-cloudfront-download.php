<?php
defined( 'ABSPATH' ) OR exit;
/*
Plugin Name: AWS Signed Cloudfront Download
Description: Generates signed urls for downloading content from Cloudfront
Version: 1.2.0
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

//
// VideoJS
//
define('VIDPHIL_HLS_PLAYER_VERSION', '1.0.4');

register_activation_hook(   __FILE__, array( 'AWSSignedCloudfrontDownload', 'aws_signed_cloudfront_download_activation' ) );
register_deactivation_hook(   __FILE__, array( 'AWSSignedCloudfrontDownload', 'aws_signed_cloudfront_download_deactivation' ) );
register_uninstall_hook(    __FILE__, array( 'AWSSignedCloudfrontDownload', 'aws_signed_cloudfront_download_uninstall' ) );

add_action( 'plugins_loaded', array( 'AWSSignedCloudfrontDownload', 'init' ) );
add_action( 'plugins_loaded', array('AWSSignedCloudfrontDownload', 'plugins_loaded_handler') );
add_action( 'wp_head', array( 'AWSSignedCloudfrontDownload', 'videojs_hls_player_header') );
add_action( 'wp_enqueue_scripts', array( 'AWSSignedCloudfrontDownload', 'videojs_hls_player_enqueue_scripts') );

add_shortcode('videojs_hls', array( 'AWSSignedCloudfrontDownload', 'videojs_hls_video_embed_handler') );

//add_filter('widget_text', 'do_shortcode');
//add_filter('the_excerpt', 'do_shortcode', 11);
//add_filter('the_content', 'do_shortcode', 11);

add_shortcode('wp-cloudfront-sign', array ('AWSSignedCloudfrontDownload', 'get_signed_cloudfront_from_shortcode' ) );

class AWSSignedCloudfrontDownload
{

  protected static $instance;

  public static function init() {
      is_null( self::$instance ) AND self::$instance = new self;
      return self::$instance;
  }

  public static function videojs_hls_video_embed_handler($atts) {

    extract(shortcode_atts(array(
        'url' => '',
        'mp4' => '',
        'webm' => '',
        'ogv' => '',
        'width' => '',
        'controls' => '',
        'preload' => 'auto',
        'autoplay' => 'false',
        'loop' => '',
        'muted' => '',
        'poster' => '',
        'class' => '',
        'inline' => 'false'
        ), $atts));

    if (empty($url))
    {
      return __('You need to specify the HLS src of the video file', 'videojs-hls-player');
    }

    $signedUrl = self::sign_URL($url);
    //echo "URL = ".$signedUrl;

    // SRC Type = application/x-mpegURL
    $src = '
                    <source src="' . $url . '" type="application/x-mpegURL" />
    ';

    if (!empty($mp4))
    {
      $mp4 = '<source src="' . $mp4 . '" type="video/mp4" />
            ';
      $src = $src . $mp4;
    }

    if (!empty($webm))
    {
      $webm = '<source src="' . $webm . '" type="video/webm" />
            ';
      $src = $src . $webm;
    }

    if (!empty($ogv))
    {
      $ogv = '<source src="' . $ogv . '" type="video/ogg" />
            ';
      $src = $src . $ogv;
    }

            // Controls
    if ($controls == 'false') $controls = '';
    else $controls = ' controls';

    // Preload
    if ($preload == 'metadata') $preload = ' preload="metadata"';
    else if ($preload == 'none') $preload = ' preload="none"';
    else $preload = ' preload="auto"';

    // Autoplay
    if ($autoplay == 'true') $autoplay = ' autoplay';
    else $autoplay = '';

    // Loop
    if ($loop == 'true') $loop = ' loop';
    else $loop = '';

    // Muted
    if ($muted == 'true') $muted = ' muted';
    else $muted = '';

    // Poster
    if(!empty($poster)) $poster = ' poster="' . $poster . '"';

    // Controls
    if ($inline == 'false') $inline = '';
    else $inline = ' playsinline';

    $player = "videojs" . uniqid();

    // Custom Style
    //
    $style = '';
    if (!empty($width)){
      $style = '
      <style>
              .videojs-hls-player-wrapper.' . $player . ' {
                      max-width: ' . $width . 'px;
              }
      </style>
      ';
    }

    $output = "";

    // https://github.com/videojs/video.js
    //
    // data-setup=\'{"fluid":true}\''
    // 
    // Video.js Player
    $output = '
    <script>
      var player = videojs("my-player");
    </script>
    <div class="videojs-hls-player-wrapper ' . $player . '">
                  <video id="' . $player . '" class="video-js vjs-default-skin vjs-fluid vjs-16-9 vjs-big-play-centered"' . $controls . $preload . $autoplay . $loop . $muted . $poster . ' data-setup=\'{"fluid":true}\'' . $inline . '>
                          ' . $src . '
                          <p class="vjs-no-js">To view this video please enable JavaScript, and consider upgrading to a web browser that <a href="http://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a></p>
                  </video>
          </div>
      ' . $style . '

    <script>
      videojs("' . $player . '").ready(function() {
          alert("hi");
          //var myPlayer = this;
          // EXAMPLE: Start playing the video.
          //myPlayer.play();
      });
    </script>
    ';

    return $output;
  }

  public static function videojs_hls_player_enqueue_scripts() {
    if (!is_admin())
    {
            $plugin_url = plugins_url('', __FILE__);

            // LOAD ALL JAVASCRIPT
            wp_enqueue_script('jquery');

            wp_register_script(
                    'videojs',
                    '//vjs.zencdn.net/7.17.1/video.js',
                    array('jquery'),
                    VIDPHIL_HLS_PLAYER_VERSION,
                    true
            );
            wp_enqueue_script('videojs', get_template_directory_uri() . '/js/video.js', array(), '1.0.0', true);

            wp_register_script(
              'videojs-ie8',
              '//vjs.zencdn.net/ie8/1.1.2/videojs-ie8.min.js',
              array('jquery'),
              VIDPHIL_HLS_PLAYER_VERSION
                  );
            wp_enqueue_script('videojs-ie8');

            wp_register_script(
              'videojs-custom',
              $plugin_url . '/videojs-hls-player.js',
              array('jquery'),
              VIDPHIL_HLS_PLAYER_VERSION,
              true
              );
            wp_enqueue_script('videojs-custom');

            // LOAD ALL CSS
            wp_register_style(
                    'videojs',
                    '//vjs.zencdn.net/7.8.4/video-js.css'
            );
            wp_enqueue_style('videojs');

            wp_register_style('videojs-style', $plugin_url . '/videojs-hls-player.css');
            wp_enqueue_style('videojs-style');
    }
  }

  public static function videojs_hls_player_header() {
      if (!is_admin())
          {
                  $config = '
  <!-- This site is embedding HLS video using Video.js HLS Plugin v' . VIDPHIL_HLS_PLAYER_VERSION . ' - https://www.socialite-media.com/videojs-hls-player-for-wordpress -->

  ';
          echo $config;
      }
  }

  public static function plugins_loaded_handler() {
      load_plugin_textdomain('videojs-hls-player', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
  }

  public function __construct() {
    require_once(plugin_dir_path(__FILE__) . '/aws-signed-cloudfront-download-options.php');
    new AWSSignedCloudfrontDownload_Options();
  }

  static function get_signed_cloudfront_from_shortcode($atts = array(), $content = null) {
    $label = "Download Link";
    $downloadFile = "download.txt";

    if (array_key_exists("label", $atts)) {
      $label = $atts['label'];
    }

    if (array_key_exists("filename", $atts)) {
      $downloadFile = $atts['filename'];
    }

    return self::get_signed_Cloudfront_Download($content, $label, $downloadFile);
  }

  static function sign_URL($resource) {
    $options = get_option('aws_signed_cloudfront_download_settings');

    $expires = time() + $options['aws_signed_cloudfront_download_lifetime'] * 60; // Convert timeout to seconds
    $json = '{"Statement":[{"Resource":"'.$resource.'","Condition":{"DateLessThan":{"AWS:EpochTime":'.$expires.'}}}]}';

    // Read the private key
    $key = openssl_get_privatekey($options['aws_signed_cloudfront_download_pem']);
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
    $signedResource = $resource.'?Expires='.$expires.'&Signature='.$signature.'&Key-Pair-Id='.$options['aws_signed_cloudfront_download_key_pair_id'];

    //echo "SIGNED URL = ".$signedResource;
    return urlencode($signedResource);
  }

  // Create a Signed Cloudfront_Download label for media assets stored on S3 and served up via CloudFront
  //
  static function get_signed_Cloudfront_Download($resource, $label, $downloadFile) {
    $encodeUrl = self::sign_URL($resource);
    $pluginDir = '/wp-content/plugins/wordpress-aws-signed-cloudfront-download';
    $button_string = "<p><a href='{$pluginDir}/download.php?filename={$downloadFile}&downloadUrl={$encodeUrl}'>{$label}</a></p>";

    return $button_string;
  }

  public static function aws_signed_cloudfront_download_activation() {
    if ( ! current_user_can( 'activate_plugins' ) )
        return;
    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
    check_admin_referer( "activate-plugin_{$plugin}" );

    // Uncomment the following line to see the function in action
    // exit( var_dump( $_GET ) );
  }

  public static function aws_signed_cloudfront_download_deactivation() {
    if ( ! current_user_can( 'activate_plugins' ) )
        return;
    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
    check_admin_referer( "deactivate-plugin_{$plugin}" );

    // Uncomment the following line to see the function in action
    // exit( var_dump( $_GET ) );
  }

  public static function aws_signed_cloudfront_download_uninstall() {
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
