jQuery(document).ready(function () {
    jQuery('.nav.nav-pills > li').click(function () {
        jQuery('.nav.nav-pills > li').removeClass('active');
        jQuery(this).addClass('active');
    });

    jQuery('#btn_mam_save_gykey').click(function () {
        jQuery.ajax({
            url: '../wp-content/plugins/mass-api-manager/mam_request.php',
            data: { 'kind': 'save_gy_key', 'key': jQuery('#mam_gy_key').val() },
            type: 'post',
            success: function (result) {
                console.log(result);
            }
        });
    });

    jQuery('#btn_mam_create_camp').click(function () {
        var camp_name = jQuery('#mam_camp_name').val();
        var keyword_list = jQuery('#mam_camp_keyword_list').val();
        var camp_table_body = jQuery('#camp_table_body').html();
        jQuery.ajax({
            url: '../wp-content/plugins/mass-api-manager/mam_request.php',
            data: {
                'kind': 'create_camp',
                'camp_name': camp_name,
                'keyword_list': keyword_list
            },
            type: 'post',
            success: function (result) {
                jQuery('#mam_camp_name').val('');
                jQuery('#mam_camp_keyword_list').val('');
                var append = "<tr>" +
                    "<td>" + camp_name + "</td>" +
                    "<td>" + keyword_list + "</td>" +
                    "<td>[mam id='" + result + "']</td>" +
                    "</tr>";
                jQuery('#camp_table_body').html(camp_table_body + append);
            }
        });
    });
});