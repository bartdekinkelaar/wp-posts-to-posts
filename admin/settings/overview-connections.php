<?php

if (!defined('ABSPATH')) {
    exit;
}
class P2P_Connections_Page_Overview {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public function add_admin_menu() {
        add_menu_page(
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
        $connection_types = $wpdb->get_col("SELECT DISTINCT connection_name FROM {$wpdb->prefix}p2p_connection_types");

        ?>
        <div class="wrap">
            <h1>Posts 2 Posts Connections</h1>

            <?php foreach ($connection_types as $type): ?>
                <h2>Connection Type: <?php echo esc_html($type); ?></h2>
                <?php
                // Get connections for this type
                $connections = p2p_get_connections($type, [
                    'direction' => 'any',
                    'fields' => 'all'
                ]);

                if (empty($connections)) {
                    echo '<p>No connections found for this type.</p>';
                    continue;
                }

                // Remove duplicates based on p2p_id
                $unique_connections = [];
                foreach ($connections as $connection) {
                    $unique_connections[$connection->p2p_id] = $connection;
                }
                $connections = array_values($unique_connections);
                $post_type_from = $connections[0]->post_type_from;
                $post_type_to = $connections[0]->post_type_to;
                
                // var_dump($unique_connections);
                echo "<p><i>From: $post_type_from <br/> To: $post_type_to</i></p>";

                ?>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Connection ID</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($connections as $connection) {
                            $from_post = $connection->post_type_from;
                            $to_post = $connection->post_type_to;
                            ?>
                            <tr>
                                <td><?php echo esc_html($connection->p2p_id); ?></td>
                                <td>
                                    <?php echo $from_post; ?>
                                </td>
                                <td>
                                    <?php echo $to_post; ?>
                                </td>
                                <td>
                                    <button class="button delete-connection" 
                                            data-connection-id="<?php echo esc_attr($connection->p2p_id); ?>"
                                            onclick="if(confirm('Are you sure you want to delete this connection?')) { deleteConnection(<?php echo esc_js($connection->p2p_id); ?>); }">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php }; ?>
                    </tbody>
                </table>
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
                    alert('Error deleting connection');
                }
            });
        }
        </script>
        <?php
    }
}

new P2P_Connections_Page_Overview();

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