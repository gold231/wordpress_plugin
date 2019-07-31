<?php

/*
Plugin Name: Mass Api Manager
Plugin URI: 
Description: 
Author: Sam Khan
Developer: Gaudin Canddy
Version: 1.0.0
Author URI: 
Tested up to: 
*/



if ((isset($_GET['page']) && ((!empty($_GET['page']) || ('mass-api-manager' === $_GET['page'])))) || (isset($_GET['tab']) && ((!empty($_GET['tab']) || ('other_plugins' === $_GET['tab']))))) {
    add_action('admin_enqueue_scripts', 'mam_load_my_script');
}

function mam_load_my_script()
{
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js', array(), 'all');
    wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js', array(), 'all');
    // wp_enqueue_script('admin-js', plugin_dir_url(__FILE__) . 'assets/js/admin.js');
    // wp_enqueue_script('public-js', plugin_dir_url(__FILE__) . 'assets/js/public.js');
    wp_enqueue_script('jquery-colorpicker-js', plugin_dir_url(__FILE__) . 'assets/js/jquery.colorpickersliders.js');
    wp_enqueue_script('custom-mam-js', plugin_dir_url(__FILE__) . 'assets/js/custom.js', array(), 'all');
    // wp_localize_script('custom-js', 'adminajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'ajax_icon' => plugin_dir_url(__FILE__) . '/images/ajax-loader.gif'));
}

if ((isset($_GET['page']) && !empty($_GET['page']) && ('mass-api-manager' === $_GET['page'])) || (isset($_GET['page']) && !empty($_GET['page']) && ('mass-api-manager' === $_GET['page']) && isset($_GET['tab']) && !empty($_GET['tab']) && ('about' === $_GET['tab']))) {
    add_action('admin_enqueue_scripts', 'mam_styles');
}

function mam_styles()
{
    wp_enqueue_style('bootstrap-mam-css', "https://stackpath.bootstrapcdn.com/bootswatch/4.3.1/cerulean/bootstrap.min.css");
    wp_enqueue_style('admin-mam-css', plugin_dir_url(__FILE__) . 'assets/css/admin.css');
    wp_enqueue_style('style-mam-css', plugin_dir_url(__FILE__) . 'assets/css/style.css');
}

function mam_pages_posts_creator()
{
    add_menu_page('Mass Api Manager', 'Mass Api Manager', 'manage_options', 'mass-api-manager', 'mam_create', plugin_dir_url(__FILE__) . 'assets/images/logo.png');
}

add_action('admin_menu', 'mam_pages_posts_creator');

add_shortcode('mam', function ($attr, $content) {
    wp_enqueue_script('custom-front-mam-js', plugin_dir_url(__FILE__) . 'assets/js/custom_front.js', array(), 'all');
    wp_enqueue_style('bootstrap-mam-css', "https://stackpath.bootstrapcdn.com/bootswatch/4.3.1/cerulean/bootstrap.min.css");
    wp_enqueue_style('style-front-mam-css', plugin_dir_url(__FILE__) . 'assets/css/style_front.css');

    global $wpdb;
    $sql = 'select * from ' . $wpdb->prefix . 'mam_campaign where id=' . $attr['id'];
    $campaign = $wpdb->get_row($sql);
    if (!empty($campaign)) {
        $campaign_name = $campaign->name;
        $keywords = str_replace("\n", "|", $campaign->keywords);
        $sql = 'select * from ' . $wpdb->prefix . 'mam_auth where user_id=' . get_current_user_id();
        $auth = $wpdb->get_row($sql);
        $key = $auth->api_key;

        $yt_base_url = 'https://www.googleapis.com/youtube/v3/search?part=snippet&maxResults=20&order=rating';
        $yt_url = $yt_base_url . '&key=' . $key . '&q=' . $keywords;
        $data_json = file_get_contents($yt_url);
        $data = json_decode($data_json, true);
        $count = count($data['items']);
        $result = '<div class="container mam_campaign_container">
        <h3>' . $campaign_name . '</h3>';

        $result .= '<div class="row mam_campaign_footer_container"><table><tr>';
        for ($i = 0; $i < $count; $i++) {
            $item = $data['items'][$i];
            $video_id = $item['id']['videoId'];
            $video_title = $item['snippet']['title'];
            $video_thumbnail = $item['snippet']['thumbnails']['medium']['url'];
            $result .= '<td><div class="mam_network_item">
                    <a href="https://www.youtube.com/watch?v=' . $video_id . '" target="_blank"><img src=' . $video_thumbnail . '></a>
                    <input type="hidden" value="' . $video_title . '">
                </div></td>';
        }
        $result .= '</tr></table></div>';

        $result .= '<div class="row">';
        $item = $data['items'][0];
        $video_id = $item['id']['videoId'];
        $video_title = $item['snippet']['title'];
        $video_thumbnail = $item['snippet']['thumbnails']['medium']['url'];
        $result .= '<div class="col-md-12 mam_campaign_main_container">
                <a href="https://www.youtube.com/watch?v=' . $video_id . '" target="_blank"><img src=' . $video_thumbnail . '></a>
                <a href="https://www.youtube.com/watch?v=' . $video_id . '" class="mam_network_anchor" target="_blank"><span>' . $video_title . '</span></a>
            </div></div></div>';
        return $result;
    } else {
        return;
    }
});

