<?php

if (!defined('ABSPATH')) {
    exit;
}

class P2P_Connections_Page_Add {
    public function __construct() {
        add_action('acf/init', [$this, 'add_acf_options_page']);
        add_action('acf/init', [$this, 'register_acf_fields']);
    }

    public function add_acf_options_page() {
        if (function_exists('acf_add_options_page')) {
            acf_add_options_page([
                'page_title' => 'Posts 2 Posts Connections',
                'menu_title' => 'Add P2P Connection',
                'menu_slug' => 'p2p-add-connections',
                'capability' => 'manage_options',
                'parent_slug' => 'p2p-connections',
            ]);
        }
    }

    public function register_acf_fields() {
        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group([
                'key' => 'group_p2p_connections',
                'title' => 'P2P Post Connection Velden',
                'fields' => [
                    [
                        'key' => 'field_post_type_from',
                        'label' => 'Post type van',
                        'name' => 'post_type_from',
                        'type' => 'text',
                        'default_value' => 'post',
                    ],
                    [
                        'key' => 'field_post_type_to',
                        'label' => 'Post type naar',
                        'name' => 'post_type_to',
                        'type' => 'text',
                        'default_value' => 'post',
                    ]
                ],
                'location' => [
                    [
                        [
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => 'p2p-add-connections',
                        ],
                    ],
                ],
            ]);
        }
    }
}

new P2P_Connections_Page_Add();