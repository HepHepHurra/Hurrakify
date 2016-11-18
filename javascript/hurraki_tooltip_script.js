jQuery(document).ready(function() {
    jQuery('.hurraki_tooltip').tooltipster({
        content: 'Loading...',
        contentAsHTML: true,
        interactive: true,
        functionBefore: function(origin, continueTooltip) {
            continueTooltip();
            if (origin.data('ajax') !== 'cached') {

                hurraki_tooltip.CurrentSelectedWord=jQuery(this).attr("data-title")

                jQuery.ajax({
                    type: 'GET',
                    dataType:'json',
                    url: hurraki.ajaxurl,
                    data: {
                        action: 'hurraki_tooltip_proxy',
                        target: hurraki_tooltip.hurraki_tooltip_wiki_api+jQuery(this).attr("data-title"),
                    },
                    success: function(data) {

                        var MoreLink='<a href="'+hurraki_tooltip.hurraki_tooltip_wiki+'/wiki/'+hurraki_tooltip.CurrentSelectedWord+'" target="_blank">'+hurraki_tooltip.read_more_button+'</a>';

                        var replacedContents=data.parse.text["*"].replace(/<img[^>]*>/g,"").replace(/<table[^>]*>/g,"").replace(/(<a.*?href\s*=\s*[\"'])\s*/ig,"$1"+hurraki_tooltip.master_url+"");

                        replacedContents=replacedContents.replace(new RegExp((hurraki_tooltip.master_url+"http"),"g"), 'http')

                        replacedContents=replacedContents.replace(/<a\s+href=/gi, '<a target="_blank" href=')+"<br>"+MoreLink;

                        origin.tooltipster('content', replacedContents).data('ajax', 'cached');
                    }
                });
            }
        }
    });
});