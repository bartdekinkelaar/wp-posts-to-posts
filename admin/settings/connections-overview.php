<?php

if (!defined('ABSPATH')) {
    exit;
}

// Only declare the class if it hasn't been declared yet
if (!class_exists('P2P_Connections_Page')) {
    class P2P_Connections_Page {
        public function __construct() {
            add_action('admin_menu', [$this, 'add_admin_menu']);
        }

        public function add_admin_menu() {
            add_submenu_page(
                'options-general.php',
                'Posts 2 Posts Connections',
                'P2P Connections',
                'manage_options',
                'p2p-connections',
                [$this, 'render_page']
            );
        }

        public function render_page() {
            global $wpdb;
            
            // Get all connection types
            $connection_types = $wpdb->get_col("SELECT DISTINCT p2p_type FROM {$wpdb->p2p}");

            ?>
            <div class="wrap">
            <h1><?php echo esc_html__( 'Posts 2 Posts Connections', P2P_TEXTDOMAIN ); ?></h1>

                <?php foreach ($connection_types as $type): ?>
                    <h2>Connection Type: <?php echo esc_html($type); ?></h2>
                    <?php
                    // Get connections for this type
                    // Paginate and fetch post titles in one go to avoid N+1
                    $paged = max(1, (int) ($_GET['paged'] ?? 1));
                    $per_page = 50;
                    $offset = ($paged - 1) * $per_page;

                    $total = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->p2p} WHERE p2p_type = %s",
                        $type
                    ));

                    $connections = $wpdb->get_results($wpdb->prepare(
                        "SELECT p.p2p_id, p.p2p_from, p.p2p_to,
                                pf.post_title AS from_title, pt.post_title AS to_title
                           FROM {$wpdb->p2p} p
                           LEFT JOIN {$wpdb->posts} pf ON pf.ID = p.p2p_from
                           LEFT JOIN {$wpdb->posts} pt ON pt.ID = p.p2p_to
                          WHERE p.p2p_type = %s
                          ORDER BY p.p2p_id DESC
                          LIMIT %d OFFSET %d",
                        $type, $per_page, $offset
                    ));

                    if (empty($connections)) {
                        echo '<p>No connections found for this type.</p>';
                        continue;
                    }

                    $post_type_from = $connections ? get_post_type((int) $connections[0]->p2p_from) : '';
                    $post_type_to = $connections ? get_post_type((int) $connections[0]->p2p_to) : '';
                    
                    echo "<p><i>From: $post_type_from <br/> To: $post_type_to</i></p>";

                    ?>

                    <table class="widefat striped">
                        <thead>
                            <tr>
                            <th><?php echo esc_html__( 'Connection ID', P2P_TEXTDOMAIN ); ?></th>
                            <th><?php echo esc_html__( 'From', P2P_TEXTDOMAIN ); ?></th>
                            <th><?php echo esc_html__( 'To', P2P_TEXTDOMAIN ); ?></th>
                            <th><?php echo esc_html__( 'Actions', P2P_TEXTDOMAIN ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($connections as $connection) {
                                $from_post = get_post($connection->p2p_from);
                                $from_post_type = $from_post->post_type;
                                $to_post = get_post($connection->p2p_to);
                                $to_post_type = $to_post->post_type;
                                ?>
                                <tr>
                                    <td><?php echo esc_html($connection->p2p_id); ?></td>
                                    <td>
                                <?php
                                $from_link = $connections ? get_edit_post_link((int) $connection->p2p_from) : '';
                                echo $from_link
                                    ? sprintf('<a href="%s">%s</a>', $from_link, esc_html($connection->from_title ?? ''))
                                    : esc_html__('Post not found', P2P_TEXTDOMAIN);
                                ?>
                                    </td>
                                    <td>
                                <?php
                                $to_link = $connections ? get_edit_post_link((int) $connection->p2p_to) : '';
                                echo $to_link
                                    ? sprintf('<a href="%s">%s</a>', $to_link, esc_html($connection->to_title ?? ''))
                                    : esc_html__('Post not found', P2P_TEXTDOMAIN);
                                ?>
                                    </td>
                                    <td>
                                        <button class="button delete-connection" 
                                                data-connection-id="<?php echo esc_attr($connection->p2p_id); ?>"
                                                onclick="if(confirm('<?php echo esc_js( __( 'Are you sure you want to delete this connection?', P2P_TEXTDOMAIN ) ); ?>')) { deleteConnection(<?php echo esc_js($connection->p2p_id); ?>); }">
                                            <?php echo esc_html__( 'Delete', P2P_TEXTDOMAIN ); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php }; ?>
                        </tbody>
                    </table>

                    <?php
                    // Simple pagination
                    $total_pages = max(1, (int) ceil($total / $per_page));
                    if ($total_pages > 1) {
                        echo '<div class="tablenav"><div class="tablenav-pages">';
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $url = add_query_arg(array('paged' => $i));
                            printf(
                                '<a class="%s" href="%s">%d</a> ',
                                $i === $paged ? 'button button-primary' : 'button',
                                esc_url($url),
                                $i
                            );
                        }
                        echo '</div></div>';
                    }
                    ?>
                <?php endforeach; ?>
            </div>

            <script>
            function deleteConnection(connectionId) {
                jQuery.post(ajaxurl, {
                    action: 'delete_p2p_connection',
                    connection_id: connectionId,
                    nonce: '<?php echo wp_create_nonce('delete_p2p_connection'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('<?php echo esc_js( __( 'Error deleting connection', P2P_TEXTDOMAIN ) ); ?>');
                    }
                });
            }
            </script>
            <?php
        }
    }

    new P2P_Connections_Page();
}

// Add AJAX handler for connection deletion
add_action('wp_ajax_delete_p2p_connection', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    if (!check_ajax_referer('delete_p2p_connection', 'nonce', false)) {
        wp_send_json_error('Invalid nonce');
    }

    $connection_id = intval($_POST['connection_id']);
    $result = p2p_delete_connection($connection_id);

    if ($result) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to delete connection');
    }
});