function db_process()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'mam_campaign';
    if ($wpdb->get_var('SHOW TABLES LIKE ' . $table_name) != $table_name) {
        $sql = 'create table ' . $table_name . '(
            id int unsigned auto_increment, 
            user_id int, 
            name varchar(255), 
            keywords varchar(255), 
            create_date timestamp default current_timestamp, 
            primary key (id)
        )';
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $wpdb->query($sql);
    }

    $table_name = $wpdb->prefix . 'mam_auth';
    if ($wpdb->get_var('SHOW TABLES LIKE ' . $table_name) != $table_name) {
        $sql = 'create table ' . $table_name . '(
            id int unsigned auto_increment, 
            user_id int, 
            api_key varchar(255), 
            primary key (id)
        )';
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $wpdb->query($sql);
    }
}

register_activation_hook(__FILE__, "db_process");

function mam_create()
{
    ?>
    <div class="wrap">
        <h2><?php _e('Mass Api Manager', 'mass-api-manager'); ?></h2>

        <!-- Nav pills -->
        <ul class="nav nav-pills section-tabs">
            <li class="nav-item">
                <span class="nav-link" data-toggle="pill" href="#campaign">CAMPAIGN</span>
            </li>
            <li class="nav-item">
                <span class="nav-link" data-toggle="pill" href="#newtemplate">TEMPLATE</span>
            </li>
            <li class="nav-item active">
                <span class="nav-link" data-toggle="pill" href="#feeds">FEEDS</span>
            </li>
            <li class="nav-item">
                <span class="nav-link" data-toggle="pill" href="#auth">AUTH</span>
            </li>
            <li class="nav-item">
                <span class="nav-link" data-toggle="pill" href="#settings">SETTINGS</span>
            </li>
            <li class="nav-item">
                <span class="nav-link" data-toggle="pill" href="#logs">LOGS</span>
            </li>
        </ul>
        <!-- Tab panes -->
        <div class="tab-content">
            <div class="tab-pane container fade" id="campaign">
                <div id="accordion_campaign">
                    <div class="card">
                        <div class="card-header">
                            <a class="card-link btn btn-info" data-toggle="collapse" href="#collapseOne">
                                Create Campaign
                            </a>
                        </div>
                        <div id="collapseOne" class="collapse show" data-parent="#accordion_campaign">
                            <div class="card-body">
                                <table class="form-table">
                                    <tbody>
                                        <tr class="campaign_name_tr">
                                            <th>Name of Campaign</th>
                                            <td><input type="text" class="regular-text" value="" id="mam_camp_name" name="campaign_name" required></td>
                                        </tr>
                                        <tr class="keyword_list_tr">
                                            <th>Keyword List</th>
                                            <td>
                                                <textarea class="code" id="mam_camp_keyword_list" cols="60" rows="5" name="keyword_list"></textarea>
                                                <input type="button" id="btn_keywords_import" class="btn btn-success vertical_top" value="Import">
                                                <p class="description">Please click import button to import keywords using text file.</p>
                                            </td>
                                        </tr>
                                        <tr class="secondary_keyword_tr">
                                            <th>Secondary Main Keyword</th>
                                            <td><input type="text" class="regular-text" value="" id="secondary_keyword" name="secondary_keyword"></td>
                                        </tr>
                                        <tr class="category_tr">
                                            <th>Category to Post</th>
                                            <td><input type="text" class="regular-text" value="" id="category" name="category"></td>
                                        </tr>
                                        <tr class="select_template_tr">
                                            <th>Select Template</th>
                                            <td><input type="file" value="" id="select_template" name="select_template"></td>
                                            <td><input type="button" id="btn_mam_create_camp" class="btn btn-success" value="Create Campaign"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <a class="collapsed card-link btn btn-info" data-toggle="collapse" href="#collapseTwo">
                                List of Campaign
                            </a>
                        </div>
                        <div id="collapseTwo" class="collapse" data-parent="#accordion_campaign">
                            <div class="card-body">
                                <table class="form-table">
                                    <tbody id="camp_table_body">
                                        <tr class="keyword_list_tr">
                                            <th>Campain Name</th>
                                            <th>Description</th>
                                            <th>Short code</th>
                                        </tr>
                                        <?php
                                        global $wpdb;
                                        $sql = 'select * from ' . $wpdb->prefix . 'mam_campaign where user_id=' . get_current_user_id() . ' order by id desc';
                                        $campaigns = $wpdb->get_results($sql);
                                        foreach ($campaigns as $campaign) {
                                            ?>
                                            <tr>
                                                <td><?php echo $campaign->name ?></td>
                                                <!-- <td>40 keywords processed out of 200</td> -->
                                                <td><?php echo $campaign->keywords ?></td>
                                                <td>[mam id="<?php echo $campaign->id ?>"]</td>
                                            </tr>
                                        <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane container fade" id="newtemplate">
                <div id="accordion_template">
                    <div class="card">
                        <div class="card-header">
                            <a class="card-link btn btn-info" data-toggle="collapse" href="#collapseOne_temp">
                                Create Template
                            </a>
                        </div>
                        <div id="collapseOne_temp" class="collapse show" data-parent="#accordion_template">
                            <div class="card-body">
                                <table class="form-table">
                                    <tbody>
                                        <tr class="campaign_name_tr">
                                            <th>Template Title</th>
                                            <td><input type="text" class="regular-text" value="" id="campaign_name" name="campaign_name"></td>
                                        </tr>
                                        <tr class="keyword_list_tr">
                                            <th>Content</th>
                                            <td>
                                                <textarea class="code" id="keyword_list" cols="60" rows="5" name="keyword_list"></textarea>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <input type="button" id="btn_create_template" class="btn btn-success" value="Create Template">
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <a class="collapsed card-link btn btn-info" data-toggle="collapse" href="#collapseTwo_temp">
                                List of Template
                            </a>
                        </div>
                        <div id="collapseTwo_temp" class="collapse" data-parent="#accordion_template">
                            <div class="card-body">
                                <table class="form-table">
                                    <tbody>
                                        <tr class="keyword_list_tr">
                                            <th>Template Name</th>
                                            <td>
                                                <!-- <textarea class="code" id="campain_list" cols="130" rows="10" name="keyword_list">
                                                    </textarea> -->
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane container active" id="feeds">
                <div class="section-sources" id="sources-list" data-view-mode="sources-list">
                    <div class="section" id="feeds-list-section">
                        <h1 class="desc-following">
                            <span>List of feeds</span>
                            <span class="admin-button btn btn-success button-add" data-toggle="modal" data-target="#feedsModal">Create feed</span>
                        </h1>
                        <p class="desc">Each feed can be connected to multiple streams. Cache for feed is being built immediately on creation. You can disable any feed and it will be disabled in all streams where it's connected. Feeds with errors are automatically disabled. <a class="ff-pseudo-link" href="#">Show only error feeds</a>.</p>
                        <div id="feeds-view">
                            <table class="feeds-list">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Feed</th>
                                        <th></th>
                                        <th>Settings</th>
                                        <th>Last update</th>
                                        <th>Live</th>
                                    </tr>
                                </thead>
                                <tbody id="feeds-list">
                                    <tr data-uid="cv99734" data-network="youtube" class="feed-enabled animated yes">
                                        <td class="controls"><i class="flaticon-tool_more"></i>
                                            <ul class="feed-dropdown-menu">
                                                <li data-action="filter">Filter feed</li>
                                                <li data-action="cache">Rebuild cache</li>
                                            </ul><i class="flaticon-tool_edit"></i> <i class="flaticon-copy"></i> <i class="flaticon-tool_delete"></i>
                                        </td>
                                        <td class="td-feed"><i class="flaticon-youtube"></i></td>
                                        <td class="td-status"><span class="cache-status-ok"></span></td>
                                        <td class="td-info"><span><span class="highlight">pcos ayuverda</span></span><span><span class="highlight">search</span></span><span><span class="highlight highlight-id">ID: cv99734</span></span></td>
                                        <td class="td-last-update">Jul 28 22:16 (Every hour)</td>
                                        <td class="td-enabled"><label for="feed-enabled-cv99734"><input checked="" id="feed-enabled-cv99734" class="switcher" type="checkbox" name="feed-enabled-cv99734" value="yep">
                                                <div>
                                                    <div></div>
                                                </div>
                                            </label></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="holder"><a class="jp-previous jp-disabled">←</a><a class="jp-current">1</a><span class="jp-hidden">...</span><a class="jp-next jp-disabled">→</a></div>
                            <div class="popup">
                                <div class="section">
                                    <i class="popupclose flaticon-close-4"></i>
                                    <div class="networks-choice add-feed-step">
                                        <h1>Create new feed</h1>
                                        <ul class="networks-list">
                                            <li class="network-twitter" data-network="twitter" data-network-name="Twitter">
                                                <i class="flaticon-twitter"></i>
                                            </li>
                                            <li class="network-facebook" data-network="facebook" data-network-name="Facebook">
                                                <i class="flaticon-facebook"></i>
                                            </li>
                                            <li class="network-instagram" data-network="instagram" data-network-name="Instagram">
                                                <i class="flaticon-instagram"></i>
                                            </li>
                                            <li class="network-youtube" data-network="youtube" data-network-name="YouTube">
                                                <i class="flaticon-youtube"></i>
                                            </li>
                                            <li class="network-pinterest" data-network="pinterest" data-network-name="Pinterest">
                                                <i class="flaticon-pinterest"></i>
                                            </li>
                                            <li class="network-linkedin" data-network="linkedin" data-network-name="LinkedIn">
                                                <i class="flaticon-linkedin"></i>
                                            </li>

                                            <li class="network-flickr" data-network="flickr" data-network-name="Flickr">
                                                <i class="flaticon-flickr"></i>
                                            </li>
                                            <li class="network-tumblr" data-network="tumblr" data-network-name="Tumblr" style="margin-right:0">
                                                <i class="flaticon-tumblr"></i>
                                            </li>
                                            <br>

                                            <li class="network-google" data-network="google" data-network-name="Google +">
                                                <i class="flaticon-google"></i>
                                            </li>
                                            <li class="network-vimeo" data-network="vimeo" data-network-name="Vimeo">
                                                <i class="flaticon-vimeo"></i>
                                            </li>
                                            <li class="network-wordpress" data-network="wordpress" data-network-name="WordPress">
                                                <i class="flaticon-wordpress"></i>
                                            </li>
                                            <li class="network-foursquare" data-network="foursquare" data-network-name="Foursquare">
                                                <i class="flaticon-foursquare"></i>
                                            </li>
                                            <li class="network-soundcloud" data-network="soundcloud" data-network-name="SoundCloud">
                                                <i class="flaticon-soundcloud"></i>
                                            </li>
                                            <li class="network-dribbble" data-network="dribbble" data-network-name="Dribbble">
                                                <i class="flaticon-dribbble"></i>
                                            </li>
                                            <li class="network-rss" data-network="rss" data-network-name="RSS" style="margin-right:0">
                                                <i class="flaticon-rss"></i>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="networks-content  add-feed-step">
                                        <div id="feed-views">
                                            <div class="feed-view" data-feed-type="youtube" data-uid="cv99734">
                                                <h1>YouTube feed settings</h1>
                                                <dl class="section-settings">
                                                    <dt>FEED TYPE</dt>
                                                    <dd> <input id="cv99734-user-timeline-type" type="radio" name="cv99734-timeline-type" value="user_timeline" checked=""> <label for="cv99734-user-timeline-type">User feed</label><br><br> <input id="cv99734-channel-type" type="radio" name="cv99734-timeline-type" value="channel"> <label for="cv99734-channel-type">Channel</label><br><br> <input id="cv99734-pl-type" type="radio" name="cv99734-timeline-type" value="playlist"> <label for="cv99734-pl-type">Playlist</label><br><br> <input id="cv99734-search-timeline-type" type="radio" name="cv99734-timeline-type" value="search" checked="checked"> <label for="cv99734-search-timeline-type">Search</label> </dd>
                                                    <dt class=""> Content to show <div class="desc hint-block"> <span class="hint-link"> <img src="http://localhost/wp_exam/wp-content/plugins/flow-flow/assets/info_icon.svg"> </span>
                                                            <div class="hint hint-pro">
                                                                <h1>Content to show</h1>
                                                                <ul>
                                                                    <li><b>User feed</b> — enter YouTube username with public access.</li>
                                                                    <li><b>Channel</b> — enter channel ID. <a href="https://support.google.com/youtube/answer/3250431?hl=en" target="_blank">What is it?</a></li>
                                                                    <li><b>Playlist</b> — enter playlist ID. <a href="http://docs.social-streams.com/article/139-find-youtube-playlist-id" target="_blank">What is it?</a></li>
                                                                    <li><b>Search</b> — enter any search query.</li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </dt>
                                                    <dd class=""><input type="text" name="cv99734-content"></dd>
                                                    <dt>Playlist reverse order</dt>
                                                    <dd> <label for="cv99734-playlist-order"> <input id="cv99734-playlist-order" class="switcher" type="checkbox" name="cv99734-playlist-order" value="yep">
                                                            <div>
                                                                <div></div>
                                                            </div>
                                                        </label> </dd>
                                                    <dt>Feed updates frequency</dt>
                                                    <dd>
                                                        <div class="select-wrapper"> <select name="cv99734-cache_lifetime" id="cv99734-cache_lifetime">
                                                                <option value="60">Every hour</option>
                                                                <option value="360">Every 6 hours</option>
                                                                <option value="1440">Once a day</option>
                                                                <option value="10080">Once a week</option>
                                                            </select> </div>
                                                    </dd>
                                                    <dt>Posts to load during update<p class="desc">The first load is always 50. <a href="http://docs.social-streams.com/article/137-managing-feed-updates" target="_blank">Learn more</a>.</p>
                                                    </dt>
                                                    <dd>
                                                        <div class="select-wrapper"> <select name="cv99734-posts" id="cv99734-post">
                                                                <option value="1">1 post</option>
                                                                <option value="5">5 posts</option>
                                                                <option selected="" value="10">10 posts</option>
                                                                <option value="20">20 posts</option>
                                                            </select></div>
                                                    </dd>
                                                    <dt> MODERATE THIS FEED <p class="desc"><a href="http://docs.social-streams.com/article/70-manual-premoderation" target="_blank">Learn more</a></p>
                                                    </dt>
                                                    <dd><label for="cv99734-mod"><input id="cv99734-mod" class="switcher" type="checkbox" name="cv99734-mod" value="yep">
                                                            <div>
                                                                <div></div>
                                                            </div>
                                                        </label></dd>
                                                </dl><input type="hidden" id="cv99734-enabled" value="yep" checked="" name="cv99734-enabled">
                                            </div>
                                        </div>
                                        <div id="filter-views">
                                            <div class="feed-view filter-feed" data-filter-uid="cv99734">
                                                <h1>Filter Feed Content</h1>
                                                <dl class="section-settings">
                                                    <dt class="">Exclude all</dt>
                                                    <dd class=""> <input type="hidden" data-type="filter-exclude-holder" name="cv99734-filter-by-words" value=""> <input type="text" data-action="add-filter" data-id="cv99734" data-type="exclude" placeholder="Type and hit Enter">
                                                        <ul class="filter-labels" data-type="exclude"></ul>
                                                    </dd>
                                                </dl>
                                                <dl class="section-settings">
                                                    <dt class="">Include all</dt>
                                                    <dd class=""> <input type="hidden" data-type="filter-include-holder" name="cv99734-include" value=""> <input type="text" data-action="add-filter" data-id="cv99734" data-type="include" placeholder="Type and hit Enter">
                                                        <ul class="filter-labels" data-type="include"></ul>
                                                    </dd>
                                                </dl>
                                                <div class="hint-block"> <a class="hint-link" href="#" data-action="hint-toggle">How to Filter</a>
                                                    <div class="hint">
                                                        <h1>Hints on Filtering</h1>
                                                        <div class="desc">
                                                            <p> 1. <strong>Filter by word</strong> — type any word<br> </p>
                                                            <p> 2. <strong>Filter by URL</strong> — enter any substring with hash like this #badpost or #1234512345<br> </p>
                                                            <p> 3. <strong>Filter by account</strong> — type word with @ symbol e.g. @apple<br> </p> <br>
                                                            <p> <a target="_blank" title="Learn more" href="http://docs.social-streams.com/article/71-automatic-moderation-with-filters">Learn more</a> </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="feed-popup-controls add">
                                            <span id="feed-sbmt-1" class="admin-button green-button submit-button">Add feed</span>
                                            <span class="space"></span><span class="admin-button grey-button button-go-back">Back to first step</span>
                                        </p>
                                        <p class="feed-popup-controls edit">
                                            <span id="feed-sbmt-2" class="admin-button green-button submit-button">Save changes</span>
                                        </p>
                                        <p class="feed-popup-controls enable">
                                            <span id="feed-sbmt-3" class="admin-button blue-button submit-button">Save &amp; Enable</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane container fade" id="auth">
                <div id="accordion_auth">
                    <div class="card">
                        <div class="card-header">
                            <a class="collapsed card-link btn btn-info" data-toggle="collapse" href="#collapse1_auth">
                                Google+ and YouTube auth settings
                            </a>
                        </div>
                        <div id="collapse1_auth" class="collapse show" data-parent="#accordion_auth">
                            <div class="card-body">
                                <table class="form-table">
                                    <tbody>
                                        <?php
                                        global $wpdb;
                                        $sql = 'select * from ' . $wpdb->prefix . 'mam_auth where user_id=' . get_current_user_id();
                                        $auth = $wpdb->get_row($sql);
                                        ?>

                                        <tr class="get_instagram_tr">
                                            <th>API Key</th>
                                            <td><input type="text" class="regular-text" value="<?php echo $auth->api_key ?>" id="mam_gy_key" name="campaign_name" required></td>
                                            <td><input type="button" class="btn btn-primary" id="btn_mam_save_gykey" value="save"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <a class="card-link btn btn-info" data-toggle="collapse" href="#collapse2_auth">
                                Twitter auth settings
                            </a>
                        </div>
                        <div id="collapse2_auth" class="collapse" data-parent="#accordion_auth">
                            <div class="card-body">
                                <table class="form-table">
                                    <tbody>
                                        <tr class="consumer_key_tr">
                                            <th>Consumer Key (Api Key)</th>
                                            <td><input type="text" class="regular-text" value="" id="campaign_name" name="campaign_name"></td>
                                        </tr>
                                        <tr class="consumer_secret_tr">
                                            <th>Consumer Secret (Api Secret)</th>
                                            <td><input type="text" class="regular-text" value="" id="campaign_name" name="campaign_name"></td>
                                        </tr>
                                        <tr class="access_token_tr">
                                            <th>Access Token</th>
                                            <td><input type="text" class="regular-text" value="" id="campaign_name" name="campaign_name"></td>
                                        </tr>
                                        <tr class="access_token_secret_tr">
                                            <th>Access Token Secret</th>
                                            <td><input type="text" class="regular-text" value="" id="campaign_name" name="campaign_name"></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <input type="button" id="btn_create_template" class="btn btn-success" value="SAVE CHANGES">
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <a class="collapsed card-link btn btn-info" data-toggle="collapse" href="#collapse3_auth">
                                Facebook auth settings
                            </a>
                        </div>
                        <div id="collapse3_auth" class="collapse" data-parent="#accordion_auth">
                            <div class="card-body">
                                <table class="form-table">
                                    <tbody>
                                        <tr class="use_own_app_tr">
                                            <th>Use Own App</th>
                                            <td><input type="text" class="regular-text" value="" id="campaign_name" name="campaign_name"></td>
                                        </tr>
                                        <tr class="access_tokens_tr">
                                            <th>Access Token</th>
                                            <td><input type="text" class="regular-text" value="" id="campaign_name" name="campaign_name"></td>
                                        </tr>
                                        <tr class="app_id">
                                            <th>App Id</th>
                                            <td><input type="text" class="regular-text" value="" id="campaign_name" name="campaign_name"></td>
                                        </tr>
                                        <tr class="app_secret_tr">
                                            <th>App Secret</th>
                                            <td><input type="text" class="regular-text" value="" id="campaign_name" name="campaign_name"></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <input type="button" id="btn_create_template" class="btn btn-success" value="SAVE CHANGES">
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <a class="collapsed card-link btn btn-info" data-toggle="collapse" href="#collapse4_auth">
                                Instagram auth settings
                            </a>
                        </div>
                        <div id="collapse4_auth" class="collapse" data-parent="#accordion_auth">
                            <div class="card-body">
                                <table class="form-table">
                                    <tbody>
                                        <tr class="get_instagram_tr">
                                            <th>Get related instagram Content</th>
                                            <td><input type="text" class="regular-text" value="" id="campaign_name" name="campaign_name"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <a class="collapsed card-link btn btn-info" data-toggle="collapse" href="#collapse5_auth">
                                Using RSS feed that support keyword
                            </a>
                        </div>
                        <div id="collapse5_auth" class="collapse" data-parent="#accordion_auth">
                            <div class="card-body">
                                <table class="form-table">
                                    <tbody>
                                        <tr class="example_tr">
                                            <th>Example</th>
                                            <td><input type="text" class="regular-text" value="" id="campaign_name" name="campaign_name" placeholder="https://news.google.com/search?for={keyword}"></td>
                                        </tr>
                                        <tr class="bing_tr">
                                            <th>Bing</th>
                                            <td><input type="text" class="regular-text" value="" id="campaign_name" name="campaign_name" placeholder="[BING API might be needed}"></td>
                                        </tr>
                                        <tr class="url_tr">
                                            <th>Url</th>
                                            <td><input type="text" class="regular-text" value="" id="campaign_name" name="campaign_name" placeholder="https://www.bing.com/search?q=keyword&qs=n&form=QBLH&sp=-1&pq=pcos&sc=8-4&sk=&cvid=C7EBBF5698F0401B8E207915E66447D5&format=rss"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <a class="collapsed card-link btn btn-info" data-toggle="collapse" href="#collapse6_auth">
                                Related Keywords
                            </a>
                        </div>
                        <div id="collapse6_auth" class="collapse" data-parent="#accordion_auth">
                            <div class="card-body">
                                <table class="form-table">
                                    <tbody>
                                        <tr class="bing_azure_api_tr">
                                            <th> Bing Azure api</th>
                                            <td><input type="text" class="regular-text" value="" id="campaign_name" name="campaign_name"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <a class="collapsed card-link btn btn-info" data-toggle="collapse" href="#collapse7_auth">
                                Pinterest
                            </a>
                        </div>
                        <div id="collapse7_auth" class="collapse" data-parent="#accordion_auth">
                            <div class="card-body">
                                <table class="form-table">
                                    <tbody>
                                        <tr class="get_pinterest_tr">
                                            <th>Get related pinterest Content</th>
                                            <td><input type="text" class="regular-text" value="" id="campaign_name" name="campaign_name"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <a class="collapsed card-link btn btn-info" data-toggle="collapse" href="#collapse8_auth">
                                Reddit
                            </a>
                        </div>
                        <div id="collapse8_auth" class="collapse" data-parent="#accordion_auth">
                            <div class="card-body">
                                <table class="form-table">
                                    <tbody>
                                        <tr class="get_reddit_tr">
                                            <th>Get related reddit Content</th>
                                            <td><input type="text" class="regular-text" value="" id="campaign_name" name="campaign_name"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane container fade" id="settings">
                <div class="section" id="general-settings">
                    <h1 class="desc-following">General Settings</h1>
                    <p class="desc">Adjust plugin's global settings here.</p>
                    <dl class="section-settings">
                        <dt class="ff_mod_roles ff_hide4site">Who can moderate
                            <p class="desc">User roles that are allowed to moderate feeds.</p>
                        </dt>
                        <dd class="ff_mod_roles ff_hide4site">
                            <div class="checkbox-row"><input type="checkbox" checked="checked" value="yep" name="flow_flow_options[mod-role-administrator]" id="mod-role-administrator"><label for="mod-role-administrator">Administrator</label></div>
                            <div class="checkbox-row"><input type="checkbox" value="yep" name="flow_flow_options[mod-role-editor]" id="mod-role-editor"><label for="mod-role-editor">Editor</label></div>
                            <div class="checkbox-row"><input type="checkbox" value="yep" name="flow_flow_options[mod-role-author]" id="mod-role-author"><label for="mod-role-author">Author</label></div>
                            <div class="checkbox-row"><input type="checkbox" value="yep" name="flow_flow_options[mod-role-contributor]" id="mod-role-contributor"><label for="mod-role-contributor">Contributor</label></div>
                            <div class="checkbox-row"><input type="checkbox" value="yep" name="flow_flow_options[mod-role-subscriber]" id="mod-role-subscriber"><label for="mod-role-subscriber">Subscriber</label></div>
                        </dd>
                        <dt class="multiline">Date format<p class="desc">Used in post timestamps.</p>
                        </dt>
                        <dd>
                            <input id="general-settings-ago-format" class="clearcache" type="radio" name="flow_flow_options[general-settings-date-format]" checked="" value="agoStyleDate">
                            <label for="general-settings-ago-format">Short</label>
                            <input id="general-settings-classic-format" class="clearcache" type="radio" name="flow_flow_options[general-settings-date-format]" value="classicStyleDate">
                            <label for="general-settings-classic-format">Classic</label>
                            <input id="general-settings-wp-format" class="clearcache" type="radio" name="flow_flow_options[general-settings-date-format]" value="wpStyleDate">
                            <label for="general-settings-wp-format">WordPress</label>
                        </dd>
                        <dt class="multiline">Open links in new tab<p class="desc">Any link in post will be opened in new tab.</p>
                        </dt>
                        <dd>
                            <label for="general-settings-open-links-in-new-window">
                                <input id="general-settings-open-links-in-new-window" class="switcher clearcache" type="checkbox" name="flow_flow_options[general-settings-open-links-in-new-window]" checked="" value="yep">
                                <div>
                                    <div></div>
                                </div>
                            </label>
                        </dd>
                        <dt class="multiline">Disable proxy pictures<p class="desc">Proxying improves performance.</p>
                        </dt>
                        <dd>
                            <label for="general-settings-disable-proxy-server">
                                <input id="general-settings-disable-proxy-server" class="clearcache switcher" type="checkbox" name="flow_flow_options[general-settings-disable-proxy-server]" value="yep">
                                <div>
                                    <div></div>
                                </div>
                            </label></dd>
                        <dt class="multiline">Disable curl "follow location"
                            <p class="desc">Can help if your server uses deprecated security setting 'safe_mode' and streams don't load.</p>
                        </dt>
                        <dd>
                            <label for="general-settings-disable-follow-location">
                                <input id="general-settings-disable-follow-location" class="clearcache switcher" type="checkbox" name="flow_flow_options[general-settings-disable-follow-location]" value="yep">
                                <div>
                                    <div></div>
                                </div>
                            </label></dd>
                        <dt class="multiline">Use IPv4 protocol
                            <p class="desc">Sometimes servers use older version of Internet protocol. Use setting when you see "Network is unreachable" error.</p>
                        </dt>
                        <dd>
                            <label for="general-settings-ipv4">
                                <input id="general-settings-ipv4" class="clearcache switcher" type="checkbox" name="flow_flow_options[general-settings-ipv4]" value="yep">
                                <div>
                                    <div></div>
                                </div>
                            </label></dd>

                        <dt class="multiline">Force HTTPS for all resources
                            <p class="desc">Load images and videos via HTTPS. Use this setting if you notice browser security warnings. Be advised, not every API provides resources via HTTPS.</p>
                        </dt>
                        <dd>
                            <label for="general-settings-https">
                                <input id="general-settings-https" class="clearcache switcher" type="checkbox" name="flow_flow_options[general-settings-https]" value="yep">
                                <div>
                                    <div></div>
                                </div>
                            </label></dd>

                        <dt class="multiline">Аmount of stored posts for each feed
                            <p class="desc"></p>
                        </dt>
                        <dd>
                            <label for="general-settings-feed-post-count">
                                <input id="general-settings-feed-post-count" class="clearcache short" type="text" name="flow_flow_options[general-settings-feed-post-count]" value="100">
                                <div>
                                    <div></div>
                                </div>
                            </label></dd>

                        <dt class="multiline">Send notifications about broken feeds
                            <p class="desc">You will get notifications once per day to your blog admin email.</p>
                        </dt>
                        <dd>
                            <label for="general-notifications">
                                <input id="general-notifications" class="clearcache switcher" type="checkbox" name="flow_flow_options[general-notifications]" value="yep">
                                <div>
                                    <div></div>
                                </div>
                            </label></dd>

                        <dt class="multiline">Remove all data when uninstall plugin
                            <p class="desc">Check this if you want to erase all database records that plugin created.</p>
                        </dt>
                        <dd>
                            <label for="general-uninstall">
                                <input id="general-uninstall" class="clearcache switcher" type="checkbox" name="flow_flow_options[general-uninstall]" value="yep">
                                <div>
                                    <div></div>
                                </div>
                            </label></dd>
                    </dl>
                    <span id="general-settings-sbmt" class="admin-button btn btn-success submit-button">Save Changes</span>
                </div>
            </div>
            <div class="tab-pane container fade" id="logs">
                <table class="form-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Log</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- The Modal -->
        <div class="modal" id="feedsModal">
            <div class="modal-dialog">
                <div class="modal-content">

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">Networks List</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>

                    <!-- Modal body -->
                    <div class="modal-body">
                        <ul class="networks-list">
                            <li class="network-twitter" data-network="twitter" data-network-name="Twitter">
                                <i class="flaticon-twitter"></i>
                            </li>
                            <li class="network-facebook" data-network="facebook" data-network-name="Facebook">
                                <i class="flaticon-facebook"></i>
                            </li>
                            <li class="network-instagram" data-network="instagram" data-network-name="Instagram">
                                <i class="flaticon-instagram"></i>
                            </li>
                            <li class="network-youtube" data-network="youtube" data-network-name="YouTube">
                                <i class="flaticon-youtube"></i>
                            </li>
                            <li class="network-pinterest" data-network="pinterest" data-network-name="Pinterest">
                                <i class="flaticon-pinterest"></i>
                            </li>
                            <li class="network-google" data-network="google" data-network-name="Google +">
                                <i class="flaticon-google"></i>
                            </li>
                            <li class="network-rss" data-network="rss" data-network-name="RSS">
                                <i class="flaticon-rss"></i>
                            </li>
                            <li class="network-reddit" data-network="reddit" data-network-name="Reddit" style="margin-right:0">
                                <i class="flaticon-star-o"></i>
                            </li>
                        </ul>
                    </div>

                    <!-- Modal footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                    </div>

                </div>
            </div>
        </div>
    </div>
<?php
}
