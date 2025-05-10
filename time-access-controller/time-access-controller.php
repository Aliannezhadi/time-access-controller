<?php
/*
Plugin Name: Time Access Controller
Plugin URI: https://example.com/
Description: محدودسازی دسترسی کاربران بر اساس زمان و نقش کاربری.
Version: 1.2
Author: meyiysam
Text Domain: time-access-controller
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'TAC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TAC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

add_action( 'admin_menu', 'tac_admin_menu' );
function tac_admin_menu() {
    add_menu_page( __('Time Access', 'time-access-controller'), __('Time Access', 'time-access-controller'), 'manage_options', 'tac-settings', 'tac_settings_page', 'dashicons-clock', 80 );
}

add_action( 'admin_enqueue_scripts', 'tac_admin_scripts' );
function tac_admin_scripts($hook) {
    if ($hook != 'toplevel_page_tac-settings') return;
    wp_enqueue_script('jquery');
    wp_enqueue_script('mdtimepicker', 'https://cdn.jsdelivr.net/npm/mdtimepicker/mdtimepicker.min.js', ['jquery'], null, true);
    wp_enqueue_style('mdtimepicker-style', 'https://cdn.jsdelivr.net/npm/mdtimepicker/mdtimepicker.min.css');
    wp_enqueue_script('tac-admin', TAC_PLUGIN_URL . 'assets/admin.js', ['mdtimepicker'], null, true);
}

add_action( 'admin_init', 'tac_register_settings' );
function tac_register_settings() {
    register_setting( 'tac_settings_group', 'tac_access_rules' );
}

function tac_settings_page() {
    $roles = wp_roles()->roles;
    $rules = get_option( 'tac_access_rules', [] );
    ?>
    <div class="wrap">
        <h1><?php _e('Time Access Controller', 'time-access-controller'); ?></h1>
        <p style="color:#555;"><em>Note: All access times are based on the WordPress timezone setting (Settings → General).</em></p>
        <form method="post" action="options.php">
            <?php settings_fields( 'tac_settings_group' ); ?>
            <table class="form-table">
                <?php foreach ( $roles as $role_key => $role ): 
                    $start = $rules[$role_key]['start'] ?? '';
                    $end = $rules[$role_key]['end'] ?? '';
                ?>
                    <tr>
                        <th scope="row"><?php echo esc_html( $role['name'] ); ?></th>
                        <td>
                            <input type="text" class="tac-timepicker" name="tac_access_rules[<?php echo $role_key; ?>][start]" value="<?php echo esc_attr($start); ?>" placeholder="از ساعت">
                            <input type="text" class="tac-timepicker" name="tac_access_rules[<?php echo $role_key; ?>][end]" value="<?php echo esc_attr($end); ?>" placeholder="تا ساعت">
                            <p class="description"><?php _e('Enter allowed access hours (24-hour format)', 'time-access-controller'); ?></p>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_action( 'init', 'tac_block_access_outside_hours' );
function tac_block_access_outside_hours() {
    if ( is_admin() || current_user_can('manage_options') ) return;
    if ( ! is_user_logged_in() ) return;

    $user = wp_get_current_user();
    $rules = get_option( 'tac_access_rules', [] );
    $role = $user->roles[0] ?? null;

    if ( ! isset( $rules[$role] ) || !isset($rules[$role]['start']) || !isset($rules[$role]['end']) ) return;

    $now = current_time('H:i');
    $start = $rules[$role]['start'];
    $end = $rules[$role]['end'];

    if ( $start <= $now && $now <= $end ) {
        return;
    }

    wp_die( __('Access is not allowed at this time. Please return during permitted hours.', 'time-access-controller') );
}
