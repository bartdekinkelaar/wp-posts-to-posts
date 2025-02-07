<?php
class P2P_Connections_Overview {
    private array $args;

    public static function setup(): void {
        if (function_exists('acf_add_options_page')) {
            acf_add_options_page([
                'page_title' => __('Connection Types', P2P_TEXTDOMAIN),
                'menu_title' => __('Connection Types', P2P_TEXTDOMAIN),
                'menu_slug' => 'connection-types',
                'capability' => 'manage_options',
                'position' => 20,
                'icon_url' => 'dashicons-admin-generic',
                'redirect' => false,
                'post_id' => 'connection_types',
                'update_button' => __('Update Connection Types', P2P_TEXTDOMAIN),
                'updated_message' => __('Connection Types Updated', P2P_TEXTDOMAIN),
            ]);

            $instance = new self();
            add_action('acf/init', [$instance, 'register_acf_fields']);
        }
    }

    public function register_acf_fields(): void {
        acf_add_local_field_group([
            'key' => 'group_connection_types',
            'title' => 'Connection Types',
            'fields' => [
                [
                    'key' => 'field_connection_types',
                    'label' => 'Connection Types',
                    'name' => 'connection_types',
                    'type' => 'repeater',
                    'layout' => 'table',
                    'sub_fields' => [
                        [
                            'key' => 'field_name',
                            'label' => __('Name', P2P_TEXTDOMAIN),
                            'name' => 'name',
                            'type' => 'text',
                            'required' => 1,
                        ],
                        [
                            'key' => 'field_information',
                            'label' => __('Information', P2P_TEXTDOMAIN),
                            'name' => 'information',
                            'type' => 'textarea',
                        ],
                        [
                            'key' => 'field_connections',
                            'label' => __('Connections', P2P_TEXTDOMAIN),
                            'name' => 'connections',
                            'type' => 'number',
                            'readonly' => 1,
                            'default_value' => 0,
                        ],
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'connection-types',
                    ],
                ],
            ],
        ]);
    }

    private function get_connection_counts(): array {
        $counts = get_posts([
            'post_type' => 'p2p',
            'fields' => 'ids',
            'group_by' => 'p2p_type',
            'select' => 'p2p_type, COUNT(*) as count'
        ]);

        $counts = array_reduce($counts, function($acc, $item) {
            $acc[$item->p2p_type] = (int)$item->count;
            return $acc;
        }, []);

        $all_types = P2P_Connection_Type_Factory::get_all_instances();
        foreach ($all_types as $p2p_type => $ctype) {
            $counts[$p2p_type] = $counts[$p2p_type] ?? 0;
        }

        ksort($counts);

        return $counts;
    }

    private function get_connection_description(string $p2p_type): string {
        $ctype = p2p_type($p2p_type);
        return $ctype ? $ctype->get_desc() : '';
    }

    public function update_connection_counts(): void {
        if (!function_exists('get_field')) {
            return;
        }

        $counts = $this->get_connection_counts();
        $connection_types = get_field('connection_types', 'connection_types') ?: [];

        foreach ($connection_types as $index => $type) {
            $p2p_type = $type['name'];
            if (isset($counts[$p2p_type])) {
                update_sub_field(['connection_types', $index, 'connections'], $counts[$p2p_type], 'connection_types');
            }
        }
    }
}