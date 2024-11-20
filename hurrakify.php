<?php
/*
Plugin Name: Hurrakify
Description: Hurrakify links automatically difficult words to entries in plain language in the Hurraki dictionary. Hurrakify adds to every hard to read word a tooltip.
Author: Hep Hep Hurra (HHH)
Plugin URI: https://hurraki.org/
Version: 8.0.1
Text Domain: hurrakify
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

require_once("lang.php");
require_once("hurrakify_tooltip_functions.php");

add_action('admin_menu', 'hurraki_tooltip_setup_menu');
add_action('admin_init', 'register_hurraki_tooltip');
add_action('activated_plugin', 'hurraki_tooltip_plugin_activate' );

add_action('wp_enqueue_scripts', 'hurrakifyEnqueueScripts');
add_action('wp_ajax_hurraki_tooltip_proxy', 'hurraki_tooltip_proxy');
add_action('wp_ajax_nopriv_hurraki_tooltip_proxy', 'hurraki_tooltip_proxy');

function hurraki_tooltip_proxy() {
    $allowed_domains = ['hurraki.org', 'hurraki.de']; // Add trusted domains here

    $target_url = urldecode($_GET['target']);
    $parsed_url = wp_parse_url($target_url);

    if (isset($parsed_url['host']) && in_array($parsed_url['host'], $allowed_domains)) {
        $result = wp_remote_get($target_url);
        $response = wp_remote_retrieve_body($result);
        $data = json_decode($response, true);
        
        if ($data && isset($data['parse']['text']['*'])) {
            // Extract HTML content from JSON
            $html_content = $data['parse']['text']['*'];

            // Check for redirect and construct the correct link
            if (isset($data['parse']['title'])) {
                $title = urlencode($data['parse']['title']);
                
                $wiki = get_option('hurraki_tooltip_wiki');
                global $lang;
                
                // Get base URL and link text from lang.php
                $base_url = $lang['wiki_links'][$wiki]['url'] . '/wiki/';
                $link_text = $lang['read_more'][$wiki];
                
                if (!$base_url || !$link_text) {
                    // Fallback to English if wiki not found
                    $base_url = $lang['wiki_links']['en']['url'] . '/wiki/';
                    $link_text = $lang['read_more']['en'];
                }
                
                $correct_link = $base_url . $title;
                $html_content .= "<p><a href=\"$correct_link\" style=\"color: blue;\">$link_text</a></p>";
            }
            
            // Define allowed HTML elements and attributes
            $allowed_html = array(
                'p' => array(),
                'a' => array(
                    'href' => array(),
                    'target' => array()
                ),
                'br' => array(),
                'div' => array(),
                'span' => array()
            );
            
            // Sanitize the HTML content
            $sanitized_html = wp_kses($html_content, $allowed_html);
            
            // Rebuild JSON structure
            $data['parse']['text']['*'] = $sanitized_html;
            echo wp_json_encode($data);
        } else {
            echo esc_html($response); // Return escaped original response if JSON parsing fails
        }
    } else {
        echo 'Invalid request';
    }
    die;
}

function hurrakifyEnqueueScripts() {
    $hurrakifyPluginDirUrl = plugin_dir_url( __FILE__ );

    wp_enqueue_style('hurraki_tooltip_style', $hurrakifyPluginDirUrl . "lib/tooltipster/css/tooltipster.css");

    wp_enqueue_script('hurraki_tooltip_lib_tooltipster_script', $hurrakifyPluginDirUrl . 'lib/tooltipster/js/jquery.tooltipster.js', 'hurraki_tooltip_script', 1.0, true);

    wp_enqueue_script('hurraki_tooltip_script', $hurrakifyPluginDirUrl . 'javascript/hurraki_tooltip_script.js', 'hurraki_tooltip_script', 1.0, true);
    wp_localize_script('hurraki_tooltip_script', 'hurraki', array('ajaxurl' => admin_url('admin-ajax.php')));
}

add_action('plugins_loaded', 'wan_load_textdomain');
function wan_load_textdomain() {
    load_plugin_textdomain( 'hurrakify', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );
}


function hurraki_tooltip_setup_menu(){
    add_menu_page('Hurrakify Settings','Hurrakify','manage_options','hurraki_tooltip_functions.php', 'func_basic_settings_page' );
}

function hurraki_tooltip_plugin_activate($plugin) {
    if($plugin==plugin_basename( __FILE__ )){
        updateOptionFields();
        hurraki_tooltip_update_wiki_fields();
        wp_safe_redirect(esc_url_raw(admin_url('admin.php?page=hurraki_tooltip_functions.php')));
        exit();
    }
}

function updateOptionFields()
{
    update_option('hurraki_tooltip_key_words_last_update_time',gmdate('Y-m-d'));
    update_option('hurraki_tooltip_wiki',"de");
    update_option('hurraki_tooltip_max_word',"10");
}

function register_hurraki_tooltip() {
    register_setting('hurraki-settings-group', 'hurraki_tooltip_wiki');
    register_setting('hurraki-settings-group', 'hurraki_tooltip_max_word');

    register_setting('hurraki-settings-group2', 'hurraki_tooltip_key_words_en');
    register_setting('hurraki-settings-group2', 'hurraki_tooltip_key_words_eo');
    register_setting('hurraki-settings-group2', 'hurraki_tooltip_key_words_de');
    register_setting('hurraki-settings-group2', 'hurraki_tooltip_key_words_ma');
	register_setting('hurraki-settings-group2', 'hurraki_tooltip_key_words_it');
    register_setting('hurraki-settings-group2', 'hurraki_tooltip_key_words_last_update_time');
}


add_action('wp_head','add_default_hurraki_tooltip');

function add_default_hurraki_tooltip() {
    global $lang;
    echo "<script>";
    // Initialize hurraki_tooltip object first
    echo "var hurraki_tooltip = {};";
    
    $wiki = get_option('hurraki_tooltip_wiki');
    switch($wiki) {
        case 'ma':
            echo "hurraki_tooltip.hurraki_tooltip_wiki_api='https://hurraki.org/magyar/w/api.php?action=parse&format=json&prop=text&section=0&noimages&disablepp&page=';";
            break;
        case 'en':
            echo "hurraki_tooltip.hurraki_tooltip_wiki_api='https://hurraki.org/english/w/api.php?action=parse&format=json&prop=text&section=0&noimages&disablepp&page=';";
            break;
        case 'eo':
            echo "hurraki_tooltip.hurraki_tooltip_wiki_api='https://hurraki.org/espanol/w/api.php?action=parse&format=json&prop=text&section=0&noimages&disablepp&page=';";
            break;
        case 'de':
            echo "hurraki_tooltip.hurraki_tooltip_wiki_api='https://hurraki.de/w/api.php?action=parse&format=json&prop=text&section=0&noimages&disablepp&page=';";
            break;
        case 'it':
            echo "hurraki_tooltip.hurraki_tooltip_wiki_api='https://hurraki.org/italiano/w/api.php?action=parse&format=json&prop=text&section=0&noimages&disablepp&page=';";
            break;
    }
    
    echo "</script>";
}

function valueToLower (&$value){
    $value = strtolower($value);
}

function theme_slug_filter_the_content($content)
{
    $wiki = get_option('hurraki_tooltip_wiki');
    $keywords = json_decode(get_option('hurraki_tooltip_key_words_' . $wiki));
    $limit = get_option('hurraki_tooltip_max_word', 10);

    $foundCounter = 0;
    $Counter = 0;


    foreach ($keywords as $keyword) {

        if ($Counter >= $limit) {
            break;
        }

        $search = '/\b(' . addcslashes(addslashes($keyword), '/') . ')\b(?!(?:[^<]+)?>)/i';
        $replace = "<span class='hurraki_tooltip' data-title='\$0' style='border-bottom:2px dotted #888;'>\$0</span>";
        $content = preg_replace($search, $replace, $content, 1,$foundCounter);  
        if($foundCounter)
          $Counter++;


    }

    return $content;
}

add_filter('the_content','theme_slug_filter_the_content', 10000);

// Remove the current wp_footer hook
remove_action('wp_footer', 'hurraki_tooltip_checkDate__');

// Add activation hook to schedule our event
register_activation_hook(__FILE__, 'hurrakify_schedule_update_check');

// Add deactivation hook to remove the scheduled event
register_deactivation_hook(__FILE__, 'hurrakify_deactivate_schedule');

function hurrakify_schedule_update_check() {
    if (!wp_next_scheduled('hurrakify_weekly_update_check')) {
        wp_schedule_event(time(), 'weekly', 'hurrakify_weekly_update_check');
    }
}

function hurrakify_deactivate_schedule() {
    wp_clear_scheduled_hook('hurrakify_weekly_update_check');
}

// Add the action for our scheduled event
add_action('hurrakify_weekly_update_check', 'hurrakify_check_and_update_wiki');
