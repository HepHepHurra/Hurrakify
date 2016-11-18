<?php
/*
Plugin Name: Hurrakify
Description: Hurrakify links automatically difficult words to entries in plain language in the Hurraki dictionary. Hurrakify adds to every hard to read word a tooltip.
Author: Hep Hep Hurra (HHH)
Plugin URI: https://wordpress.org/plugins/hurrakify/
Version: 2
Text Domain: hurrakify
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
    $result = wp_remote_get(urldecode($_GET['target']));
    echo $result['body'];
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
        exit(wp_redirect(admin_url('admin.php?page=hurraki_tooltip_functions.php')));
    }
}

function updateOptionFields()
{
    update_option('hurraki_tooltip_key_words_last_update_time',date('Y-m-d'));
    update_option('hurraki_tooltip_wiki',"de");
    update_option('hurraki_tooltip_max_word',"10");
    update_option('hurraki_tooltip_apply_to',"add_hurraki_tooltip_everything");
}

function register_hurraki_tooltip() {
    register_setting('hurraki-settings-group', 'hurraki_tooltip_wiki');
    register_setting('hurraki-settings-group', 'hurraki_tooltip_max_word');
    register_setting('hurraki-settings-group', 'hurraki_tooltip_apply_to');

    register_setting('hurraki-settings-group2', 'hurraki_tooltip_key_words_en');
    register_setting('hurraki-settings-group2', 'hurraki_tooltip_key_words_eo');
    register_setting('hurraki-settings-group2', 'hurraki_tooltip_key_words_de');
    register_setting('hurraki-settings-group2', 'hurraki_tooltip_key_words_last_update_time');
}


add_action('wp_head','add_default_hurraki_tooltip');

function add_default_hurraki_tooltip() {
    global $lang;
    echo "<script>";
    echo "var hurraki_tooltip={};hurraki_tooltip.hurraki_tooltip_wiki='".$lang["wiki_links"][get_option('hurraki_tooltip_wiki')]["url"]."';hurraki_tooltip.read_more_button='".$lang["read_more"][get_option('hurraki_tooltip_wiki')]."';hurraki_tooltip.master_url='".$lang["wiki_links"][get_option('hurraki_tooltip_wiki')]["master_url"]."';";
    echo "hurraki_tooltip.hurraki_tooltip_wiki_api='".$lang["wiki_links"][get_option('hurraki_tooltip_wiki')]["api_url"]."';";
    echo "</script>";

    echo "<style>";

    echo "</style>";
}

function valueToLower (&$value){
    $value = strtolower($value);
}

function theme_slug_filter_the_content( $content ) {

    $type = get_option('hurraki_tooltip_apply_to');

    if ($type == get_post_type() || $type == "add_hurraki_tooltip_everything") {

        $wiki     = get_option('hurraki_tooltip_wiki');
        $keywords = json_decode(get_option('hurraki_tooltip_key_words_' . $wiki));
        $limit=get_option('hurraki_tooltip_max_word',10);

        // Get all text words except HTML tags
        $contentWords = str_word_count(strip_tags($content), 1);

        // Move all values to same register
        array_walk($contentWords, 'valueToLower');
        array_walk($keywords, 'valueToLower');

        // Find words those can be replaced
        $keywordsInContent = array_intersect_key(array_count_values($contentWords), array_flip($keywords));

        // Sort, most used on top
        arsort($keywordsInContent);
        // Limit apply
        $keywordsInContent = array_slice($keywordsInContent, 0 ,$limit);

        foreach (array_keys($keywordsInContent) as $keyword) {
            $search  = '/\b(' . $keyword . ')\b(?!(?:[^<]+)?>)/i';
            $replace ="<span class='hurraki_tooltip' data-title='\$0' style='border-bottom:2px dotted #888;'>\$0</span>";
            $content = preg_replace($search, $replace, $content, 1);
        }
    }

    return $content;
}

add_filter('the_content','theme_slug_filter_the_content', 10000);


add_action('wp_footer','hurraki_tooltip_checkDate__');