<?php
/*
Plugin Name: WPU News Slug
Plugin URI: https://github.com/WordPressUtilities/wpu_news_slug
Update URI: https://github.com/WordPressUtilities/wpu_news_slug
Description: Add a slug to the post type "post" in WordPress
Version: 0.0.1
Author: darklg
Author URI: https://darklg.me/
Text Domain: wpu_news_slug
Domain Path: /lang
Requires at least: 6.7
Requires PHP: 8.0
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

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
        'urls'
    );

    $languages = pll_languages_list();

    foreach ($languages as $lang) {
        $slug = pll_translate_string(wpu_news_slug__get_slug(), $lang);
        add_rewrite_rule(
            '^' . $lang . '/' . $slug . '/([^/]+)/?$',
            'index.php?name=$matches[1]&lang=' . $lang,
            'top'
        );
    }

    $default_slug = pll_translate_string(wpu_news_slug__get_slug(), pll_default_language());

    add_rewrite_rule(
        '^' . $default_slug . '/([^/]+)/?$',
        'index.php?name=$matches[1]',
        'top'
    );
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
});

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
            $base_slug = "/{$lang}/{$slug}/{$post->post_name}/";
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
