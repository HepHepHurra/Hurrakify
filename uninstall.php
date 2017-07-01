<?php

if (WP_UNINSTALL_PLUGIN) {
    delete_option("hurraki_tooltip_wiki");
    delete_option("hurraki_tooltip_max_word");
    delete_option("hurraki_tooltip_key_words_en");
    delete_option("hurraki_tooltip_key_words_eo");
    delete_option("hurraki_tooltip_key_words_de");
    delete_option("hurraki_tooltip_key_words_ma");
    delete_option("hurraki_tooltip_key_words_last_update_time");
}
