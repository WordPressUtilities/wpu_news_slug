<?php
defined('ABSPATH') || die;
/*
Plugin Name: WPU News Slug
Plugin URI: https://github.com/WordPressUtilities/wpu_news_slug
Update URI: https://github.com/WordPressUtilities/wpu_news_slug
Description: Add a slug to the post type "post" in WordPress
Version: 0.1.0
Author: darklg
Author URI: https://darklg.me/
Text Domain: wpu_news_slug
Domain Path: /lang
Requires at least: 6.7
Requires PHP: 8.0
Network: Optional
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

define('WPU_NEWS_SLUG_VERSION', '0.1.0');

/* ----------------------------------------------------------
  Handle rewrite if Polylang is active
---------------------------------------------------------- */

add_action('init', function () {

    if (!wpu_news_slug__has_polylang()) {
        return;
    }

    pll_register_string(
        'news_slug',
        wpu_news_slug__get_slug(),
        'urls',
        false
    );

    $languages = pll_languages_list();
    $default_language = pll_default_language();
    $slug_infos = array();

    foreach ($languages as $lang) {
        if ($lang === $default_language) {
            continue;
        }
        $slug = pll_translate_string(wpu_news_slug__get_slug(), $lang);
        $slug_infos[$lang] = $slug;
        add_rewrite_rule(
            '^' . $lang . '/' . $slug . '/([^/]+)/?$',
            'index.php?name=$matches[1]&lang=' . $lang,
            'top'
        );
    }

    $default_slug = pll_translate_string(wpu_news_slug__get_slug(), $default_language);
    $slug_infos[$default_language] = $default_slug;

    add_rewrite_rule(
        '^' . $default_slug . '/([^/]+)/?$',
        'index.php?name=$matches[1]',
        'top'
    );

    wpu_news_slug__check_cached_slug($slug_infos);
});

add_action('init', function () {

    if (wpu_news_slug__has_polylang()) {
        return;
    }

    add_rewrite_rule(
        '^' . wpu_news_slug__get_slug() . '/([^/]+)/?$',
        'index.php?name=$matches[1]',
        'top'
    );
    wpu_news_slug__check_cached_slug(array(wpu_news_slug__get_slug()));
});

/* ----------------------------------------------------------
  Handle rewrite flush
---------------------------------------------------------- */

register_activation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

function wpu_news_slug__check_cached_slug($slug_infos) {
    if (!is_array($slug_infos) || empty($slug_infos)) {
        return;
    }
    $slug_infos_hash = md5(json_encode($slug_infos) . WPU_NEWS_SLUG_VERSION);
    $cached_slug_infos_hash = get_option('wpu_news_slug__slug_infos_hash');
    if ($slug_infos_hash !== $cached_slug_infos_hash) {
        update_option('wpu_news_slug__slug_infos_hash', $slug_infos_hash);
        flush_rewrite_rules();
    }
}

/* ----------------------------------------------------------
  Filter the permalink of the post type "post" to include the slug
---------------------------------------------------------- */

add_filter('post_link', function ($permalink, $post) {

    if ($post->post_type !== 'post') {
        return $permalink;
    }

    $slug = wpu_news_slug__get_slug();
    $base_slug = "/{$slug}/{$post->post_name}/";
    if (wpu_news_slug__has_polylang()) {
        $lang = pll_get_post_language($post->ID);
        if ($lang) {
            $slug = pll_translate_string(wpu_news_slug__get_slug(), $lang);
            if ($lang === pll_default_language()) {
                $base_slug = "/{$slug}/{$post->post_name}/";
            } else {
                $base_slug = "/{$lang}/{$slug}/{$post->post_name}/";
            }
        }
    }

    return home_url($base_slug);
}, 10, 2);

/* ----------------------------------------------------------
  Helpers
---------------------------------------------------------- */

function wpu_news_slug__get_slug() {
    return apply_filters('wpu_news_slug__get_slug', 'wpu_news_slug');
}

function wpu_news_slug__has_polylang() {
    return function_exists('pll_register_string') || function_exists('pll_languages_list') || function_exists('pll_translate_string');
}
