<?php

function func_basic_settings_page()
{
    global $lang;

    $m=explode("_",get_locale());	
    $locale=$m[0];
    if($locale=="es"){
        $locale="eo";
    }
	
    if($locale!="eo" && $locale!="de" && $locale!="en"){
        $locale="en";
    }


    if(!function_exists('curl_version')) {
?>
        <div id="message" class="updated notice is-dismissible"><p><?php esc_html_e('Your server does not support cURL which is required to use this plugin. Please activate cURL for the plugin to work properly.', 'hurrakify'); ?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">.</span></button></div>
<?php
    }

    $hurraki_tooltip_wiki=get_option('hurraki_tooltip_wiki','de');
?>
    <div class="wrap">
        <h1><?php esc_html_e("Hurrakify Settings", 'hurrakify'); ?></h1>
        <p><?php esc_html_e("Please update settings as per your requirement.", 'hurrakify'); ?></p>
        <form method="post" action="options.php" novalidate="novalidate">
            <?php settings_fields( 'hurraki-settings-group' ); ?>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><label for="blogname"><?php esc_html_e("Select a Easy-to-read Wiki", 'hurrakify'); ?></label></th>
                    <td>
                        <select name="hurraki_tooltip_wiki" id="hurraki_tooltip_wiki">
                            <?php
                            foreach ($lang["wiki_links"] as $key => $value) {
                                if($hurraki_tooltip_wiki==$key){
                                    echo '<option value="' . esc_attr($key) . '" selected="selected">' . esc_html($lang["wiki_links"][$key]['title']) . '</option>';
                                }else{
                                    echo '<option value="' . esc_attr($key) . '">' . esc_html($lang["wiki_links"][$key]['title']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <p class="description" id="tagline-description"><?php esc_html_e("Hurrakify will link to words in this Wiki.", 'hurrakify'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e("Maximum Tooltips", 'hurrakify'); ?></th>
                    <td>
                        <input name="hurraki_tooltip_max_word" type="text" value="<?php echo esc_attr(get_option('hurraki_tooltip_max_word',10)); ?>" size="2" />
                        <p class="description" id="tagline-description"><?php esc_html_e("How many words should Hurrakify link at most? (Default setting is 10)", 'hurrakify'); ?></p>
                    </td>
                </tr>
                </tbody>
            </table>
            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'hurrakify'); ?>"></p>
        </form>
    </div>
<?php
}


function hurrakify_check_and_update_wiki() {
    // Get the last update time
    $last_update_time = get_option('hurraki_tooltip_key_words_last_update_time');
    
    if (empty($last_update_time)) {
        $should_update = true;
    } else {
        $date1 = new DateTime(gmdate('Y-m-d'));
        $date2 = new DateTime($last_update_time);
        $interval = $date1->diff($date2);
        $days_gap = ($interval->y * 365) + ($interval->m * 30) + $interval->d;
        $should_update = ($days_gap > 7); // Changed to 7 days for weekly updates
    }

    if ($should_update) {
        // Use transients to prevent multiple simultaneous updates
        if (get_transient('hurrakify_updating') === false) {
            // Set a transient that expires in 5 minutes
            set_transient('hurrakify_updating', true, 5 * MINUTE_IN_SECONDS);
            
            try {
                hurraki_tooltip_update_wiki_fields();
                delete_transient('hurrakify_updating');
            } catch (Exception $e) {
                delete_transient('hurrakify_updating');
            }
        }
    }
}

function hurraki_tooltip_update_wiki_fields() {
    $allowed_domains = ['hurraki.org', 'hurraki.de'];
    
    // Define API endpoints and their corresponding option names
    $endpoints = [
        'de' => [
            'url' => 'https://hurraki.de/w/api.php?action=parse&page=Hurraki:Artikel_von_A_bis_Z&prop=links&format=json',
            'option' => 'hurraki_tooltip_key_words_de'
        ],
        'en' => [
            'url' => 'https://hurraki.org/english/w/api.php?action=parse&page=Hurraki:Articles_A_to_Z&prop=links&format=json', 
            'option' => 'hurraki_tooltip_key_words_en'
        ],
        'es' => [
            'url' => 'https://hurraki.org/espanol/w/api.php?action=parse&page=Hurraki:Art%C3%ADculos_de_la_A_a_la_Z&prop=links&format=json',
            'option' => 'hurraki_tooltip_key_words_eo'
        ],
        'hu' => [
            'url' => 'https://hurraki.org/magyar/w/api.php?action=parse&page=Hurraki:_Szavak_A-t%C3%B3l_ZS-ig&prop=links&format=json',
            'option' => 'hurraki_tooltip_key_words_ma'
        ],
        'it' => [
            'url' => 'https://hurraki.org/italiano/w/api.php?action=parse&page=Hurraki:Articolo_da_A_a_Z&prop=links&format=json',
            'option' => 'hurraki_tooltip_key_words_it'
        ]
    ];

    // Process each endpoint
    foreach ($endpoints as $lang => $config) {
        $parsed_url = wp_parse_url($config['url']);
        
        if (!isset($parsed_url['host']) || !in_array($parsed_url['host'], $allowed_domains)) {
            continue;
        }

        try {
            $response = json_decode(get_data_url($config['url']), true);
            if (!empty($response['parse']['links'])) {
                $words = array_map(function($item) {
                    return $item['*'];
                }, $response['parse']['links']);
                
                update_option($config['option'], wp_json_encode($words));
            }
        } catch (Exception $e) {
            do_action('hurrakify_log_error', sprintf(
                'Failed to update %s words: %s',
                strtoupper($lang),
                $e->getMessage()
            ));
            continue;
        }
    }

    update_option('hurraki_tooltip_key_words_last_update_time', gmdate('Y-m-d'));
}


function get_data_url($url) {
    $response = wp_remote_get($url, array(
        'timeout' => 30, // Increase timeout to 30 seconds
        'sslverify' => true
    ));
    
    if (is_wp_error($response)) {
        do_action('hurrakify_log_error', 'Hurrakify wp_remote_get error: ' . $response->get_error_message());
        throw new Exception(sprintf(
            /* translators: %s: Error message */
            esc_html__('Failed to fetch data: %s', 'hurrakify'),
            esc_html($response->get_error_message())
        ));
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        do_action('hurrakify_log_error', 'Hurrakify HTTP error: ' . $response_code);
        throw new Exception(sprintf(
            /* translators: %s: HTTP response code */
            esc_html__('HTTP error: %s', 'hurrakify'),
            esc_html($response_code)
        ));
    }
    
    return wp_remote_retrieve_body($response);
}