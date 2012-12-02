<?php
/*
 Plugin Name: GAS Injector
 Plugin URI: http://www.geckosolutions.se/blog/wordpress-plugins/
 Description: GAS Injector for Wordpress will help you add Google Analytics on Steroids (GAS) to your WordPress blog. 
 This will not only add basic Google Analytics tracking but also let you track whitch outbound links your visitors click on, 
 how they use your forms, whitch movies they are watching, how far down on the page are they scrolling. This and more you get by using GAS Injector for Wordpress. 
 Just add your Google Analytics tracking code and your domain to start the tracking.
 Version: 1.0
 Author: Niklas Olsson
 Author URI: http://www.geckosolutions.se
 License: GPL 3.0, @see http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * WP Hooks
 **/
add_action('init', 'load_gas_for_wordpress_translation_file');
add_action('wp_head', 'insert_google_analytics_code_and_domain');
add_action('admin_head', 'admin_register_gas_for_wordpress_head');
add_action('admin_menu', 'add_gas_for_wordpress_options_admin_menu');

/**
 * Loads the translation file for this plugin.
 */
function load_gas_for_wordpress_translation_file() {
  $plugin_path = basename(dirname(__FILE__));
  load_plugin_textdomain('gas_for_wordpress', null, $plugin_path . '/languages/');
}

/**
 * Prints the stylesheet link in the admin head.
 */
function admin_register_gas_for_wordpress_head() {
  $wp_content_url = get_option('siteurl');
  if(is_ssl()) {
    $wp_content_url = str_replace('http://', 'https://', $wp_content_url);
  }
  
  $url = $wp_content_url . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/css/gas_for_wordpress.css';
  echo "<link rel='stylesheet' type='text/css' href='$url' />\n";
}

/**
 * Inserts the Google Analytics tracking code and domain.
 */
function insert_google_analytics_code_and_domain() {
  if (!is_admin() && get_option('ua_tracking_code') != "") {
    echo "<!-- GAS for Wordpress from http://www.geckosolutions.se/blog/wordpress-plugins/ -->\n"; 
    echo get_gas_tracking_code(get_option('ua_tracking_code'), get_option('site_domain_url'));
    echo "\n<!-- / GAS for Wordpress -->\n";
  }
}

/**
 * Get the GAS tracking code based on the users given values for the UA tracking code
 * and the domain url.
 * 
 * @param ua_tracking_code the UA-xxxx-x code from your Google Analytics account.
 * @param site_domain_url the url to use to determine the domain of the tracking.
 * @return the tracking code to render.
 */
function get_gas_tracking_code($ua_tracking_code, $site_domain_url) {
  $gasFile = path_join(WP_PLUGIN_URL, basename(dirname(__FILE__))."/js/gas-1.10.1.min.js");
  $gas_tracking_code = "<script type='text/javascript'>";
  $gas_tracking_code .= "var _gas = _gas || [];";
  $gas_tracking_code .= "
    _gas.push(['_setAccount', '".$ua_tracking_code."']);
    _gas.push(['_setDomainName', '".$site_domain_url."']);
    _gas.push(['_trackPageview']);
    _gas.push(['_gasTrackForms']);
    _gas.push(['_gasTrackOutboundLinks']);
    _gas.push(['_gasTrackMaxScroll']);
    _gas.push(['_gasTrackDownloads']);
    _gas.push(['_gasTrackYoutube', {
      percentages: [25, 50, 75, 90],
      force: true
    }]);
    _gas.push(['_gasTrackVimeo', {force: true}]);
    _gas.push(['_gasTrackMailto']);
    ";
  $gas_tracking_code .= "
    (function() {
    var ga = document.createElement('script');
    ga.type = 'text/javascript';
    ga.async = true;
    ga.src = '".$gasFile."';
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(ga, s);
  })();
  ";
  $gas_tracking_code .= "</script>";
  
  return $gas_tracking_code;
}

/**
 * Add the plugin options page link to the dashboard menu.  
 */
function add_gas_for_wordpress_options_admin_menu() {
  add_options_page(__('GAS for Wordpress Settings', 'gas-injector'), __('GAS for Wordpress Settings', 'gas-injector'), 'manage_options', basename(__FILE__), 'gas_for_wordpress_plugin_options_page'); 
} 

