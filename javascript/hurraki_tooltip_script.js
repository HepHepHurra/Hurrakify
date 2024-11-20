jQuery(document).ready(function() {
    jQuery('.hurraki_tooltip').tooltipster({
        content: 'Loading...',
        contentAsHTML: true,
        interactive: true,
        functionBefore: function(origin, continueTooltip) {
            continueTooltip();
            if (origin.data('ajax') !== 'cached') {
                hurraki_tooltip.CurrentSelectedWord = jQuery(this).attr("data-title");

                // Encode the API URL and word to handle special characters
                // We don't encode the API URL since it's a known valid URL
                // We only encode the word since it may contain special characters
                var encodedWord = encodeURIComponent(hurraki_tooltip.CurrentSelectedWord);

                jQuery.ajax({
                    type: 'GET', 
                    dataType:'json',
                    url: hurraki.ajaxurl,
                    data: {
                        action: 'hurraki_tooltip_proxy',
                        // Don't encode/decode here since the proxy function will urldecode it
                        // The proxy function handles the URL validation and request
                        target: hurraki_tooltip.hurraki_tooltip_wiki_api + encodedWord,
                    },
                    success: function(data) {
                        var replacedContents = data.parse.text["*"]
                            .replace(/<img[^>]*>/g, "")
                            .replace(/<table[^>]*>/g, "")
                            .replace(/(<a.*?href\s*=\s*[\"'])\s*/ig, "$1" + hurraki_tooltip.master_url + "");

                        replacedContents = replacedContents.replace(new RegExp((hurraki_tooltip.master_url + "http"), "g"), 'http');
                        replacedContents = replacedContents.replace(/<a\s+href=/gi, '<a target="_blank" href=');

                        origin.tooltipster('content', replacedContents).data('ajax', 'cached');
                    }
                });
            }
        }
    });
});