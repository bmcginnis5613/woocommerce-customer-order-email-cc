<?php
/**
 * Plugin Name: WooCommerce - Customer Order Email CC
 * Description: Automatically copy additional email addresses listed in a users profile on all WooCommerce order related emails.
 * Version: 1.0.0
 * Author: FirstTracks Marketing
 * Author URI: https://firsttracksmarketing.com
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WC_Email_CC {
    
    /**
     * Meta key for storing additional email addresses
     */
    const META_KEY = 'wc_additional_email_addresses';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add custom field to user profile
        add_action('show_user_profile', array($this, 'add_custom_user_profile_fields'));
        add_action('edit_user_profile', array($this, 'add_custom_user_profile_fields'));
        
        // Save custom field
        add_action('personal_options_update', array($this, 'save_custom_user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_custom_user_profile_fields'));
        
        // Add CC recipients to WooCommerce emails
        add_filter('woocommerce_email_headers', array($this, 'add_cc_to_email_headers'), 10, 3);
    }
    
    /**
     * Add custom field to user profile
     */
    public function add_custom_user_profile_fields($user) {
        ?>
        <h3><?php _e('WooCommerce - Customer Order Email CC', 'woocommerce-email-cc'); ?></h3>
        <table class="form-table">
            <tr>
                <th>
                    <label for="<?php echo self::META_KEY; ?>">
                        <?php _e('Additional Email Addresses', 'woocommerce-email-cc'); ?>
                    </label>
                </th>
                <td>
                    <input 
                        type="text" 
                        name="<?php echo self::META_KEY; ?>" 
                        id="<?php echo self::META_KEY; ?>" 
                        value="<?php echo esc_attr(get_user_meta($user->ID, self::META_KEY, true)); ?>" 
                        class="regular-text"
                    />
                    <p class="description">
                        <?php _e('Enter additional email addresses separated by commas. These addresses will receive copies of all WooCommerce order related emails sent to this customer. Example: email1@example.com, email2@example.com', 'woocommerce-email-cc'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save custom field
     */
    public function save_custom_user_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        if (isset($_POST[self::META_KEY])) {
            $email_addresses = sanitize_text_field($_POST[self::META_KEY]);
            
            // Validate email addresses
            $email_addresses = $this->validate_email_list($email_addresses);
            
            update_user_meta($user_id, self::META_KEY, $email_addresses);
        }
    }
    
    /**
     * Validate and clean email address list
     */
    private function validate_email_list($email_string) {
        if (empty($email_string)) {
            return '';
        }
        
        // Split by comma
        $emails = array_map('trim', explode(',', $email_string));
        
        // Validate each email
        $valid_emails = array();
        foreach ($emails as $email) {
            if (!empty($email) && is_email($email)) {
                $valid_emails[] = $email;
            }
        }
        
        return implode(', ', $valid_emails);
    }
    
    /**
     * Add CC headers to WooCommerce emails
     */
    public function add_cc_to_email_headers($headers, $email_id, $order) {
        // Define customer-facing email types only (order-related emails)
        $customer_email_types = array(
            'customer_on_hold_order',
            'customer_processing_order',
            'customer_completed_order',
            'customer_refunded_order',
            'customer_partially_refunded_order',
            'customer_invoice',
            'customer_failed_order'
        );
        
        // Only process customer-facing emails
        if (!in_array($email_id, $customer_email_types)) {
            return $headers;
        }
        
        // Only process if we have an order object
        if (!is_a($order, 'WC_Order')) {
            return $headers;
        }
        
        // Get the customer user ID
        $user_id = $order->get_user_id();
        
        // If no user ID (guest checkout), return original headers
        if (!$user_id) {
            return $headers;
        }
        
        // Get additional email addresses from user meta
        $additional_emails = get_user_meta($user_id, self::META_KEY, true);
        
        // If no additional emails, return original headers
        if (empty($additional_emails)) {
            return $headers;
        }
        
        // Parse email addresses
        $email_array = array_map('trim', explode(',', $additional_emails));
        
        // Validate and filter emails
        $valid_emails = array_filter($email_array, 'is_email');
        
        // If no valid emails, return original headers
        if (empty($valid_emails)) {
            return $headers;
        }
        
        // Ensure headers is an array or string we can work with
        if (!is_array($headers)) {
            $headers = array($headers);
        }
        
        // Add CC header for each email
        foreach ($valid_emails as $email) {
            $headers[] = 'Cc: ' . $email;
        }
        
        return $headers;
    }
}

// Initialize the plugin
function wc_email_cc_init() {
    // Check if WooCommerce is active
    if (class_exists('WooCommerce')) {
        new WC_Email_CC();
    } else {
        // Show admin notice if WooCommerce is not active
        add_action('admin_notices', 'wc_email_cc_admin_notice');
    }
}
add_action('plugins_loaded', 'wc_email_cc_init');

/**
 * Admin notice if WooCommerce is not active
 */
function wc_email_cc_admin_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('WooCommerce - Customer Order Email CC requires WooCommerce to be installed and active.', 'woocommerce-email-cc'); ?></p>
    </div>
    <?php
}
