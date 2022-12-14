<?php

add_filter('the_content', 'wp_podcast_ku_content_filter', 10, 1);

function wp_podcast_ku_content_filter($content)
{
    global $post_type;
    if ($post_type === 'podcast') {
        $podcast = wp_podcast_ku_player();
        return $podcast . $content;
    }
    return $content;
}


function wp_podcast_ku_player($post_id = null)
{
    if (is_null($post_id)) {
        global $post;
        $post_id = $post->ID;
    }
    $data = get_post_meta($post_id, 'wp_podcast_ku', true);
    $data = $data ?? array();

    $mimeType = array(
        "mp3" => "audio/mpeg",
        "m3a" => "audio/mpeg",
        "mp2a" => "audio/mpeg",
        "m4a" => "audio/mp4",
        "mp4" => "audio/mp4",
    );



    if (empty($data)) {
        return '';
    }

    $podcast_url = isset($data['podcast_url']) ? esc_url($data['podcast_url']) : null;

    $podcast_access = isset($data['podcast_access']) ? esc_attr($data['podcast_access']) : null;

    if (is_null($podcast_url)) {
        return '';
    }

    $fileExtension = pathinfo($podcast_url, PATHINFO_EXTENSION);

    $audioType = isset($mimeType[$fileExtension]) ? esc_attr($mimeType[$fileExtension]) : null;

    if (is_null($audioType)) {
        return '';
    }

    $cover = get_the_post_thumbnail_url($post_id);
    $terms = wp_get_post_terms($post_id, 'podcast-categories');

    $terms_output = array();
    foreach ($terms as $term) {
        $term_url = get_term_link($term, 'podcast-categories');
        $terms_output[] = wp_sprintf('<a href="%s" target="_blank">%s</a>', esc_url($term_url), esc_attr($term->name));
    }



    $output = '<div id="wp-podcast-player-wrapper">';
    if ($cover) {
        $output .= wp_sprintf('<div class="wp-podcast-player-cover"><img src="%s" lat="%s"/></div>', esc_url($cover), get_the_title($post_id));
    }
    $output .= '<div class="wp-podcast-player-content">';
    $output .= wp_sprintf('<div class="wp-podcast-terms">%s</div>', implode(', ', $terms_output));
    $output .= wp_sprintf('<h2 class="wp-podcast-player-hader">%s</h2>', get_the_title($post_id));

    //cek login jika podcast di setting hanya untuk member
    if ($podcast_access === 'member' && !is_user_logged_in()) {
        $output .= wp_podcast_ku_fake_player($post_id);
    } else {
        $output .= '<div class="wp-podcast-player-container">';
        $output .= wp_sprintf('<audio controls class="wp-podcast-player"><source src="%s" type="%s"></audio>', esc_url($podcast_url), $audioType);
        $output .= '</div>';
    }


    $output .= '</div>';
    $output .= '</div>';
    return wp_kses_post($output);
}



function wp_podcast_ku_fake_player($post_id)
{
    $redirect_url = get_permalink($post_id);

    ob_start();
    ?>
    <div class="wp-podcast-fake-player">
        <div class="mejs-button"><button></button></div>
        <div class="wp-podcast-fake-rails"><a href="<?php echo esc_url(wp_login_url($redirect_url)); ?>" class="player-link-login">Login untuk mendengar</a>
            <span class="podcast-fake-progress"></span>
        </div>
        <span class="fake-duration">10:00</span>
        <span class="fake-duration">00:00</span>
    </div>
<?php
        $output = ob_get_clean();
    return wp_kses_post($output);
}



add_shortcode('wp-podcast', 'wp_podcast_register_shortcode');

function wp_podcast_register_shortcode($atts)
{
    $atts = array_change_key_case((array) $atts, CASE_LOWER);
    $attributes = shortcode_atts(
        array(
            'id' => null,
        ),
        $atts
    );

    if ($attributes['id'] === null) {
        return '';
    }

    return wp_podcast_ku_player(intval($attributes['id']));
}
