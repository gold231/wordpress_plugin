jQuery(document).ready(function(){
    jQuery('.mam_network_item a').mouseover(function(){
        jQuery('.mam_campaign_main_container a').attr('href', jQuery(this).attr('href'));
        jQuery('.mam_campaign_main_container a img').attr('src', jQuery(jQuery(this).find('img')).attr('src'));
        jQuery('.mam_campaign_main_container a:last-child span').text(jQuery(jQuery(this).next()).val());
    });
});