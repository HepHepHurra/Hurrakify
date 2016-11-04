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
        <div id="message" class="updated notice is-dismissible"><?php echo $lang["curl_error_mgs"][$locale]?><button type="button" class="notice-dismiss"><span class="screen-reader-text">.</span></button></div>
<?php
    }

    $hurraki_tooltip_wiki=get_option('hurraki_tooltip_wiki','de');
    $hurraki_tooltip_apply_to=get_option('hurraki_tooltip_apply_to','add_hurraki_tooltip_everything');
?>
    <div class="wrap">
        <h1><?php echo $lang["settings_title"][$locale]?></h1>
        <p><?php echo $lang["settings_description"][$locale]?></p>
        <form method="post" action="options.php" novalidate="novalidate">
            <?php settings_fields( 'hurraki-settings-group' ); ?>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><label for="blogname"><?php echo $lang["wiki_links_title"][$locale]?></label></th>
                    <td>
                        <select name="hurraki_tooltip_wiki" id="hurraki_tooltip_wiki">
                            <?php
                            foreach ($lang["wiki_links"] as $key => $value) {
                                if($hurraki_tooltip_wiki==$key){
                                    echo '<option value="'.$key.'" selected="selected">'.$lang["wiki_links"][$key]['title'].'</option>';
                                }else{
                                    echo '<option value="'.$key.'">'.$lang["wiki_links"][$key]['title'].'</option>';
                                }
                            }
                            ?>
                        </select>
                        <p class="description" id="tagline-description"><?php echo $lang["wiki_links_desc"][$locale]?></p>
                    </td>
                </tr>


                <tr>
                    <th scope="row"><label for="blogname"><?php echo $lang["tooltip_on_title"][$locale]?></label></th>
                    <td>
                        <select name="hurraki_tooltip_apply_to" id="hurraki_tooltip_apply_to">
                            <option value="add_hurraki_tooltip_everything">All</option>
                            <?php

                            $types=get_post_types();

                            foreach ($types as $key => $value) {
                                if($hurraki_tooltip_apply_to==$key){
                                    echo '<option value="'.$key.'" selected="selected">'.ucfirst($value).'</option>';
                                }else{
                                    echo '<option value="'.$key.'">'.ucfirst($value).'</option>';
                                }
                            }
                            ?>
                        </select>
                        <p class="description" id="tagline-description"><?php echo $lang["tooltip_on_desc"][$locale]?></p>
                    </td>
                </tr>


                <tr valign="top">
                    <th scope="row"><?php echo $lang["max_limit"][$locale]?></th>
                    <td>
                        <input name="hurraki_tooltip_max_word" type="text" value="<?php echo get_option('hurraki_tooltip_max_word',10); ?>" size="2" />
                        <p class="description" id="tagline-description"><?php echo $lang["max_limit_descrip"][$locale]?></p>
                    </td>
                </tr>
                </tbody>
            </table>
            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
        </form>
    </div>
<?php
}


function hurraki_tooltip_checkDate__()
{
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    if (is_plugin_active('Hurrakify/hurrakify.php')){

        $hurraki_tooltip_key_words_last_update_time=get_option('hurraki_tooltip_key_words_last_update_time');
        $date1 = new DateTime(date('Y-m-d'));
        $date2 = new DateTime($hurraki_tooltip_key_words_last_update_time);
        $interval = $date1->diff($date2);
        $daysGap=($interval->y * 365)+($interval->m * 30)+$interval->d;

        if($daysGap>20){
            hurraki_tooltip_update_wiki_fields();
        }
    }
}

function hurraki_tooltip_update_wiki_fields()
{

    $words_de=array();
    $hurraki_tooltip_key_words_de=json_decode(get_data_url("http://hurraki.de/w/api.php?action=parse&page=Hurraki:Artikel_von_A_bis_Z&prop=links&format=json"), true);
    foreach ($hurraki_tooltip_key_words_de["parse"]["links"] as $_v) {
        $words_de[]=$_v["*"];
    }

    update_option('hurraki_tooltip_key_words_de',json_encode($words_de));

    $words_en=array();
    $hurraki_tooltip_key_words_en=json_decode(get_data_url("http://hurraki.org/english/w/api.php?action=parse&page=Hurraki:Articles_A_to_Z&prop=links&format=json"), true);
    foreach ($hurraki_tooltip_key_words_en["parse"]["links"] as $_v) {
        $words_en[]=$_v["*"];
    }

    update_option('hurraki_tooltip_key_words_en',json_encode($words_en));

    $words_eo=array();
    $hurraki_tooltip_key_words_eo=json_decode(get_data_url("http://hurraki.org/espanol/w/api.php?action=parse&page=Hurraki:Art%C3%ADculos_de_la_A_a_la_Z&prop=links&format=json"), true);
    foreach ($hurraki_tooltip_key_words_eo["parse"]["links"] as $_v) {
        $words_eo[]=$_v["*"];
    }

    update_option('hurraki_tooltip_key_words_eo',json_encode($words_eo));

    update_option('hurraki_tooltip_key_words_last_update_time',date('Y-m-d'));
}


function get_data_url($url)
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $curl_response = curl_exec($curl);
    if ($curl_response === false) {
        $info = curl_getinfo($curl);
        curl_close($curl);
        die('error occured during curl exec. Additioanl info: ' . var_export($info));
    }
    curl_close($curl);
    //$decoded = json_decode($curl_response);
    //return $decoded;

    return $curl_response;
}