/**
 * The main function that generate the options page for this plugin.
 */
function gas_for_wordpress_plugin_options_page() {

  $tracking_code_err = "";
  if(!isset($_POST['update_gas_for_wordpress_plugin_options'])) {
    $_POST['update_gas_for_wordpress_plugin_options'] == 'false';
  } 
  
  if ($_POST['update_gas_for_wordpress_plugin_options'] == 'true') {
    
  	$errors = gas_for_wordpress_plugin_options_update(); 
  
    if (is_wp_error($errors)) {
      $tracking_code_err = $errors->get_error_message('tracking_code');
    }
  }
  ?>
    <div class="wrap">
    	<div class="gai-col1">
        <div id="icon-themes" class="icon32"><br /></div>
        <h2><?php echo __('GAS for WordPress Settings', 'gas-injector'); ?></h2>
  
        <form method="post" action="">
          
          <h4 style="margin-bottom: 0px;"><?php echo __('Google Analytics tracking code (UA-xxxx-x)', 'gas-injector'); ?></h4>
          <?php 
            if ($tracking_code_err) {
              echo '<div class="errorMsg">'.$tracking_code_err.'</div>';
            }
          ?>
          <input type="text" name="ua_tracking_code" id="ua_tracking_code" value="<?php echo get_option('ua_tracking_code'); ?>" style="width:400px;"/>
          
          <h4 style="margin-bottom: 0px;"><?php echo __('Your domain eg. .mydomain.com', 'gas-injector'); ?></h4>
          <input type="text" name="site_domain_url" id="site_domain_url" value="<?php echo get_option('site_domain_url'); ?>" style="width:400px;"/>
          
          <input type="hidden" name="update_gas_for_wordpress_plugin_options" value="true" />
          <p><input type="submit" name="search" value="<?php echo __('Update Options', 'gas-injector'); ?>" class="button" /></p>
        </form>
      </div>
      <div class="gai-col2">
      
      	<div class="description">
      		<?php 
      		  echo __('Enter the tracking code from the Google Analytics account you want to use for this site. None of the java script code will be inserted if you leave this field empty. (eg. the plugin will be inactive) ', 'google-analytics-injector');
      		  
      		  $images_path = path_join(WP_PLUGIN_URL, basename(dirname(__FILE__))."/images/");
      		  $external_icon = '<img src="'.$images_path.'external_link_icon.png" title="External link" />';
      		  
      		  printf(__('Go to <a href="http://www.google.com/analytics/" target="_blank">Google Analytics</a> %s and get your tracking code.', 'google-analytics-injector'), $external_icon);
      		?>
      	</div>
      	
      	<div class="description">
      	  <?php echo __('This plugin exclude the visits from the Administrator if he/she is currently logged in.', 'gas-injector'); ?>
      	</div>
      	
      	<div class="description">
      	  <?php printf(__('This plugin is created by Gecko Solutions. Find more plugins at <br /><a href="http://www.geckosolutions.se/blog/wordpress-plugins/">Gecko Solutions plugins</a> %s', 'gas-injector'), $external_icon); ?>
      	</div>
      	
      </div>
    </div>
  <?php
}

/**
 * Update the GAS Injector plugin options. 
 */
function gas_for_wordpress_plugin_options_update() {
  
  
  
  if(isset($_POST['ua_tracking_code'])) {
    update_option('ua_tracking_code', $_POST['ua_tracking_code']);
  } 
  
  if(isset($_POST['ua_tracking_code']) && !isValidUaCode($_POST['ua_tracking_code'])) {
    $errors = new WP_Error('tracking_code', __('The tracking code is on the wrong format', 'gas-injector'));
  }
  
  if(isset($_POST['site_domain_url'])) {
    update_option('site_domain_url', $_POST['site_domain_url']);
  }
  return $errors;
}

/**
 * Validate the format of the given Google Analytics tracking code.
 * @param $ua_tracking_code the given Google Analytics tracking code to validate.
 */
function isValidUaCode($ua_tracking_code) {
  if($ua_tracking_code == "" || preg_match('/^UA-\d{4,9}-\d{1,2}$/', $ua_tracking_code)) {
    return true;
  }
  return false;
}
