<?php

if (!defined('ABSPATH')) {
    exit;
}
// Deprecated duplicate admin page. Kept as a thin wrapper to avoid fatal errors if referenced.
class P2P_Connections_Page_Overview {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public function add_admin_menu() {}

    public function render_page() {}
}

new P2P_Connections_Page_Overview();