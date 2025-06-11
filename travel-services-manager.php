<?php
/*
Plugin Name: Orange Travels
Description: Manage vehicles and tour packages with locations, pricing, and features.
Version: 2.5
Author: Md Waseel Mohiuddin
*/

if (!defined('ABSPATH')) exit;


// === Database Tables Setup ===
function tsm_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Locations table
    $locations_table = $wpdb->prefix . 'tsm_locations';
    $sql_locations = "CREATE TABLE IF NOT EXISTS $locations_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    // Vehicle assignments table
    $assignments_table = $wpdb->prefix . 'tsm_vehicle_assignments';
    $sql_assignments = "CREATE TABLE IF NOT EXISTS $assignments_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        vehicle_id mediumint(9) NOT NULL,
        location_id mediumint(9) NOT NULL,
        price decimal(10,2) NOT NULL,
        extra_km_price decimal(10,2) NOT NULL,
        extra_hour_price decimal(10,2) NOT NULL,
        max_range decimal(10,2) DEFAULT NULL,
        available TINYINT(1) NOT NULL DEFAULT 1,
        rental_type VARCHAR(50) NOT NULL DEFAULT 'Round Trip',
        PRIMARY KEY (id),
        KEY vehicle_id (vehicle_id),
        KEY location_id (location_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Create or update tables
    dbDelta($sql_locations);
    dbDelta($sql_assignments);
    
    // Check and add 'available' column if missing
    $schema_version = get_option('tsm_schema_version', '1.0');
    if (version_compare($schema_version, '2.1', '<')) {
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $assignments_table LIKE 'available'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $assignments_table ADD available TINYINT(1) NOT NULL DEFAULT 1");
        }
        $max_range_exists = $wpdb->get_results("SHOW COLUMNS FROM $assignments_table LIKE 'max_range'");
        if (empty($max_range_exists)) {
            $wpdb->query("ALTER TABLE $assignments_table ADD max_range decimal(10,2) DEFAULT NULL");
        }
        if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE $assignments_table ADD rental_type VARCHAR(50) NOT NULL DEFAULT 'Round Trip'");
        }
        update_option('tsm_schema_version', '2.2');
    }
}
register_activation_hook(__FILE__, 'tsm_create_tables');

// === Admin Menu ===
function tsm_admin_menu() {
    // Main menu page with dashboard callback
    add_menu_page(
        'Orange Travels',
      	 'Orange Travels',
        'manage_options',
        'tsm-dashboard',
        'tsm_dashboard_page',
        'dashicons-admin-site-alt3',
        6
    );
    
    // Submenu pages
    add_submenu_page(
        'tsm-dashboard',
        'Locations',
        'Locations',
        'manage_options',
        'tsm-locations',
        'tsm_locations_page'
    );
    
    add_submenu_page(
        'tsm-dashboard',
        'Vehicles',
        'Vehicles',
        'manage_options',
        'tsm-vehicles',
        'tsm_vehicles_page'
    );
    
    add_submenu_page(
        'tsm-dashboard',
        'Assign Locations',
        'Assign Locations',
        'manage_options',
        'tsm-assign-locations',
        'tsm_assign_locations_page'
    );
    
    add_submenu_page(
        'tsm-dashboard',
        'Tour Packages',
        'Tour Packages',
        'manage_options',
        'tsm-tour-packages',
        'tsm_tour_packages_page'
    );
    
    add_submenu_page(
        'tsm-dashboard',
        'Button URLs',
        'Button URLs',
        'manage_options',
        'tsm-button-urls',
        'tsm_button_urls_page'
    );
}
add_action('admin_menu', 'tsm_admin_menu');

// === Dashboard Page ===
function tsm_dashboard_page() {
    ?>
    <div class="wrap">
        <h1>Orange Travel Services Dashboard</h1>
        <p>Welcome to the Orange Travel Services Manager. Use the buttons below to manage different aspects of your travel services.</p>
        
        <div class="tsm-dashboard-buttons" style="margin-top: 20px;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=tsm-locations')); ?>" class="button button-primary" style="margin-right: 10px;">Manage Locations</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=tsm-vehicles')); ?>" class="button button-primary" style="margin-right: 10px;">Manage Vehicles</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=tsm-assign-locations')); ?>" class="button button-primary" style="margin-right: 10px;">Assign Locations</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=tsm-tour-packages')); ?>" class="button button-primary" style="margin-right: 10px;">Manage Tour Packages</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=tsm-tour-packages')); ?>" class="button button-primary" style="margin-right: 10px;">Manage Tour Packages</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=tsm-button-urls')); ?>" class="button button-primary">Manage Button URLs</a>
        </div>
    </div>
    <?php
}


function tsm_button_urls_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Handle form submission
    if (isset($_POST['tsm_button_urls_submit']) && check_admin_referer('tsm_button_urls_nonce')) {
        // Sanitize and save Book Now URL
        $book_now_url = isset($_POST['tsm_book_now_url']) ? esc_url_raw($_POST['tsm_book_now_url']) : '';
        update_option('tsm_book_now_url', $book_now_url);

        // Sanitize and save Contact URL
        $contact_url = isset($_POST['tsm_contact_url']) ? esc_url_raw($_POST['tsm_contact_url']) : '';
        update_option('tsm_contact_url', $contact_url);

        // Display success message
        echo '<div class="notice notice-success is-dismissible"><p>Button URLs saved successfully!</p></div>';
    }

    // Get current URLs
    $book_now_url = get_option('tsm_book_now_url', '#');
    $contact_url = get_option('tsm_contact_url', '#');
    ?>
    <div class="wrap">
        <h1>Manage Button URLs</h1>
        <p>Configure the URLs for the "Book Now" and "Contact" buttons displayed on the tour pages.</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('tsm_button_urls_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="tsm_book_now_url">Book Now URL</label></th>
                    <td>
                        <input type="url" name="tsm_book_now_url" id="tsm_book_now_url" value="<?php echo esc_attr($book_now_url); ?>" class="regular-text" placeholder="https://example.com/book-now">
                        <p class="description">Enter the URL for the "Book Now" button.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="tsm_contact_url">Contact URL</label></th>
                    <td>
                        <input type="url" name="tsm_contact_url" id="tsm_contact_url" value="<?php echo esc_attr($contact_url); ?>" class="regular-text" placeholder="https://example.com/contact">
                        <p class="description">Enter the URL for the "Contact" button.</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="tsm_button_urls_submit" class="button button-primary" value="Save URLs">
            </p>
        </form>
    </div>
    <?php
}



// === Tour Packages Management ===
function tsm_tour_packages_page() {
    global $wpdb;

    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Handle add form submission
    if (isset($_POST['add_tour'])) {
        check_admin_referer('tsm_add_tour');
        
        $post_data = [
            'post_title'   => sanitize_text_field($_POST['tour_name'] ?? ''),
            'post_content' => wp_kses_post($_POST['tour_description'] ?? ''),
            'post_type'    => 'tsm_tour',
            'post_status'  => 'publish',
        ];
        
        if (empty($post_data['post_title'])) {
            echo '<div class="notice notice-error"><p>Tour name is required.</p></div>';
        } else {
            $tour_id = wp_insert_post($post_data, true);
            
            if (is_wp_error($tour_id)) {
                echo '<div class="notice notice-error"><p>Error adding tour: ' . esc_html($tour_id->get_error_message()) . '</p></div>';
            } else {
                // Save basic information
                update_post_meta($tour_id, '_tsm_location', sanitize_text_field($_POST['location'] ?? ''));
                update_post_meta($tour_id, '_tsm_price_per_person', floatval($_POST['price_per_person'] ?? 0));
                update_post_meta($tour_id, '_tsm_days', intval($_POST['days'] ?? 0));
                update_post_meta($tour_id, '_tsm_nights', intval($_POST['nights'] ?? 0));
                update_post_meta($tour_id, '_tsm_featured', isset($_POST['featured']) ? 1 : 0);
                
                //  save tour type 
                update_post_meta($tour_id, '_tsm_tour_type', sanitize_text_field($_POST['tour_type'] ?? ''));
                
                // Save max people (only for Group Tour, default to 2 for Honeymoon Tour)
                if ($_POST['tour_type'] === 'Group Tour') {
                    update_post_meta($tour_id, '_tsm_max_people', intval($_POST['max_people'] ?? 0));
                } elseif ($_POST['tour_type'] === 'Honeymoon Tour') {
                    update_post_meta($tour_id, '_tsm_max_people', 2);
                } else {
                    delete_post_meta($tour_id, '_tsm_max_people');
                }
                
                // Handle featured image
                if (!empty($_FILES['featured_image']['name'])) {
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                    require_once(ABSPATH . 'wp-admin/includes/media.php');
                    
                    $attachment_id = media_handle_upload('featured_image', $tour_id);
                    if (!is_wp_error($attachment_id)) {
                        set_post_thumbnail($tour_id, $attachment_id);
                    }
                }
                
                // Handle gallery images
                if (!empty($_FILES['gallery_images']['name'][0])) {
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                    require_once(ABSPATH . 'wp-admin/includes/media.php');

                    $gallery_ids = [];
                    $files = $_FILES['gallery_images'];
                    $file_count = count($files['name']);

                    // Reorganize the $_FILES array for easier processing
                    for ($i = 0; $i < $file_count; $i++) {
                        if ($files['name'][$i] && $files['error'][$i] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $files['name'][$i],
                                'type' => $files['type'][$i],
                                'tmp_name' => $files['tmp_name'][$i],
                                'error' => $files['error'][$i],
                                'size' => $files['size'][$i]
                            ];
                            $_FILES['gallery_image_' . $i] = $file;
                            $gallery_id = media_handle_upload('gallery_image_' . $i, $tour_id);
                            if (!is_wp_error($gallery_id)) {
                                $gallery_ids[] = $gallery_id;
                            } else {
                                echo '<div class="notice notice-warning"><p>Failed to upload gallery image: ' . esc_html($gallery_id->get_error_message()) . '</p></div>';
                            }
                        }
                    }
                    if (!empty($gallery_ids)) {
                        update_post_meta($tour_id, '_tsm_gallery_images', $gallery_ids);
                    }
                }
                
                // Save places covered
                update_post_meta($tour_id, '_tsm_places_covered', sanitize_text_field($_POST['places_covered'] ?? ''));
                
                // Save itinerary
                $itinerary = [];
                if (!empty($_POST['itinerary'])) {
                    foreach ($_POST['itinerary'] as $day) {
                        $itinerary[] = [
                            'day_number' => intval($day['day_number'] ?? 0),
                            'date' => sanitize_text_field($day['date'] ?? ''),
                            'title' => sanitize_text_field($day['title'] ?? ''),
                            'description' => wp_kses_post($day['description'] ?? '')
                        ];
                    }
                }
                update_post_meta($tour_id, '_tsm_itinerary', $itinerary);
                
                // Save features
                $features = [];
                if (!empty($_POST['features'])) {
                    foreach ($_POST['features'] as $feature) {
                        $features[] = [
                            'icon' => sanitize_text_field($feature['icon'] ?? ''),
                            'title' => sanitize_text_field($feature['title'] ?? ''),
                            'description' => sanitize_text_field($feature['description'] ?? '')
                        ];
                    }
                }
                update_post_meta($tour_id, '_tsm_features', $features);
                
                // Save guidelines
                $guidelines = !empty($_POST['guidelines']) ? array_map('sanitize_textarea_field', explode("\n", $_POST['guidelines'])) : [];
                update_post_meta($tour_id, '_tsm_guidelines', $guidelines);
                
                echo '<div class="notice notice-success"><p>Tour package added successfully!</p></div>';
            }
        }
    }
    
    // Handle edit form submission
    if (isset($_POST['edit_tour'])) {
        check_admin_referer('tsm_edit_tour');
        
        $tour_id = intval($_POST['tour_id'] ?? 0);
        $post_data = [
            'ID'           => $tour_id,
            'post_title'   => sanitize_text_field($_POST['tour_name'] ?? ''),
            'post_content' => wp_kses_post($_POST['tour_description'] ?? ''),
        ];
        
        if (empty($post_data['post_title']) || $tour_id <= 0) {
            echo '<div class="notice notice-error"><p>Tour name and valid ID are required.</p></div>';
        } else {
            $result = wp_update_post($post_data, true);
            
            if (is_wp_error($result)) {
                echo '<div class="notice notice-error"><p>Error updating tour: ' . esc_html($result->get_error_message()) . '</p></div>';
            } else {
                // Update basic information
                update_post_meta($tour_id, '_tsm_location', sanitize_text_field($_POST['location'] ?? ''));
                update_post_meta($tour_id, '_tsm_price_per_person', floatval($_POST['price_per_person'] ?? 0));
                update_post_meta($tour_id, '_tsm_days', intval($_POST['days'] ?? 0));
                update_post_meta($tour_id, '_tsm_nights', intval($_POST['nights'] ?? 0));
                update_post_meta($tour_id, '_tsm_featured', isset($_POST['featured']) ? 1 : 0);
                
                //tour type 
                update_post_meta($tour_id, '_tsm_tour_type', sanitize_text_field($_POST['tour_type'] ?? ''));
                
                // Update max people (only for Group Tour, default to 2 for Honeymoon Tour)
                if ($_POST['tour_type'] === 'Group Tour') {
                    update_post_meta($tour_id, '_tsm_max_people', intval($_POST['max_people'] ?? 0));
                } elseif ($_POST['tour_type'] === 'Honeymoon Tour') {
                    update_post_meta($tour_id, '_tsm_max_people', 2);
                } else {
                    delete_post_meta($tour_id, '_tsm_max_people');
                }
                
                // Handle featured image
                if (!empty($_FILES['featured_image']['name'])) {
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                    require_once(ABSPATH . 'wp-admin/includes/media.php');
                    
                    $attachment_id = media_handle_upload('featured_image', $tour_id);
                    if (!is_wp_error($attachment_id)) {
                        set_post_thumbnail($tour_id, $attachment_id);
                    }
                }
                
                // Handle gallery images
                // Handle gallery images
$gallery_ids = get_post_meta($tour_id, '_tsm_gallery_images', true) ?: [];
$deleted_images = isset($_POST['delete_gallery_images']) ? array_map('intval', $_POST['delete_gallery_images']) : [];

if (!empty($_FILES['gallery_images']['name'][0]) || !empty($deleted_images)) {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    // Remove deleted images
    if (!empty($deleted_images)) {
        $gallery_ids = array_diff($gallery_ids, $deleted_images);
        foreach ($deleted_images as $attachment_id) {
            wp_delete_attachment($attachment_id, true);
        }
    }

    // Add new images
    if (!empty($_FILES['gallery_images']['name'][0])) {
        $files = $_FILES['gallery_images'];
        $file_count = count($files['name']);

        for ($i = 0; $i < $file_count; $i++) {
            if ($files['name'][$i] && $files['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                $_FILES['gallery_image_' . $i] = $file;
                $gallery_id = media_handle_upload('gallery_image_' . $i, $tour_id);
                if (!is_wp_error($gallery_id)) {
                    $gallery_ids[] = $gallery_id;
                } else {
                    echo '<div class="notice notice-warning"><p>Failed to upload gallery image: ' . esc_html($gallery_id->get_error_message()) . '</p></div>';
                }
            }
        }
    }

    // Update gallery meta only if there are changes
    if (!empty($gallery_ids)) {
            update_post_meta($tour_id, '_tsm_gallery_images', array_unique($gallery_ids));
        } else {
            delete_post_meta($tour_id, '_tsm_gallery_images');
        }
    }
                
                // Update places covered
                update_post_meta($tour_id, '_tsm_places_covered', sanitize_text_field($_POST['places_covered'] ?? ''));
                
                // Update itinerary
                $itinerary = [];
                if (!empty($_POST['itinerary'])) {
                    foreach ($_POST['itinerary'] as $day) {
                        $itinerary[] = [
                            'day_number' => intval($day['day_number'] ?? 0),
                            'date' => sanitize_text_field($day['date'] ?? ''),
                            'title' => sanitize_text_field($day['title'] ?? ''),
                            'description' => wp_kses_post($day['description'] ?? '')
                        ];
                    }
                }
                update_post_meta($tour_id, '_tsm_itinerary', $itinerary);
                
                // Update features
                $features = [];
                if (!empty($_POST['features'])) {
                    foreach ($_POST['features'] as $feature) {
                        $features[] = [
                            'icon' => sanitize_text_field($feature['icon'] ?? ''),
                            'title' => sanitize_text_field($feature['title'] ?? ''),
                            'description' => sanitize_text_field($feature['description'] ?? '')
                        ];
                    }
                }
                update_post_meta($tour_id, '_tsm_features', $features);
                
                // Update guidelines
                $guidelines = !empty($_POST['guidelines']) ? array_map('sanitize_textarea_field', explode("\n", $_POST['guidelines'])) : [];
                update_post_meta($tour_id, '_tsm_guidelines', $guidelines);
                
                echo '<div class="notice notice-success"><p>Tour package updated successfully!</p></div>';
            }
        }
    }
    
    // Handle delete
    if (isset($_GET['delete_tour'])) {
        check_admin_referer('tsm_delete_tour');
        $tour_id = intval($_GET['delete_tour']);
        $result = wp_delete_post($tour_id, true);
        
        if ($result === false) {
            echo '<div class="notice notice-error"><p>Error deleting tour package.</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>Tour package deleted successfully!</p></div>';
        }
    }
    
    // Get all tours
    $tours = get_posts([
        'post_type' => 'tsm_tour',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    // Handle edit form display
    $edit_tour = null;
    $itinerary = []; // Initialize to avoid undefined variable
    $features = [];
    if (isset($_GET['edit_tour'])) {
        check_admin_referer('tsm_edit_tour_nonce');
        $edit_tour = get_post(intval($_GET['edit_tour']));
    }
    
    ?>
    <div class="wrap">
        <h1>Tour Packages</h1>
        
        <?php if ($edit_tour) { ?>
            <form method="post" enctype="multipart/form-data" style="margin-bottom: 20px;">
                <?php wp_nonce_field('tsm_edit_tour'); ?>
                <input type="hidden" name="tour_id" value="<?php echo esc_attr($edit_tour->ID); ?>">
                <h2>Edit Tour Package</h2>
                
                <!-- Tour Basic Information -->
                <h3>Tour Basic Information</h3>
                
<table class="form-table">
    <tr>
        <th><label for="tour_name">Tour Name</label></th>
        <td><input type="text" name="tour_name" id="tour_name" value="<?php echo esc_attr($edit_tour->post_title); ?>" required class="regular-text"></td>
    </tr>
    <tr>
        <th><label for="tour_description">Description</label></th>
        <td>
            <?php wp_editor($edit_tour->post_content, 'tour_description', ['textarea_rows' => 10, 'media_buttons' => false]); ?>
        </td>
    </tr>
    <tr>
        <th><label for="location">Location</label></th>
        <td><input type="text" name="location" id="location" value="<?php echo esc_attr(get_post_meta($edit_tour->ID, '_tsm_location', true)); ?>" class="regular-text"></td>
    </tr>
    <tr>
        <th><label for="price_per_person">Price per Person</label></th>
        <td><input type="number" name="price_per_person" id="price_per_person" value="<?php echo esc_attr(get_post_meta($edit_tour->ID, '_tsm_price_per_person', true)); ?>" min="0" step="0.01" class="regular-text"></td>
    </tr>
    <tr>
        <th><label for="days">Days</label></th>
        <td><input type="number" name="days" id="days" value="<?php echo esc_attr(get_post_meta($edit_tour->ID, '_tsm_days', true)); ?>" min="0" class="regular-text"></td>
    </tr>
    <tr>
        <th><label for="nights">Nights</label></th>
        <td><input type="number" name="nights" id="nights" value="<?php echo esc_attr(get_post_meta($edit_tour->ID, '_tsm_nights', true)); ?>" min="0" class="regular-text"></td>
    </tr>
    <tr>
        <th><label for="featured">Featured Tour</label></th>
        <td>
            <input type="checkbox" name="featured" id="featured" value="1" <?php checked(get_post_meta($edit_tour->ID, '_tsm_featured', true), 1); ?>>
            <label for="featured">Mark as Featured</label>
        </td>
    </tr>
    <tr>
        <th><label for="tour_type">Tour Type</label></th>
        <td>
            <select name="tour_type" id="tour_type" required class="regular-text">
                <option value="">Select Tour Type</option>
                <option value="Group Tour" <?php selected(get_post_meta($edit_tour->ID, '_tsm_tour_type', true), 'Group Tour'); ?>>Group Tour</option>
                <option value="Private Tour" <?php selected(get_post_meta($edit_tour->ID, '_tsm_tour_type', true), 'Private Tour'); ?>>Private Tour</option>
                <option value="Honeymoon Tour" <?php selected(get_post_meta($edit_tour->ID, '_tsm_tour_type', true), 'Honeymoon Tour'); ?>>Honeymoon Tour</option>
                <option value="Adventure Tour" <?php selected(get_post_meta($edit_tour->ID, '_tsm_tour_type', true), 'Adventure Tour'); ?>>Adventure Tour</option>
                <option value="Cultural Tour" <?php selected(get_post_meta($edit_tour->ID, '_tsm_tour_type', true), 'Cultural Tour'); ?>>Cultural Tour</option>
                <option value="Wildlife Tour" <?php selected(get_post_meta($edit_tour->ID, '_tsm_tour_type', true), 'Wildlife Tour'); ?>>Wildlife Tour</option>
                <option value="Pilgrimage Tour" <?php selected(get_post_meta($edit_tour->ID, '_tsm_tour_type', true), 'Pilgrimage Tour'); ?>>Pilgrimage Tour</option>
                <option value="Luxury Tour" <?php selected(get_post_meta($edit_tour->ID, '_tsm_tour_type', true), 'Luxury Tour'); ?>>Luxury Tour</option>
                <option value="Weekend Getaway" <?php selected(get_post_meta($edit_tour->ID, '_tsm_tour_type', true), 'Weekend Getaway'); ?>>Weekend Getaway</option>
                <option value="Corporate Tour" <?php selected(get_post_meta($edit_tour->ID, '_tsm_tour_type', true), 'Corporate Tour'); ?>>Corporate Tour</option>
                <option value="Cruise Tour" <?php selected(get_post_meta($edit_tour->ID, '_tsm_tour_type', true), 'Cruise Tour'); ?>>Cruise Tour</option>
                <option value="Eco Tour" <?php selected(get_post_meta($edit_tour->ID, '_tsm_tour_type', true), 'Eco Tour'); ?>>Eco Tour</option>
            </select>
        </td>
    </tr>
    <tr>
        <th><label for="max_people">Max People</label></th>
        <td>
            <input type="number" name="max_people" id="max_people" value="<?php echo esc_attr(get_post_meta($edit_tour->ID, '_tsm_max_people', true)); ?>" min="1" class="regular-text" <?php echo get_post_meta($edit_tour->ID, '_tsm_tour_type', true) !== 'Group Tour' ? 'disabled' : ''; ?>>
            <p class="description">Enter the maximum number of people for Group Tours. Defaults to 2 for Honeymoon Tours.</p>
        </td>
    </tr>
</table>
                </table>
                
                <!-- Images -->
                <h3>Images</h3>
                <table class="form-table">
                    <tr>
                        <th><label for="featured_image">Featured Image</label></th>
                        <td>
                            <?php if (has_post_thumbnail($edit_tour->ID)) { ?>
                                <?php echo get_the_post_thumbnail($edit_tour->ID, [100, 100]); ?>
                                <p>Upload a new image to replace the current one.</p>
                            <?php } ?>
                            <input type="file" name="featured_image" id="featured_image" accept="image/*">
                        </td>
                    </tr>
            <tr>
                <th><label for="gallery_images">Gallery Images</label></th>
            <td>
                <?php
                $gallery_ids = get_post_meta($edit_tour->ID, '_tsm_gallery_images', true) ?: [];
                if (!empty($gallery_ids)) {
                    echo '<div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">';
                    foreach ($gallery_ids as $id) {
                        $image_url = wp_get_attachment_url($id);
                        if ($image_url) {
                            echo '<div style="position: relative;">';
                            echo '<img src="' . esc_url($image_url) . '" style="width: 100px; height: 100px; object-fit: cover;">';
                            echo '<label style="position: absolute; top: 5px; right: 5px; background: rgba(255,255,255,0.7); padding: 2px;">';
                            echo '<input type="checkbox" name="delete_gallery_images[]" value="' . esc_attr($id) . '"> Delete';
                            echo '</label>';
                            echo '</div>';
                        }
                    }
                    echo '</div>';
                }
                ?>
                <input type="file" name="gallery_images[]" id="gallery_images" accept="image/*" multiple>
                <p class="description">Select multiple images to add to the gallery. Check the boxes to delete existing images.</p>
            </td>
        </tr>
                </table>
                
                <!-- Places Covered -->
                <h3>Places Covered</h3>
                <table class="form-table">
                    <tr>
                        <th><label for="places_covered">Places</label></th>
                        <td><input type="text" name="places_covered" id="places_covered" value="<?php echo esc_attr(get_post_meta($edit_tour->ID, '_tsm_places_covered', true)); ?>" class="regular-text"></td>
                    </tr>
                </table>
                
                <!-- Itinerary -->
                <h3>Itinerary (Day-wise)</h3>
                <div id="itinerary-container">
                    <?php
                    $itinerary = get_post_meta($edit_tour->ID, '_tsm_itinerary', true) ?: [];
                    if (empty($itinerary)) {
                        $itinerary[] = ['day_number' => 1, 'date' => '', 'title' => '', 'description' => ''];
                    }
                    foreach ($itinerary as $index => $day) {
                    ?>
                        <div class="itinerary-day" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px;">
                            <table class="form-table">
                                <tr>
                                    <th><label>Day Number</label></th>
                                    <td><input type="number" name="itinerary[<?php echo $index; ?>][day_number]" value="<?php echo esc_attr($day['day_number']); ?>" min="1" required></td>
                                </tr>
                                <tr>
                                    <th><label>Date</label></th>
                                    <td><input type="date" name="itinerary[<?php echo $index; ?>][date]" value="<?php echo esc_attr($day['date']); ?>"></td>
                                </tr>
                                <tr>
                                    <th><label>Title</label></th>
                                    <td><input type="text" name="itinerary[<?php echo $index; ?>][title]" value="<?php echo esc_attr($day['title']); ?>" class="regular-text"></td>
                                </tr>
                                <tr>
                                    <th><label>Description</label></th>
                                    <td>
                                        <?php wp_editor($day['description'], 'itinerary_' . $index, ['textarea_name' => 'itinerary[' . $index . '][description]', 'textarea_rows' => 5, 'media_buttons' => false]); ?>
                                    </td>
                                </tr>
                            </table>
                            <button type="button" class="button remove-day">Remove Day</button>
                        </div>
                    <?php } ?>
                </div>
                <button type="button" id="add-day" class="button">Add Day</button>
                
                <!-- Features -->
                <h3>Tour Information (Features)</h3>
                <div id="features-container">
                    <?php
                    $features = get_post_meta($edit_tour->ID, '_tsm_features', true) ?: [];
                    if (empty($features)) {
                        $features[] = ['icon' => '', 'title' => '', 'description' => ''];
                    }
                    $icons = ['wifi', 'car', 'utensils', 'shield-alt', 'bed', 'swimming-pool', 'map'];
                    foreach ($features as $index => $feature) {
                    ?>
                        <div class="feature" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px;">
                            <table class="form-table">
                                <tr>
                                    <th><label>Icon</label></th>
                                    <td>
                                        <select name="features[<?php echo $index; ?>][icon]">
                                            <option value="">Select Icon</option>
                                            <?php foreach ($icons as $icon) { ?>
                                                <option value="<?php echo esc_attr($icon); ?>" <?php selected($feature['icon'], $icon); ?>>
                                                    <?php echo esc_html(ucfirst(str_replace('-', ' ', $icon))); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>Title</label></th>
                                    <td><input type="text" name="features[<?php echo $index; ?>][title]" value="<?php echo esc_attr($feature['title']); ?>" class="regular-text"></td>
                                </tr>
                                <tr>
                                    <th><label>Description</label></th>
                                    <td><input type="text" name="features[<?php echo $index; ?>][description]" value="<?php echo esc_attr($feature['description']); ?>" class="regular-text"></td>
                                </tr>
                            </table>
                            <button type="button" class="button remove-feature">Remove Feature</button>
                        </div>
                    <?php } ?>
                </div>
                <button type="button" id="add-feature" class="button">Add Feature</button>
                
                <!-- Need to Know Section -->
                <h3>Need to Know</h3>
                <table class="form-table">
                    <tr>
                        <th><label for="guidelines">Guidelines</label></th>
                        <td>
                            <textarea name="guidelines" id="guidelines" rows="6" class="regular-text"><?php echo esc_textarea(implode("\n", get_post_meta($edit_tour->ID, '_tsm_guidelines', true) ?: [])); ?></textarea>
                            <p class="description">Enter each guideline on a new line. Each line will be displayed as a bullet point.</p>
                        </td>
                    </tr>
                </table>
                
                <input type="submit" name="edit_tour" class="button button-primary" value="Update Tour Package">
                <a href="<?php echo esc_url(admin_url('admin.php?page=tsm-tour-packages')); ?>" class="button button-secondary">Cancel</a>
            </form>
        <?php } else { ?>
            <form method="post" enctype="multipart/form-data" style="margin-bottom: 20px;">
                <?php wp_nonce_field('tsm_add_tour'); ?>
                <h2>Add New Tour Package</h2>
                
                <!-- Tour Basic Information -->
                <h3>Tour Basic Information</h3>
                <table class="form-table">
                    <tr>
                        <th><label for="tour_name">Tour Name</label></th>
                        <td><input type="text" name="tour_name" id="tour_name" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="tour_description">Description</label></th>
                        <td>
                            <?php wp_editor('', 'tour_description', ['textarea_rows' => 10, 'media_buttons' => false]); ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="location">Location</label></th>
                        <td><input type="text" name="location" id="location" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="price_per_person">Price per Person</label></th>
                        <td><input type="number" name="price_per_person" id="price_per_person" min="0" step="0.01" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="days">Days</label></th>
                        <td><input type="number" name="days" id="days" min="0" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="nights">Nights</label></th>
                        <td><input type="number" name="nights" id="nights" min="0" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="featured">Featured Tour</label></th>
                        <td>
                            <input type="checkbox" name="featured" id="featured" value="1">
                            <label for="featured">Mark as Featured</label>
                        </td>
                    </tr>
                    <tr>
        <th><label for="tour_type">Tour Type</label></th>
        <td>
            <select name="tour_type" id="tour_type" required class="regular-text">
                <option value="">Select Tour Type</option>
                <option value="Group Tour">Group Tour</option>
                <option value="Private Tour">Private Tour</option>
                <option value="Honeymoon Tour">Honeymoon Tour</option>
                <option value="Adventure Tour">Adventure Tour</option>
                <option value="Cultural Tour">Cultural Tour</option>
                <option value="Wildlife Tour">Wildlife Tour</option>
                <option value="Pilgrimage Tour">Pilgrimage Tour</option>
                <option value="Luxury Tour">Luxury Tour</option>
                <option value="Weekend Getaway">Weekend Getaway</option>
                <option value="Corporate Tour">Corporate Tour</option>
                <option value="Cruise Tour">Cruise Tour</option>
                <option value="Eco Tour">Eco Tour</option>
            </select>
        </td>
    </tr>
    <tr>
        <th><label for="max_people">Max People</label></th>
        <td>
            <input type="number" name="max_people" id="max_people" min="1" class="regular-text" disabled>
            <p class="description">Enter the maximum number of people for Group Tours. Defaults to 2 for Honeymoon Tours.</p>
        </td>
    </tr>
                </table>
                
                <!-- Images -->
                <h3>Images</h3>
                <table class="form-table">
                    <tr>
                        <th><label for="featured_image">Featured Image</label></th>
                        <td><input type="file" name="featured_image" id="featured_image" accept="image/*"></td>
                    </tr>
                    <tr>
                        <th><label for="gallery_images">Gallery Images</label></th>
                        <td>
                            <input type="file" name="gallery_images[]" id="gallery_images" accept="image/*" multiple>
                            <p class="description">Select multiple images for the gallery.</p>
                        </td>
                    </tr>
                </table>
                
                <!-- Places Covered -->
                <h3>Places Covered</h3>
                <table class="form-table">
                    <tr>
                        <th><label for="places_covered">Places</label></th>
                        <td><input type="text" name="places_covered" id="places_covered" class="regular-text"></td>
                    </tr>
                </table>
                
                <!-- Itinerary -->
                <h3>Itinerary (Day-wise)</h3>
                <div id="itinerary-container">
                    <div class="itinerary-day" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px;">
                        <table class="form-table">
                            <tr>
                                <th><label>Day Number</label></th>
                                <td><input type="number" name="itinerary[0][day_number]" value="1" min="1" required></td>
                            </tr>
                            <tr>
                                <th><label>Date</label></th>
                                <td><input type="date" name="itinerary[0][date]"></td>
                            </tr>
                            <tr>
                                <th><label>Title</label></th>
                                <td><input type="text" name="itinerary[0][title]" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label>Description</label></th>
                                <td>
                                    <?php wp_editor('', 'itinerary_0', ['textarea_name' => 'itinerary[0][description]', 'textarea_rows' => 5, 'media_buttons' => false]); ?>
                                </td>
                            </tr>
                        </table>
                        <button type="button" class="button remove-day">Remove Day</button>
                    </div>
                </div>
                <button type="button" id="add-day" class="button">Add Day</button>
                
                <!-- Features -->
                <h3>Tour Information (Features)</h3>
                <div id="features-container">
                    <div class="feature" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px;">
                        <table class="form-table">
                            <tr>
                                <th><label>Icon</label></th>
                                <td>
                                    <select name="features[0][icon]">
                                        <option value="">Select Icon</option>
                                        <?php
                                        $icons = ['wifi', 'car', 'utensils', 'shield-alt', 'bed', 'swimming-pool', 'map'];
                                        foreach ($icons as $icon) {
                                            echo '<option value="' . esc_attr($icon) . '">' . esc_html(ucfirst(str_replace('-', ' ', $icon))) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label>Title</label></th>
                                <td><input type="text" name="features[0][title]" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label>Description</label></th>
                                <td><input type="text" name="features[0][description]" class="regular-text"></td>
                            </tr>
                        </table>
                        <button type="button" class="button remove-feature">Remove Feature</button>
                    </div>
                </div>
                <button type="button" id="add-feature" class="button">Add Feature</button>
                
                <!-- Need to Know Section -->
                <h3>Need to Know</h3>
                <table class="form-table">
                    <tr>
                        <th><label for="guidelines">Guidelines</label></th>
                        <td>
                            <textarea name="guidelines" id="guidelines" rows="6" class="regular-text"></textarea>
                            <p class="description">Enter each guideline on a new line. Each line will be displayed as a bullet point.</p>
                        </td>
                    </tr>
                </table>
                
                <input type="submit" name="add_tour" class="button button-primary" value="Add Tour Package">
            </form>
        <?php } ?>
        
        <h2>Existing Tour Packages</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Price</th>
                    <th>Duration</th>
                    <th>Places</th>
                    <th>Max People</th>
                    <th>Featured</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tours)) { ?>
                    <tr>
                        <td colspan="10">No tour packages found.</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($tours as $tour) { ?>
                        <tr>
                            <td><?php echo esc_html($tour->ID); ?></td>
                            <td>
                                <?php if (has_post_thumbnail($tour->ID)) { ?>
                                    <?php echo get_the_post_thumbnail($tour->ID, [50, 50]); ?>
                                <?php } ?>
                            </td>
                            <td><?php echo esc_html($tour->post_title); ?></td>
                            <td><?php echo esc_html(get_post_meta($tour->ID, '_tsm_location', true)); ?></td>
                            <td><?php echo esc_html(number_format(get_post_meta($tour->ID, '_tsm_price_per_person', true), 2)); ?></td>
                            <td><?php echo esc_html(get_post_meta($tour->ID, '_tsm_days', true)) . ' days / ' . esc_html(get_post_meta($tour->ID, '_tsm_nights', true)) . ' nights'; ?></td>
                            <td><?php echo esc_html(get_post_meta($tour->ID, '_tsm_places_covered', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($tour->ID, '_tsm_max_people', true) ?: 'N/A'); ?></td>
                            <td><?php echo get_post_meta($tour->ID, '_tsm_featured', true) ? 'Yes' : 'No'; ?></td>
                            <td>
                                <a href="<?php echo wp_nonce_url(
                                    admin_url('admin.php?page=tsm-tour-packages&edit_tour=' . $tour->ID),
                                    'tsm_edit_tour_nonce'
                                ); ?>" class="button button-secondary">Edit</a>
                                <a href="<?php echo wp_nonce_url(
                                    admin_url('admin.php?page=tsm-tour-packages&delete_tour=' . $tour->ID),
                                    'tsm_delete_tour'
                                ); ?>" class="button button-secondary" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <script>
        
    jQuery(document).ready(function($) {
        let dayIndex = <?php echo count($itinerary); ?>;
        let featureIndex = <?php echo count($features); ?>;
        

        // Add new itinerary day
        $('#add-day').click(function() {
            const dayHtml = `
                <div class="itinerary-day" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px;">
                    <table class="form-table">
                        <tr>
                            <th><label>Day Number</label></th>
                            <td><input type="number" name="itinerary[${dayIndex}][day_number]" value="${dayIndex + 1}" min="1" required></td>
                        </tr>
                        <tr>
                            <th><label>Date</label></th>
                            <td><input type="date" name="itinerary[${dayIndex}][date]"></td>
                        </tr>
                        <tr>
                            <th><label>Title</label></th>
                            <td><input type="text" name="itinerary[${dayIndex}][title]" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label>Description</label></th>
                            <td>
                                <textarea name="itinerary[${dayIndex}][description]" id="itinerary_${dayIndex}" rows="5" class="regular-text"></textarea>
                            </td>
                        </tr>
                    </table>
                    <button type="button" class="button remove-day">Remove Day</button>
                </div>`;
            $('#itinerary-container').append(dayHtml);
            wp.editor.initialize('itinerary_' + dayIndex, {
                tinymce: { height: 200, menubar: false, plugins: 'lists link', toolbar: 'bold italic bullist numlist link' },
                quicktags: true
            });
            dayIndex++;
        });
        

        // Remove itinerary day
        $(document).on('click', '.remove-day', function() {
            if ($('.itinerary-day').length > 1) {
                const editorId = $(this).closest('.itinerary-day').find('textarea').attr('id');
                wp.editor.remove(editorId);
                $(this).closest('.itinerary-day').remove();
            }
        });

        // Add new feature
        $('#add-feature').click(function() {
            const featureHtml = `
                <div class="feature" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px;">
                    <table class="form-table">
                        <tr>
                            <th><label>Icon</label></th>
                            <td>
                                <select name="features[${featureIndex}][icon]">
                                    <option value="">Select Icon</option>
                                    <?php foreach ($icons as $icon) { ?>
                                        <option value="<?php echo esc_attr($icon); ?>">
                                            <?php echo esc_html(ucfirst(str_replace('-', ' ', $icon))); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Title</label></th>
                            <td><input type="text" name="features[${featureIndex}][title]" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label>Description</label></th>
                            <td><input type="text" name="features[${featureIndex}][description]" class="regular-text"></td>
                        </tr>
                    </table>
                    <button type="button" class="button remove-feature">Remove Feature</button>
                </div>`;
            $('#features-container').append(featureHtml);
            featureIndex++;
        });

        // Remove feature
        $(document).on('click', '.remove-feature', function() {
            if ($('.feature').length > 1) {
                $(this).closest('.feature').remove();
            }
        });
        // Control Max People field based on Tour Type
            $('#tour_type').on('change', function() {
            var tourType = $(this).val();
            var $maxPeople = $('#max_people');

            if (tourType === 'Honeymoon Tour') {
                $maxPeople.prop('disabled', true).val('2').prop('required', false);
            } else {
                $maxPeople.prop('disabled', false).prop('required', true).val('');
            }
        });

        $('#tour_type').trigger('change');
        // Gallery image preview
$('#gallery_images').on('change', function(e) {
    var $previewContainer = $(this).prev('div');
    if (!$previewContainer.length) {
        $previewContainer = $('<div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;"></div>').insertBefore(this);
    }
    
    // Clear previous previews
    $previewContainer.find('.new-image-preview').remove();
    
    // Add new previews
    var files = e.target.files;
    for (var i = 0; i < files.length; i++) {
        var file = files[i];
        if (file.type.match('image.*')) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var $img = $('<img>', {
                    src: e.target.result,
                    class: 'new-image-preview',
                    style: 'width: 100px; height: 100px; object-fit: cover;'
                });
                $previewContainer.append($img);
            };
            reader.readAsDataURL(file);
        }
    }
});
    });
    
    </script>
    <?php
}

// === Locations Management ===
function tsm_locations_page() {
    global $wpdb;
    
    // Handle add form submission
    if (isset($_POST['add_location'])) {
        check_admin_referer('tsm_add_location');
        $name = sanitize_text_field($_POST['location_name']);
        
        if (!empty($name)) {
            $wpdb->insert(
                $wpdb->prefix . 'tsm_locations',
                ['name' => $name],
                ['%s']
            );
            echo '<div class="notice notice-success"><p>Location added successfully!</p></div>';
        }
    }
    
    // Handle edit form submission
    if (isset($_POST['edit_location'])) {
        check_admin_referer('tsm_edit_location');
        $location_id = intval($_POST['location_id']);
        $name = sanitize_text_field($_POST['location_name']);
        
        if (!empty($name) && $location_id) {
            $wpdb->update(
                $wpdb->prefix . 'tsm_locations',
                ['name' => $name],
                ['id' => $location_id],
                ['%s'],
                ['%d']
            );
            echo '<div class="notice notice-success"><p>Location updated successfully!</p></div>';
        }
    }
    
    // Handle delete
    if (isset($_GET['delete_location'])) {
        check_admin_referer('tsm_delete_location');
        $wpdb->delete(
            $wpdb->prefix . 'tsm_locations',
            ['id' => intval($_GET['delete_location'])],
            ['%d']
        );
        echo '<div class="notice notice-success"><p>Location deleted successfully!</p></div>';
    }
    
    // Get all locations
    $locations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tsm_locations ORDER BY name");
    
    // Handle edit form display
    $edit_location = null;
    if (isset($_GET['edit_location'])) {
        check_admin_referer('tsm_edit_location_nonce');
        $edit_id = intval($_GET['edit_location']);
        $edit_location = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tsm_locations WHERE id = %d", $edit_id));
    }
    
    ?>
    <div class="wrap">
        <h1>Locations</h1>
        
        <?php if ($edit_location): ?>
            <form method="post" style="margin-bottom: 20px;">
                <?php wp_nonce_field('tsm_edit_location'); ?>
                <h2>Edit Location</h2>
                <input type="hidden" name="location_id" value="<?php echo esc_attr($edit_location->id); ?>">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="location_name">Location Name</label></th>
                        <td>
                            <input type="text" name="location_name" id="location_name" value="<?php echo esc_attr($edit_location->name); ?>" required>
                        </td>
                    </tr>
                </table>
                <input type="submit" name="edit_location" class="button button-primary" value="Update Location">
                <a href="<?php echo esc_url(admin_url('admin.php?page=tsm-locations')); ?>" class="button button-secondary">Cancel</a>
            </form>
        <?php else: ?>
            <form method="post" style="margin-bottom: 20px;">
                <?php wp_nonce_field('tsm_add_location'); ?>
                <h2>Add New Location</h2>
                <input type="text" name="location_name" required placeholder="Location Name">
                <input type="submit" name="add_location" class="button button-primary" value="Add Location">
            </form>
        <?php endif; ?>
        
        <h2>Existing Locations</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($locations)): ?>
                    <tr>
                        <td colspan="3">No locations found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($locations as $location): ?>
                        <tr>
                            <td><?php echo esc_html($location->id); ?></td>
                            <td><?php echo esc_html($location->name); ?></td>
                            <td>
                                <a href="<?php echo wp_nonce_url(
                                    admin_url('admin.php?page=tsm-locations&edit_location=' . $location->id),
                                    'tsm_edit_location_nonce'
                                ); ?>" class="button button-secondary">Edit</a>
                                <a href="<?php echo wp_nonce_url(
                                    admin_url('admin.php?page=tsm-locations&delete_location=' . $location->id),
                                    'tsm_delete_location'
                                ); ?>" class="button button-secondary" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function tsm_featured_tours_shortcode($atts) {
    // Enqueue Slick Slider dependencies
    wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css', [], '1.8.1');
    wp_enqueue_style('slick-theme-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css', [], '1.8.1');
    wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', ['jquery'], '1.8.1', true);

    // Get featured tours
    $tours = get_posts([
        'post_type' => 'tsm_tour',
        'numberposts' => -1,
        'meta_query' => [
            [
                'key' => '_tsm_featured',
                'value' => '1',
                'compare' => '='
            ]
        ]
    ]);

    if (empty($tours)) {
        return '<div class="tsm-no-featured-tours">No featured tours available.</div>';
    }

    ob_start(); // Start output buffering
    ?>
    <div class="tsm-featured-tours">
        <div class="tsm-featured-tours-carousel">
            <?php foreach ($tours as $tour): 
                $price = get_post_meta($tour->ID, '_tsm_price_per_person', true);
                $days = get_post_meta($tour->ID, '_tsm_days', true);
                $nights = get_post_meta($tour->ID, '_tsm_nights', true);
                $location = get_post_meta($tour->ID, '_tsm_location', true);
                $places = get_post_meta($tour->ID, '_tsm_places_covered', true);
                $tour_type = get_post_meta($tour->ID, '_tsm_tour_type', true);
                $max_people = get_post_meta($tour->ID, '_tsm_max_people', true);
            ?>
            <div class="tsm-tour-card">
                <?php if (has_post_thumbnail($tour->ID)): ?>
                    <div class="tsm-tour-image">
                        <?php echo get_the_post_thumbnail($tour->ID, 'large'); ?>
                    </div>
                <?php endif; ?>
                
                <div class="tsm-tour-content">
                    <h3 class="tsm-tour-title"><?php echo esc_html($tour->post_title); ?></h3>
                    
                    <div class="tsm-tour-meta">
                        <?php if ($location): ?>
                            <span class="tsm-tour-location">
                                <i class="fas fa-map-marker-alt"></i> <?php echo esc_html($location); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($tour_type): ?>
                            <span class="tsm-tour-type">
                                <i class="fas fa-tag"></i> <?php echo esc_html($tour_type); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tsm-tour-details">
                        <?php if ($days && $nights): ?>
                            <div class="tsm-tour-duration">
                                <i class="fas fa-calendar-alt"></i>
                                <span><?php echo esc_html($days); ?> Days / <?php echo esc_html($nights); ?> Nights</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($price): ?>
                            <div class="tsm-tour-price">
                                <i class="fas fa-rupee-sign"></i>
                                <span>₹<?php echo esc_html(number_format($price, 2)); ?> per person</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($max_people): ?>
                            <div class="tsm-tour-capacity">
                                <i class="fas fa-users"></i>
                                <span>Max <?php echo esc_html($max_people); ?> people</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($places): ?>
                        <div class="tsm-tour-places">
                            <h4>Places Covered:</h4>
                            <p><?php echo esc_html($places); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="tsm-tour-actions">
                        <a href="<?php echo esc_url(get_permalink($tour->ID)); ?>" class="tsm-btn tsm-view-details">View Details</a>
                        <a href="<?php echo esc_url(get_option('tsm_contact_url', '#')); ?>" class="tsm-btn tsm-contact-btn">Contact Us</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="tsm-carousel-nav">
            <button class="tsm-carousel-prev"><i class="fas fa-chevron-left"></i></button>
            <button class="tsm-carousel-next"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        const $carousel = $('.tsm-featured-tours-carousel');
        
        // Initialize Slick slider
        $carousel.on('init', function(event, slick) {
            console.log('Slick initialized');
        }).slick({
            dots: true,
            infinite: true,
            speed: 300,
            slidesToShow: 3,
            slidesToScroll: 1,
            arrows: true,
            prevArrow: $('.tsm-carousel-prev'),
            nextArrow: $('.tsm-carousel-next'),
            adaptiveHeight: true,
            responsive: [
                {
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 600,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1
                    }
                }
            ]
        });

        // Reinitialize on window resize to handle dynamic content
        $(window).on('resize', function() {
            if ($carousel.hasClass('slick-initialized')) {
                $carousel.slick('unslick');
            }
            $carousel.slick({
                dots: true,
                infinite: true,
                speed: 300,
                slidesToShow: 3,
                slidesToScroll: 1,
                arrows: true,
                prevArrow: $('.tsm-carousel-prev'),
                nextArrow: $('.tsm-carousel-next'),
                adaptiveHeight: true,
                responsive: [
                    {
                        breakpoint: 1024,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: 600,
                        settings: {
                            slidesToShow: 1,
                            slidesToScroll: 1
                        }
                    }
                ]
            });
        });
    });
    </script>
    
    <style>
    .tsm-featured-tours {
        position: relative;
        padding: 20px 0;
        max-width: 1200px;
        margin: 0 auto;
        overflow: hidden;
    }
    
    .tsm-featured-tours-carousel {
        margin: 0;
        padding: 0;
    }
    
    .tsm-tour-card {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        margin: 0 15px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: inline-block; /* Fallback for non-Slick scenarios */
        vertical-align: top;
    }
    
    .tsm-tour-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.15);
    }
    
    .tsm-tour-image {
        height: 200px;
        overflow: hidden;
    }
    
    .tsm-tour-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .tsm-tour-card:hover .tsm-tour-image img {
        transform: scale(1.05);
    }
    
    .tsm-tour-content {
        padding: 20px;
    }
    
    .tsm-tour-title {
        font-size: 2rem !important;
        margin: 0 0 10px;
        color: #333;
    }
    
    .tsm-tour-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 15px;
        font-size: 0.9rem;
        color: #666;
    }
    
    .tsm-tour-details {
        margin: 15px 0;
    }
    
    .tsm-tour-details div {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }
    
    .tsm-tour-details i {
        margin-right: 8px;
        color: #0073aa;
        width: 20px;
        text-align: center;
    }
    
    .tsm-tour-places {
        margin: 15px 0;
    }
    
    .tsm-tour-places h4 {
        font-size: 1.1rem;
        margin: 0 0 8px;
        color: #444;
    }
    
    .tsm-tour-places p {
        margin: 0;
        font-size: 0.95rem;
        color: #666;
    }
    
    .tsm-tour-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    
    .tsm-btn {
        display: inline-block;
        padding: 10px 20px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        flex: 1;
        text-align: center;
     
    }

    .tsm-btn:hover {
        color:white
    }
    
    .tsm-view-details {
        background-color: #0073aa;
       
    }
    
    .tsm-view-details:hover {
        background-color: #005f8c;
        color: white;
    }
    
    .tsm-contact-btn {
        background-color: #28a745;
        color: white;
    }
    
    .tsm-contact-btn:hover {
        background-color: #218838;
        color: white;
    }
    
    .tsm-carousel-nav {
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        transform: translateY(-50%);
        display: flex;
        justify-content: space-between;
        pointer-events: none;
        z-index: 10;
    }
    
    .tsm-carousel-prev,
    .tsm-carousel-next {
        
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        pointer-events: all;
        padding:10px !important;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
    }
    
    .tsm-carousel-prev:hover,
    .tsm-carousel-next:hover {
        background: #fff;
        box-shadow: 0 3px 8px rgba(0,0,0,0.3);
    }
    
    .tsm-carousel-prev {
        margin-left: 1px;
    }
    
    .tsm-carousel-next {
        margin-right: 1px;
    }
    
    /* Ensure Slick slider compatibility */
    .tsm-featured-tours-carousel .slick-track {
        display: flex !important;
    }
    
    .tsm-featured-tours-carousel .slick-slide {
        height: auto;
        margin: 15px !important;
        display: flex;
        justify-content: center;
    }
    .tsm-featured-tours-carousel .slick-slide{
         margin: 15px !important;

    }
    
    .tsm-featured-tours-carousel .slick-list {
        overflow: hidden;
    }
    
    .tsm-featured-tours-carousel .slick-dots {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        padding: 0;
        list-style: none;
    }
    
    .tsm-featured-tours-carousel .slick-dots li {
        margin: 0 5px;
    }
    
    .tsm-featured-tours-carousel .slick-dots li button {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #ccc;
        border: none;
        cursor: pointer;
        text-indent: -9999px;
    }
    
    .tsm-featured-tours-carousel .slick-dots li.slick-active button {
        background: #0073aa;
    }
    
    @media (max-width: 768px) {
        .tsm-tour-content{
            padding:30px !important;
        }
        
        .tsm-carousel-prev {
            margin-left: 1px;
        }
        
        .tsm-carousel-next {
            margin-right: 1px;
        }
        
        .tsm-tour-card {
            margin: 0 10px;
        }
        
        .tsm-tour-image {
            height: 250px;
        }
        
        .tsm-tour-title {
            font-size: 2rem !important;
        }
    }
    
    @media (max-width: 600px) {
        .tsm-tour-content{
            padding: 30px !important;
        }
        .tsm-tour-image {
            height: 250px;
        }
        
        .tsm-tour-title {
            font-size: 2rem !important;
        }
        
        .tsm-tour-content {
            padding: 15px;
        }
        
        .tsm-tour-actions {
            flex-direction: column;
        }
        
        .tsm-btn {
            width: 100%;
        }
    }
    </style>
    <?php
    
    return ob_get_clean(); // Return the buffered content
}
add_shortcode('featured_tours_carousel', 'tsm_featured_tours_shortcode');
    // === Vehicles Management ===
function tsm_vehicles_page() {
    // Handle add form submission
    if (isset($_POST['add_vehicle'])) {
        check_admin_referer('tsm_add_vehicle');
        
        $post_data = [
            'post_title'   => sanitize_text_field($_POST['vehicle_name']),
            'post_type'    => 'tsm_vehicle',
            'post_status'  => 'publish',
        ];
        
        $vehicle_id = wp_insert_post($post_data);
        
        if ($vehicle_id) {
            update_post_meta($vehicle_id, '_tsm_vehicle_type', sanitize_text_field($_POST['vehicle_type']));
            update_post_meta($vehicle_id, '_tsm_seats', intval($_POST['seats']));
            update_post_meta($vehicle_id, '_tsm_fuel_type', sanitize_text_field($_POST['fuel_type']));
            update_post_meta($vehicle_id, '_tsm_min_booking_hours', intval($_POST['min_booking_hours']));
            if ($_POST['vehicle_type'] !== 'Boat') {
                update_post_meta($vehicle_id, '_tsm_ac_status', sanitize_text_field($_POST['ac_status']));
            } else {
                delete_post_meta($vehicle_id, '_tsm_ac_status');
            }
            
            // Handle image upload
            if (!empty($_FILES['vehicle_image']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                
                $attachment_id = media_handle_upload('vehicle_image', $vehicle_id);
                if (!is_wp_error($attachment_id)) {
                    set_post_thumbnail($vehicle_id, $attachment_id);
                }
            }
            
            echo '<div class="notice notice-success"><p>Vehicle added successfully!</p></div>';
        }
    }
    
    // Handle edit form submission
    if (isset($_POST['edit_vehicle'])) {
        check_admin_referer('tsm_edit_vehicle');
        
        $vehicle_id = intval($_POST['vehicle_id']);
        $post_data = [
            'ID'           => $vehicle_id,
            'post_title'   => sanitize_text_field($_POST['vehicle_name']),
        ];
        
        wp_update_post($post_data);
        
        update_post_meta($vehicle_id, '_tsm_vehicle_type', sanitize_text_field($_POST['vehicle_type']));
        update_post_meta($vehicle_id, '_tsm_seats', intval($_POST['seats']));
        update_post_meta($vehicle_id, '_tsm_fuel_type', sanitize_text_field($_POST['fuel_type']));
        update_post_meta($vehicle_id, '_tsm_min_booking_hours', intval($_POST['min_booking_hours']));
        if ($_POST['vehicle_type'] !== 'Boat') {
            update_post_meta($vehicle_id, '_tsm_ac_status', sanitize_text_field($_POST['ac_status']));
        } else {
            delete_post_meta($vehicle_id, '_tsm_ac_status');
        }
        
        // Handle image upload
        if (!empty($_FILES['vehicle_image']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            $attachment_id = media_handle_upload('vehicle_image', $vehicle_id);
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($vehicle_id, $attachment_id);
            }
        }
        
        echo '<div class="notice notice-success"><p>Vehicle updated successfully!</p></div>';
    }
    
    // Handle delete
    if (isset($_GET['delete_vehicle'])) {
        check_admin_referer('tsm_delete_vehicle');
        wp_delete_post(intval($_GET['delete_vehicle']), true);
        echo '<div class="notice notice-success"><p>Vehicle deleted successfully!</p></div>';
    }
    
    // Get all vehicles
    $vehicles = get_posts([
        'post_type' => 'tsm_vehicle',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    // Handle edit form display
    $edit_vehicle = null;
    if (isset($_GET['edit_vehicle'])) {
        check_admin_referer('tsm_edit_vehicle_nonce');
        $edit_vehicle = get_post(intval($_GET['edit_vehicle']));
    }
    
    ?>
    <div class="wrap">
        <h1>Vehicles</h1>
        
        <?php if ($edit_vehicle): ?>
            <form method="post" enctype="multipart/form-data" style="margin-bottom: 20px;">
                <?php wp_nonce_field('tsm_edit_vehicle'); ?>
                <h2>Edit Vehicle</h2>
                <input type="hidden" name="vehicle_id" value="<?php echo esc_attr($edit_vehicle->ID); ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="vehicle_type">Vehicle Type</label></th>
                        <td>
                            <select name="vehicle_type" id="vehicle_type" required>
                                <option value="">Select Type</option>
                                <option value="Car" <?php selected(get_post_meta($edit_vehicle->ID, '_tsm_vehicle_type', true), 'Car'); ?>>Car</option>
                                <option value="Bus" <?php selected(get_post_meta($edit_vehicle->ID, '_tsm_vehicle_type', true), 'Bus'); ?>>Bus</option>
                                <option value="Boat" <?php selected(get_post_meta($edit_vehicle->ID, '_tsm_vehicle_type', true), 'Boat'); ?>>Boat</option>
                                <option value="Tempo" <?php selected(get_post_meta($edit_vehicle->ID, '_tsm_vehicle_type', true), 'Tempo'); ?>>Tempo</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vehicle_name">Vehicle Name</label></th>
                        <td><input type="text" name="vehicle_name" id="vehicle_name" value="<?php echo esc_attr($edit_vehicle->post_title); ?>" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vehicle_image">Vehicle Image</label></th>
                        <td>
                            <?php if (has_post_thumbnail($edit_vehicle->ID)): ?>
                                <?php echo get_the_post_thumbnail($edit_vehicle->ID, [100, 100]); ?>
                                <p>Upload a new image to replace the current one.</p>
                            <?php endif; ?>
                            <input type="file" name="vehicle_image" id="vehicle_image" accept="image/*">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="seats">Number of Seats</label></th>
                        <td><input type="number" name="seats" id="seats" value="<?php echo esc_attr(get_post_meta($edit_vehicle->ID, '_tsm_seats', true)); ?>" required min="1"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="fuel_type">Fuel Type</label></th>
                        <td>
                            <select name="fuel_type" id="fuel_type" required>
                                <option value="">Select Fuel Type</option>
                                <option value="Petrol" <?php selected(get_post_meta($edit_vehicle->ID, '_tsm_fuel_type', true), 'Petrol'); ?>>Petrol</option>
                                <option value="Diesel" <?php selected(get_post_meta($edit_vehicle->ID, '_tsm_fuel_type', true), 'Diesel'); ?>>Diesel</option>
                                <option value="Electric" <?php selected(get_post_meta($edit_vehicle->ID, '_tsm_fuel_type', true), 'Electric'); ?>>Electric</option>
                                <option value="Hybrid" <?php selected(get_post_meta($edit_vehicle->ID, '_tsm_fuel_type', true), 'Hybrid'); ?>>Hybrid</option>
                                <option value="CNG" <?php selected(get_post_meta($edit_vehicle->ID, '_tsm_fuel_type', true), 'CNG'); ?>>CNG</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="min_booking_hours">Minimum Booking Hours</label></th>
                        <td>
                            <input type="number" name="min_booking_hours" id="min_booking_hours" value="<?php echo esc_attr(get_post_meta($edit_vehicle->ID, '_tsm_min_booking_hours', true)); ?>" min="0">
                            <p class="description">Enter the minimum number of hours for booking this vehicle.</p>
                        </td>
                    </tr>
                    <tr class="ac-status-row" <?php echo get_post_meta($edit_vehicle->ID, '_tsm_vehicle_type', true) === 'Boat' ? 'style="display:none;"' : ''; ?>>
                        <th scope="row"><label for="ac_status">AC/Non-AC</label></th>
                        <td>
                            <select name="ac_status" id="ac_status">
                                <option value="">Select AC Status</option>
                                <option value="AC" <?php selected(get_post_meta($edit_vehicle->ID, '_tsm_ac_status', true), 'AC'); ?>>AC</option>
                                <option value="Non-AC" <?php selected(get_post_meta($edit_vehicle->ID, '_tsm_ac_status', true), 'Non-AC'); ?>>Non-AC</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <input type="submit" name="edit_vehicle" class="button button-primary" value="Update Vehicle">
                <a href="<?php echo esc_url(admin_url('admin.php?page=tsm-vehicles')); ?>" class="button button-secondary">Cancel</a>
            </form>
            
            <script>
            jQuery(document).ready(function($) {
                $('#vehicle_type').on('change', function() {
                    if ($(this).val() === 'Boat') {
                        $('.ac-status-row').hide();
                        $('#ac_status').val('');
                    } else {
                        $('.ac-status-row').show();
                    }
                });
            });
            </script>
        <?php else: ?>
            <form method="post" enctype="multipart/form-data" style="margin-bottom: 20px;">
                <?php wp_nonce_field('tsm_add_vehicle'); ?>
                <h2>Add New Vehicle</h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="vehicle_type">Vehicle Type</label></th>
                        <td>
                            <select name="vehicle_type" id="vehicle_type" required>
                                <option value="">Select Type</option>
                                <option value="Car">Car</option>
                                <option value="Bus">Bus</option>
                                <option value="Boat">Boat</option>
                                <option value="Tempo">Tempo</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vehicle_name">Vehicle Name</label></th>
                        <td><input type="text" name="vehicle_name" id="vehicle_name" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vehicle_image">Vehicle Image</label></th>
                        <td><input type="file" name="vehicle_image" id="vehicle_image" accept="image/*"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="seats">Number of Seats</label></th>
                        <td><input type="number" name="seats" id="seats" required min="1"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="fuel_type">Fuel Type</label></th>
                        <td>
                            <select name="fuel_type" id="fuel_type" required>
                                <option value="">Select Fuel Type</option>
                                <option value="Petrol">Petrol</option>
                                <option value="Diesel">Diesel</option>
                                <option value="Electric">Electric</option>
                                <option value="Hybrid">Hybrid</option>
                                <option value="CNG">CNG</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="min_booking_hours">Minimum Booking Hours</label></th>
                        <td>
                            <input type="number" name="min_booking_hours" id="min_booking_hours" min="0" value="0">
                            <p class="description">Enter the minimum number of hours for booking this vehicle.</p>
                        </td>
                    </tr>
                    <tr class="ac-status-row">
                        <th scope="row"><label for="ac_status">AC/Non-AC</label></th>
                        <td>
                            <select name="ac_status" id="ac_status" required>
                                <option value="">Select AC Status</option>
                                <option value="AC">AC</option>
                                <option value="Non-AC">Non-AC</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <input type="submit" name="add_vehicle" class="button button-primary" value="Add Vehicle">
            </form>
            
            <script>
            jQuery(document).ready(function($) {
                $('#vehicle_type').on('change', function() {
                    if ($(this).val() === 'Boat') {
                        $('.ac-status-row').hide();
                        $('#ac_status').val('').prop('required', false);
                    } else {
                        $('.ac-status-row').show();
                        $('#ac_status').prop('required', true);
                    }
                });
            });
            </script>
        <?php endif; ?>
        
        <h2>Existing Vehicles</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Seats</th>
                    <th>Fuel Type</th>
                    <th>Min Booking Hours</th>
                    <th>AC Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vehicles)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center;">No vehicles found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <tr>
                            <td><?php echo esc_html($vehicle->ID); ?></td>
                            <td>
                                <?php if (has_post_thumbnail($vehicle->ID)): ?>
                                    <?php echo get_the_post_thumbnail($vehicle->ID, [50, 50]); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($vehicle->post_title); ?></td>
                            <td><?php echo esc_html(get_post_meta($vehicle->ID, '_tsm_vehicle_type', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($vehicle->ID, '_tsm_seats', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($vehicle->ID, '_tsm_fuel_type', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($vehicle->ID, '_tsm_min_booking_hours', true)); ?></td>
                            <td><?php echo get_post_meta($vehicle->ID, '_tsm_vehicle_type', true) === 'Boat' ? 'N/A' : esc_html(get_post_meta($vehicle->ID, '_tsm_ac_status', true)); ?></td>
                            <td>
                                <a href="<?php echo wp_nonce_url(
                                    admin_url('admin.php?page=tsm-vehicles&edit_vehicle=' . $vehicle->ID),
                                    'tsm_edit_vehicle_nonce'
                                ); ?>" class="button button-secondary">Edit</a>
                                <a href="<?php echo wp_nonce_url(
                                    admin_url('admin.php?page=tsm-vehicles&delete_vehicle=' . $vehicle->ID),
                                    'tsm_delete_vehicle'
                                ); ?>" class="button button-secondary" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// === Assign Locations ===
function tsm_assign_locations_page() {
    global $wpdb;
    
    // Handle add form submission
    if (isset($_POST['assign_location'])) {
        check_admin_referer('tsm_assign_location');
        
        // Validate inputs
        $vehicle_id = !empty($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : 0;
        $location_id = !empty($_POST['location_id']) ? intval($_POST['location_id']) : 0;
        $price = isset($_POST['price']) && is_numeric($_POST['price']) ? floatval($_POST['price']) : null;
        $extra_km_price = isset($_POST['extra_km_price']) && is_numeric($_POST['extra_km_price']) ? floatval($_POST['extra_km_price']) : null;
        $extra_hour_price = isset($_POST['extra_hour_price']) && is_numeric($_POST['extra_hour_price']) ? floatval($_POST['extra_hour_price']) : null;
        $max_range = isset($_POST['max_range']) && is_numeric($_POST['max_range']) ? floatval($_POST['max_range']) : null;
        $available = isset($_POST['available']) && in_array($_POST['available'], ['0', '1']) ? intval($_POST['available']) : 1;
        $rental_type = isset($_POST['rental_type']) && in_array($_POST['rental_type'], ['Round Trip', 'Single Trip', 'Hourly Rental', 'Daily Rental']) ? sanitize_text_field($_POST['rental_type']) : 'Round Trip';


        $errors = [];
        if ($vehicle_id <= 0) {
            $errors[] = 'Please select a valid vehicle.';
        }
        if ($location_id <= 0) {
            $errors[] = 'Please select a valid location.';
        }
        if (is_null($price) || $price < 0) {
            $errors[] = 'Please enter a valid price (non-negative number).';
        }
        // Check if vehicle is a boat
        $vehicle_type = get_post_meta($vehicle_id, '_tsm_vehicle_type', true);
        if ($vehicle_type !== 'Boat') {
            if (is_null($extra_km_price) || $extra_km_price < 0) {
                $errors[] = 'Please enter a valid extra KM price (non-negative number).';
            }
            if (is_null($extra_hour_price) || $extra_hour_price < 0) {
                $errors[] = 'Please enter a valid extra hour price (non-negative number).';
            }
        } else {
            // Set default values for boats
            $extra_km_price = 0;
            $extra_hour_price = 0;
            // Clear fuel type for boats
            update_post_meta($vehicle_id, '_tsm_fuel_type', '');
        }
        // Check if vehicle is a car and max_range is provided
        if ($vehicle_type === 'Car' && (is_null($max_range) || $max_range < 0)) {
            $errors[] = 'Please enter a valid max range (non-negative number) for cars.';
        }
        
        // Check for duplicate assignment
        if (empty($errors)) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}tsm_vehicle_assignments WHERE vehicle_id = %d AND location_id = %d",
                $vehicle_id,
                $location_id,
                
            ));
            if ($existing) {
                $errors[] = 'This vehicle is already assigned to this location.';
            }
        }
        
        if (empty($errors)) {
            $data = [
                'vehicle_id' => $vehicle_id,
                'location_id' => $location_id,
                'price' => $price,
                'extra_km_price' => $extra_km_price,
                'extra_hour_price' => $extra_hour_price,
                'available' => $available,
                'rental_type' => $rental_type
            ];
            $format = ['%d', '%d', '%f', '%f', '%f', '%d','%s'];
            
            // Only include max_range for cars
            if ($vehicle_type === 'Car') {
                $data['max_range'] = $max_range;
                $format[] = '%f';
            }
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'tsm_vehicle_assignments',
                $data,
                $format
            );
            
            if ($result === false) {
                echo '<div class="notice notice-error"><p>Error assigning location: ' . esc_html($wpdb->last_error) . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>Location assigned successfully!</p></div>';
            }
        } else {
            foreach ($errors as $error) {
                echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
            }
        }
    }
    
    // Handle edit form submission
    if (isset($_POST['edit_assignment'])) {
        check_admin_referer('tsm_edit_assignment');
        
        $assignment_id = !empty($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
        $price = isset($_POST['price']) && is_numeric($_POST['price']) ? floatval($_POST['price']) : null;
        $extra_km_price = isset($_POST['extra_km_price']) && is_numeric($_POST['extra_km_price']) ? floatval($_POST['extra_km_price']) : null;
        $extra_hour_price = isset($_POST['extra_hour_price']) && is_numeric($_POST['extra_hour_price']) ? floatval($_POST['extra_hour_price']) : null;
        $max_range = isset($_POST['max_range']) && is_numeric($_POST['max_range']) ? floatval($_POST['max_range']) : null;
        $available = isset($_POST['available']) && in_array($_POST['available'], ['0', '1']) ? intval($_POST['available']) : 1;
        $rental_type = isset($_POST['rental_type']) && in_array($_POST['rental_type'], ['Round Trip', 'Single Trip', 'Hourly Rental', 'Daily Rental']) ? sanitize_text_field($_POST['rental_type']) : 'Round Trip';
        
        $errors = [];
        if ($assignment_id <= 0) {
            $errors[] = 'Invalid assignment ID.';
        }
        if (is_null($price) || $price < 0) {
            $errors[] = 'Please enter a valid price (non-negative number).';
        }
        // Check if vehicle is a boat
        $assignment = $wpdb->get_row($wpdb->prepare("SELECT vehicle_id FROM {$wpdb->prefix}tsm_vehicle_assignments WHERE id = %d", $assignment_id));
        $vehicle_type = get_post_meta($assignment->vehicle_id, '_tsm_vehicle_type', true);
        if ($vehicle_type !== 'Boat') {
            if (is_null($extra_km_price) || $extra_km_price < 0) {
                $errors[] = 'Please enter a valid extra KM price (non-negative number).';
            }
            if (is_null($extra_hour_price) || $extra_hour_price < 0) {
                $errors[] = 'Please enter a valid extra hour price (non-negative number).';
            }
        } else {
            // Set default values for boats
            $extra_km_price = 0;
            $extra_hour_price = 0;
            // Clear fuel type for boats
            update_post_meta($assignment->vehicle_id, '_tsm_fuel_type', '');
        }
        // Check if vehicle is a car and max_range is provided
        if ($vehicle_type === 'Car' && (is_null($max_range) || $max_range < 0)) {
            $errors[] = 'Please enter a valid max range (non-negative number) for cars.';
        }
        
        if (empty($errors)) {
            $data = [
                'price' => $price,
                'extra_km_price' => $extra_km_price,
                'extra_hour_price' => $extra_hour_price,
                'available' => $available,
                'rental_type' => $rental_type
            ];
            $format = ['%f', '%f', '%f', '%d', '%s'];
            
            // Only include max_range for cars
            if ($vehicle_type === 'Car') {
                $data['max_range'] = $max_range;
                $format[] = '%f';
            }
            
            $result = $wpdb->update(
                $wpdb->prefix . 'tsm_vehicle_assignments',
                $data,
                ['id' => $assignment_id],
                $format,
                ['%d']
            );
            
            if ($result === false) {
                echo '<div class="notice notice-error"><p>Error updating assignment: ' . esc_html($wpdb->last_error) . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>Assignment updated successfully!</p></div>';
            }
        } else {
            foreach ($errors as $error) {
                echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
            }
        }
    }
    
    // Handle delete
    if (isset($_GET['delete_assignment'])) {
        check_admin_referer('tsm_delete_assignment');
        $result = $wpdb->delete(
            $wpdb->prefix . 'tsm_vehicle_assignments',
            ['id' => intval($_GET['delete_assignment'])],
            ['%d']
        );
        if ($result === false) {
            echo '<div class="notice notice-error"><p>Error deleting assignment: ' . esc_html($wpdb->last_error) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>Assignment deleted successfully!</p></div>';
        }
    }
    
    // Get all assignments with vehicle and location names
    $assignments = $wpdb->get_results("
        SELECT a.*, v.post_title as vehicle_name, l.name as location_name, pm.meta_value as vehicle_type, pm2.meta_value as fuel_type
        FROM {$wpdb->prefix}tsm_vehicle_assignments a
        LEFT JOIN {$wpdb->posts} v ON a.vehicle_id = v.ID
        LEFT JOIN {$wpdb->prefix}tsm_locations l ON a.location_id = l.id
        LEFT JOIN {$wpdb->postmeta} pm ON a.vehicle_id = pm.post_id AND pm.meta_key = '_tsm_vehicle_type'
        LEFT JOIN {$wpdb->postmeta} pm2 ON a.vehicle_id = pm2.post_id AND pm2.meta_key = '_tsm_fuel_type'
        ORDER BY l.name, v.post_title
    ");
    
    // Get vehicles and locations for dropdowns
    $vehicles = get_posts([
        'post_type' => 'tsm_vehicle',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
            $filter_vehicle = isset($_GET['filter_vehicle']) ? intval($_GET['filter_vehicle']) : 0;
$filter_location = isset($_GET['filter_location']) ? intval($_GET['filter_location']) : 0;
$filter_available = isset($_GET['filter_available']) ? ($_GET['filter_available'] === '0' ? 0 : 1) : -1; // -1 means all
$sort_by = isset($_GET['sort_by']) && in_array($_GET['sort_by'], ['id', 'vehicle_name', 'location_name', 'price', 'extra_km_price', 'extra_hour_price', 'max_range', 'available']) ? $_GET['sort_by'] : 'location_name';
$sort_order = isset($_GET['sort_order']) && in_array(strtoupper($_GET['sort_order']), ['ASC', 'DESC']) ? strtoupper($_GET['sort_order']) : 'ASC';

// Build the SQL query with filters and sorting
$sql = "
    SELECT a.*, v.post_title as vehicle_name, l.name as location_name, pm.meta_value as vehicle_type, pm2.meta_value as fuel_type
    FROM {$wpdb->prefix}tsm_vehicle_assignments a
    LEFT JOIN {$wpdb->posts} v ON a.vehicle_id = v.ID
    LEFT JOIN {$wpdb->prefix}tsm_locations l ON a.location_id = l.id
    LEFT JOIN {$wpdb->postmeta} pm ON a.vehicle_id = pm.post_id AND pm.meta_key = '_tsm_vehicle_type'
    LEFT JOIN {$wpdb->postmeta} pm2 ON a.vehicle_id = pm2.post_id AND pm2.meta_key = '_tsm_fuel_type'
";

// Add WHERE clauses for filters
$where_clauses = [];
if ($filter_vehicle > 0) {
    $where_clauses[] = $wpdb->prepare("a.vehicle_id = %d", $filter_vehicle);
}
if ($filter_location > 0) {
    $where_clauses[] = $wpdb->prepare("a.location_id = %d", $filter_location);
}
if ($filter_available !== -1) {
    $where_clauses[] = $wpdb->prepare("a.available = %d", $filter_available);
}
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Add ORDER BY clause
$sort_column = $sort_by === 'vehicle_name' ? 'v.post_title' : ($sort_by === 'location_name' ? 'l.name' : 'a.' . $sort_by);
$sql .= " ORDER BY " . esc_sql($sort_column) . " " . esc_sql($sort_order);

// Execute the query
$assignments = $wpdb->get_results($sql);

// Get vehicles and locations for filter dropdowns
$vehicles = get_posts([
    'post_type' => 'tsm_vehicle',
    'numberposts' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
]);
$locations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tsm_locations ORDER BY name");

    
    $locations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tsm_locations ORDER BY name");
    
    // Handle edit form display
    $edit_assignment = null;
    if (isset($_GET['edit_assignment'])) {
        check_admin_referer('tsm_edit_assignment_nonce');
        $edit_id = intval($_GET['edit_assignment']);
        $edit_assignment = $wpdb->get_row($wpdb->prepare("
            SELECT a.*, v.post_title as vehicle_name, l.name as location_name, pm.meta_value as vehicle_type, pm2.meta_value as fuel_type
            FROM {$wpdb->prefix}tsm_vehicle_assignments a
            LEFT JOIN {$wpdb->posts} v ON a.vehicle_id = v.ID
            LEFT JOIN {$wpdb->prefix}tsm_locations l ON a.location_id = l.id
            LEFT JOIN {$wpdb->postmeta} pm ON a.vehicle_id = pm.post_id AND pm.meta_key = '_tsm_vehicle_type'
            LEFT JOIN {$wpdb->postmeta} pm2 ON a.vehicle_id = pm2.post_id AND pm2.meta_key = '_tsm_fuel_type'
            WHERE a.id = %d
        ", $edit_id));
    }
    
    ?>
    <div class="wrap">
        <h1>Assign Locations to Vehicles</h1>
        
        <?php if ($edit_assignment): ?>
            <form method="post" style="margin-bottom: 20px;">
                <?php wp_nonce_field('tsm_edit_assignment'); ?>
                <h2>Edit Assignment</h2>
                <input type="hidden" name="assignment_id" value="<?php echo esc_attr($edit_assignment->id); ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label>Vehicle</label></th>
                        <td><?php echo esc_html($edit_assignment->vehicle_name ?: 'Vehicle ID ' . $edit_assignment->vehicle_id); ?> (ID: <?php echo esc_html($edit_assignment->vehicle_id); ?>)</td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Location</label></th>
                        <td><?php echo esc_html($edit_assignment->location_name ?: 'Location ID ' . $edit_assignment->location_id); ?> (ID: <?php echo esc_html($edit_assignment->location_id); ?>)</td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="price">Price</label></th>
                        <td><input type="number" name="price" id="price" value="<?php echo esc_attr($edit_assignment->price); ?>" required min="0" step="0.01"></td>
                    </tr>
                    <?php if ($edit_assignment->vehicle_type !== 'Boat'): ?>
                    <tr class="extra-km-price-row">
                        <th scope="row"><label for="extra_km_price">Extra KM Price</label></th>
                        <td><input type="number" name="extra_km_price" id="extra_km_price" value="<?php echo esc_attr($edit_assignment->extra_km_price); ?>" required min="0" step="0.01"></td>
                    </tr>
                    <tr class="extra-hour-price-row">
                        <th scope="row"><label for="extra_hour_price">Extra Hour Price</label></th>
                        <td><input type="number" name="extra_hour_price" id="extra_hour_price" value="<?php echo esc_attr($edit_assignment->extra_hour_price); ?>" required min="0" step="0.01"></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($edit_assignment->vehicle_type === 'Car'): ?>
                    <tr class="max-range-row">
                        <th scope="row"><label for="max_range">Max Range (km)</label></th>
                        <td>
                            <input type="number" name="max_range" id="max_range" value="<?php echo esc_attr($edit_assignment->max_range); ?>" required min="0" step="0.01">
                            <p class="description">Enter the maximum range in kilometers for this car.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th scope="row"><label for="available">Available</label></th>
                        <td>
                            <select name="available" id="available" required>
                                <option value="1" <?php selected($edit_assignment->available, 1); ?>>Yes</option>
                                <option value="0" <?php selected($edit_assignment->available, 0); ?>>No</option>
                            </select>
                            <p class="description">Indicate if the vehicle is available at this location.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rental_type">Rental Type</label></th>
                        <td>
                            <select name="rental_type" id="rental_type" required>
                                <option value="Round Trip" <?php selected($edit_assignment->rental_type, 'Round Trip'); ?>>Round Trip</option>
                                <option value="Single Trip" <?php selected($edit_assignment->rental_type, 'Single Trip'); ?>>Single Trip</option>
                                <option value="Hourly Rental" <?php selected($edit_assignment->rental_type, 'Hourly Rental'); ?>>Hourly Rental</option>
                                <option value="Daily Rental" <?php selected($edit_assignment->rental_type, 'Daily Rental'); ?>>Daily Rental</option>
                            </select>
                            <p class="description">Select the rental type for this vehicle at this location.</p>
                        </td>
                    </tr>
                </table>
                
                <input type="submit" name="edit_assignment" class="button button-primary" value="Update Assignment">
                <a href="<?php echo esc_url(admin_url('admin.php?page=tsm-assign-locations')); ?>" class="button button-secondary">Cancel</a>
            </form>
        <?php else: ?>
            <form method="post" style="margin-bottom: 20px;">
                <?php wp_nonce_field('tsm_assign_location'); ?>
                <h2>Assign New Location</h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="vehicle_id">Vehicle</label></th>
                        <td>
                            <select name="vehicle_id" id="vehicle_id" required>
                                <option value="">Select Vehicle</option>
                                <?php if (empty($vehicles)): ?>
                                    <option value="" disabled>No vehicles available</option>
                                <?php else: ?>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <option value="<?php echo esc_attr($vehicle->ID); ?>" data-type="<?php echo esc_attr(get_post_meta($vehicle->ID, '_tsm_vehicle_type', true)); ?>">
                                            <?php echo esc_html($vehicle->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?php if (empty($vehicles)): ?>
                                <p class="description" style="color: red;">Please add vehicles first under the Vehicles menu.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="location_id">Location</label></th>
                        <td>
                            <select name="location_id" id="location_id" required>
                                <option value="">Select Location</option>
                                <?php if (empty($locations)): ?>
                                    <option value="" disabled>No locations available</option>
                                <?php else: ?>
                                    <?php foreach ($locations as $location): ?>
                                        <option value="<?php echo esc_attr($location->id); ?>">
                                            <?php echo esc_html($location->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?php if (empty($locations)): ?>
                                <p class="description" style="color: red;">Please add locations first under the Locations menu.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="price">Price</label></th>
                        <td><input type="number" name="price" id="price" required min="0" step="0.01" value="0.00"></td>
                    </tr>
                    <tr class="extra-km-price-row">
                        <th scope="row"><label for="extra_km_price">Extra KM Price</label></th>
                        <td><input type="number" name="extra_km_price" id="extra_km_price" required min="0" step="0.01" value="0.00"></td>
                    </tr>
                    <tr class="extra-hour-price-row">
                        <th scope="row"><label for="extra_hour_price">Extra Hour Price</label></th>
                        <td><input type="number" name="extra_hour_price" id="extra_hour_price" required min="0" step="0.01" value="0.00"></td>
                    </tr>
                    <tr class="max-range-row" style="display: none;">
                        <th scope="row"><label for="max_range">Max Range (km)</label></th>
                        <td>
                            <input type="number" name="max_range" id="max_range" min="0" step="0.01" value="0.00">
                            <p class="description">Enter the maximum range in kilometers for this car.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="available">Available</label></th>
                        <td>
                            <select name="available" id="available" required>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                            <p class="description">Indicate if the vehicle is available at this location.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rental_type">Rental Type</label></th>
                        <td>
                            <select name="rental_type" id="rental_type" required>
                                <option value="Round Trip">Round Trip</option>
                                <option value="Single Trip">Single Trip</option>
                                <option value="Hourly Rental">Hourly Rental</option>
                                <option value="Daily Rental">Daily Rental</option>
                            </select>
                            <p class="description">Select the rental type for this vehicle at this location.</p>
                        </td>
                    </tr>
                </table>
                
                <input type="submit" name="assign_location" class="button button-primary" value="Assign Location" <?php echo empty($vehicles) || empty($locations) ? 'disabled' : ''; ?>>
            </form>
            
            <script>
            jQuery(document).ready(function($) {
                $('#vehicle_id').on('change', function() {
                    var vehicleType = $(this).find(':selected').data('type');
                    if (vehicleType === 'Car') {
                        $('.max-range-row').show();
                        $('#max_range').prop('required', true);
                    } else {
                        $('.max-range-row').hide();
                        $('#max_range').prop('required', false).val('');
                    }
                    if (vehicleType === 'Boat') {
                        $('.extra-km-price-row').hide();
                        $('.extra-hour-price-row').hide();
                        $('#extra_km_price').prop('required', false).val('0.00');
                        $('#extra_hour_price').prop('required', false).val('0.00');
                    } else {
                        $('.extra-km-price-row').show();
                        $('.extra-hour-price-row').show();
                        $('#extra_km_price').prop('required', true);
                        $('#extra_hour_price').prop('required', true);
                    }
                });
            });
            </script>
        <?php endif; ?>
        
        <h2>Existing Assignments</h2>

<div class="wrap">
    <h1>Assign Locations to Vehicles</h1>
    
    <!-- Existing form code for adding/editing assignments remains unchanged -->
    <?php if ($edit_assignment): ?>
        <!-- Your existing edit form code here -->
    <?php else: ?>
        <!-- Your existing add form code here -->
    <?php endif; ?>
    
    <h2>Existing Assignments</h2>
    
    <!-- Filter and Sort Form -->
    <form method="get" style="margin-bottom: 20px;">
        <input type="hidden" name="page" value="tsm-assign-locations">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="filter_vehicle">Filter by Vehicle</label></th>
                <td>
                    <select name="filter_vehicle" id="filter_vehicle">
                        <option value="0">All Vehicles</option>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?php echo esc_attr($vehicle->ID); ?>" <?php selected($filter_vehicle, $vehicle->ID); ?>>
                                <?php echo esc_html($vehicle->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="filter_location">Filter by Location</label></th>
                <td>
                    <select name="filter_location" id="filter_location">
                        <option value="0">All Locations</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo esc_attr($location->id); ?>" <?php selected($filter_location, $location->id); ?>>
                                <?php echo esc_html($location->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="filter_available">Filter by Availability</label></th>
                <td>
                    <select name="filter_available" id="filter_available">
                        <option value="-1" <?php selected($filter_available, -1); ?>>All</option>
                        <option value="1" <?php selected($filter_available, 1); ?>>Available</option>
                        <option value="0" <?php selected($filter_available, 0); ?>>Not Available</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="sort_by">Sort By</label></th>
                <td>
                    <select name="sort_by" id="sort_by">
                        <option value="id" <?php selected($sort_by, 'id'); ?>>ID</option>
                        <option value="vehicle_name" <?php selected($sort_by, 'vehicle_name'); ?>>Vehicle</option>
                        <option value="location_name" <?php selected($sort_by, 'location_name'); ?>>Location</option>
                        <option value="price" <?php selected($sort_by, 'price'); ?>>Price</option>
                        <option value="extra_km_price" <?php selected($sort_by, 'extra_km_price'); ?>>Extra KM Price</option>
                        <option value="extra_hour_price" <?php selected($sort_by, 'extra_hour_price'); ?>>Extra Hour Price</option>
                        <option value="max_range" <?php selected($sort_by, 'max_range'); ?>>Max Range</option>
                        <option value="available" <?php selected($sort_by, 'available'); ?>>Available</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="sort_order">Sort Order</label></th>
                <td>
                    <select name="sort_order" id="sort_order">
                        <option value="ASC" <?php selected($sort_order, 'ASC'); ?>>Ascending</option>
                        <option value="DESC" <?php selected($sort_order, 'DESC'); ?>>Descending</option>
                    </select>
                </td>
            </tr>
        </table>
        <input type="submit" class="button button-secondary" value="Apply Filters">
        <a href="<?php echo esc_url(admin_url('admin.php?page=tsm-assign-locations')); ?>" class="button button-secondary">Clear Filters</a>
    </form>

    <!-- Assignments Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Vehicle</th>
                <th>Location</th>
                <th>Price</th>
                <th>Extra KM Price</th>
                <th>Extra Hour Price</th>
                <th>Max Range (km)</th>
                <th>Available</th>
                <th>Rental Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($assignments)): ?>
                <tr>
                    <td colspan="9">No assignments found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($assignments as $assignment): ?>
                    <tr>
                        <td><?php echo esc_html($assignment->id); ?></td>
                        <td><?php echo esc_html($assignment->vehicle_name ?: 'Vehicle ID ' . $assignment->vehicle_id); ?></td>
                        <td><?php echo esc_html($assignment->location_name ?: 'Location ID ' . $assignment->location_id); ?></td>
                        <td><?php echo esc_html(number_format($assignment->price, 2)); ?></td>
                        <td><?php echo $assignment->vehicle_type === 'Boat' ? 'N/A' : esc_html(number_format($assignment->extra_km_price, 2)); ?></td>
                        <td><?php echo $assignment->vehicle_type === 'Boat' ? 'N/A' : esc_html(number_format($assignment->extra_hour_price, 2)); ?></td>
                        <td><?php echo $assignment->vehicle_type === 'Car' ? esc_html(number_format($assignment->max_range, 2)) : 'N/A'; ?></td>
                        <td><?php echo esc_html($assignment->rental_type); ?></td>
                        <td><?php echo $assignment->available ? 'Yes' : 'No'; ?></td>
                        <td>
                            <a href="<?php echo wp_nonce_url(
                                admin_url('admin.php?page=tsm-assign-locations&edit_assignment=' . $assignment->id),
                                'tsm_edit_assignment_nonce'
                            ); ?>" class="button button-secondary">Edit</a>
                            <a href="<?php echo wp_nonce_url(
                                admin_url('admin.php?page=tsm-assign-locations&delete_assignment=' . $assignment->id),
                                'tsm_delete_assignment'
                            ); ?>" class="button button-secondary" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
    <?php
}
// === Register Custom Post Types ===
function tsm_register_custom_post_types() {
    // Vehicle post type
    register_post_type('tsm_vehicle', [
        'labels' => [
            'name' => 'Vehicles',
            'singular_name' => 'Vehicle'
        ],
        'public' => false,
        'show_ui' => false,
        'supports' => ['title', 'thumbnail'],
        'show_in_rest' => true,
    ]);
    
    // Tour package post type
    register_post_type('tsm_tour', [
        'labels' => [
            'name' => 'Tour Packages',
            'singular_name' => 'Tour Package'
        ],
        'public' => true, // Changed to true for single page access
        'show_ui' => false,
        'supports' => ['title', 'editor', 'thumbnail'],
        'show_in_rest' => true,
        'has_archive' => false,
        'rewrite' => ['slug' => 'tour'], // Custom slug for permalinks
    ]);
}
add_action('init', 'tsm_register_custom_post_types');

// === Shortcode for Vehicles ===
// === Shortcode for Vehicles ===
function tsm_display_vehicles_shortcode($atts) {
    global $wpdb;
    
    $atts = shortcode_atts([
        'location' => '',
        'type' => ''
    ], $atts, 'show_vehicles');
    
    // Get button URLs from options
    $book_now_url = get_option('tsm_book_now_url', '#');
    $contact_url = get_option('tsm_contact_url', '#');
    $services_url = get_permalink(get_page_by_path('services')) ?: '#';
    
    // Get all locations for the filter dropdown
    $all_locations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tsm_locations ORDER BY name");
    
    // Prepare the base query
    $query = "
    SELECT v.*, a.price, a.extra_km_price, a.extra_hour_price, a.max_range, a.available, a.rental_type, l.name as location_name
    FROM {$wpdb->posts} v
    LEFT JOIN {$wpdb->prefix}tsm_vehicle_assignments a ON v.ID = a.vehicle_id
    LEFT JOIN {$wpdb->prefix}tsm_locations l ON a.location_id = l.id
    WHERE v.post_type = 'tsm_vehicle' 
    AND v.post_status = 'publish'
";
    
    // Add location filter if specified
    if (!empty($atts['location'])) {
        $query .= $wpdb->prepare(" AND l.name = %s", $atts['location']);
    }
    
    // Add vehicle type filter if specified
    if (!empty($atts['type'])) {
        $query .= $wpdb->prepare(" AND EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} 
            WHERE post_id = v.ID 
            AND meta_key = '_tsm_vehicle_type' 
            AND meta_value = %s
        )", $atts['type']);
    }
    
    $query .= " ORDER BY l.name, v.post_title";
    
    $vehicles = $wpdb->get_results($query);
    
    // Start output with filter dropdown
    $output = '<div class="tsm-vehicle-filter">';
    
    // Add location filter dropdown only if no location is specified in shortcode
    if (empty($atts['location'])) {
        $output .= '<select id="tsm-location-filter" class="tsm-filter-dropdown">';
        $output .= '<option value="" selected>Please select your preferred location</option>';
        $output .= '<option value="all">All Locations</option>';
        foreach ($all_locations as $location) {
            
            if ($location->name !== 'Outstations') {
                $output .= '<option value="' . esc_attr($location->name) . '">' . esc_html($location->name) . '</option>';
            }
        }
        $output .= '</select>';
    }
    
    // Add vehicle type filter if type parameter is set
    if (!empty($atts['type'])) {
        $output .= '<input type="hidden" id="tsm-vehicle-type" value="' . esc_attr($atts['type']) . '">';
    }
    
    $output .= '</div>';
    
    $output .= '<div class="tsm-vehicle-container">';
    // Output all vehicles in a single grid
    if (!empty($vehicles)) {
        $output .= '<div class="tsm-vehicle-grid"' . (empty($atts['location']) ? ' style="display: none;"' : '') . '>';
        
        foreach ($vehicles as $vehicle) {
            $vehicle_type = get_post_meta($vehicle->ID, '_tsm_vehicle_type', true);
            $fuel_type = get_post_meta($vehicle->ID, '_tsm_fuel_type', true);
            
            // Determine fuel type icon
            $fuel_icon = 'fa-gas-pump';
            if ($fuel_type === 'Electric') {
                $fuel_icon = 'fa-bolt';
            } elseif ($fuel_type === 'Hybrid') {
                $fuel_icon = 'fa-leaf';
            } elseif ($fuel_type === 'CNG') {
                $fuel_icon = 'fa-cloud';
            }
            
            $output .= '<div class="tsm-vehicle" data-location="' . esc_attr($vehicle->location_name) . '">';
            $output .= get_the_post_thumbnail($vehicle->ID, [300, 200]);
            $output .= '<h4>' . esc_html($vehicle->post_title) . '</h4>';
            
            $output .= '<div class="tsm-vehicle-details">';
            $output .= '<p><i class="fas fa-users"></i><strong>Seats:</strong> ' . esc_html(get_post_meta($vehicle->ID, '_tsm_seats', true)) . '</p>';
           if ($vehicle_type !== 'Boat') {
    if (!empty($fuel_type)) {
        $output .= '<p><i class="fas ' . esc_attr($fuel_icon) . '"></i><strong>Fuel Type:</strong> ' . esc_html($fuel_type) . '</p>';
    }
    
    $ac_status = get_post_meta($vehicle->ID, '_tsm_ac_status', true);
    if (!empty($ac_status)) {
        $ac_icon = ($ac_status === 'AC') ? 'fa-snowflake' : 'fa-fan';
        $output .= '<p><i class="fas ' . esc_attr($ac_icon) . '"></i><strong>AC Status:</strong> ' . esc_html($ac_status) . '</p>';
    }
}

$min_booking_hours = get_post_meta($vehicle->ID, '_tsm_min_booking_hours', true);
if (!empty($min_booking_hours)) {
    $output .= '<p><i class="fas fa-clock"></i><strong>Min Booking Hours:</strong> ' . esc_html($min_booking_hours) . '</p>';
}

// Only show price if it's greater than 0
if (!empty($vehicle->price) && floatval($vehicle->price) > 0) {
    $output .= '<p><i class="fas fa-solid fa-indian-rupee-sign"></i><strong>Price:</strong> ₹' . esc_html(number_format($vehicle->price, 2)) . '</p>';
}

if ($vehicle_type !== 'Boat') {
    // Only show Extra KM Price if it's greater than 0
    if (!empty($vehicle->extra_km_price) && floatval($vehicle->extra_km_price) > 0) {
        $output .= '<p><i class="fas fa-road"></i><strong>Extra KM Price:</strong> ₹' . esc_html(number_format($vehicle->extra_km_price, 2)) . '</p>';
    }
    
    // Only show Extra Hour Price if it's greater than 0
    if (!empty($vehicle->extra_hour_price) && floatval($vehicle->extra_hour_price) > 0) {
        $output .= '<p><i class="fas fa-hourglass"></i><strong>Extra Hour Price:</strong> ₹' . esc_html(number_format($vehicle->extra_hour_price, 2)) . '</p>';
    }
}

// Only show Max Range if it's greater than 0
if ($vehicle_type === 'Car' && !empty($vehicle->max_range) && floatval($vehicle->max_range) > 0) {
    $output .= '<p><i class="fas fa-solid fa-gauge"></i><strong>Max Range:</strong> ' . esc_html(number_format($vehicle->max_range, 2)) . ' km</p>';
}

if (!empty($vehicle->rental_type)) {
    $output .= '<p><i class="fas fa-ticket-alt"></i><strong>Rental Type:</strong> ' . esc_html($vehicle->rental_type) . '</p>';
}

$output .= '<p><i class="fas fa-check"></i><strong>Available:</strong> Yes</p>';
$output .= '</div>';
            
            // Use centralized URLs
            $output .= '<a href="' . esc_url($book_now_url) . '" class="tsm-btn book">Book Now</a>';
            $output .= '<a href="' . esc_url($contact_url) . '" class="tsm-btn">Contact</a>';
            
            $output .= '</div>'; // .tsm-vehicle
        }
        
        $output .= '</div>'; // .tsm-vehicle-grid
    }
    
    // No vehicles message
    $output .= '<div class="tsm-no-results"' . (empty($atts['location']) ? '' : ' style="display: none;"') . '>';
    if (empty($atts['location'])) {
        $output .= '<p>Select your location to see the vehicles</p>';
    } else {
        $output .= '<p>No vehicles available at this location.</p><a href="' . esc_url($services_url) . '" class="tsm-btn services">Explore Other Services</a>';
    }
    $output .= '</div>';
    
    $output .= '</div>'; // .tsm-vehicle-container
    
    // Add JavaScript for filtering
    $output .= '
    <script>
    jQuery(document).ready(function($) {
        // Initially hide/show based on whether location is preselected
        ' . (empty($atts['location']) ? '
        $(".tsm-vehicle-grid").hide();
        $(".tsm-no-results").show();
        ' : '
        $(".tsm-vehicle-grid").show();
        $(".tsm-vehicle").show();
        if ($(".tsm-vehicle").length === 0) {
            $(".tsm-no-results").show();
        }
        ') . '
        
        $("#tsm-location-filter").on("change", function() {
            var selectedLocation = $(this).val();
            var vehicleType = $("#tsm-vehicle-type").val() || "";
            
            // Hide all vehicles and no results message
            $(".tsm-vehicle").hide();
            $(".tsm-no-results").hide();
            $(".tsm-vehicle-grid").hide();
            
            if (selectedLocation === "") {
                // Show default message if default option is selected
                $(".tsm-no-results").html("<p>Select your location to see the vehicles</p>").show();
            } else if (selectedLocation === "all") {
                // Show all vehicles
                $(".tsm-vehicle-grid").show();
                $(".tsm-vehicle").show();
                if ($(".tsm-vehicle").length === 0) {
                    $(".tsm-no-results").html("<p>No vehicles available at this location.</p><a href=\"" + "' . esc_js($services_url) . '" + "\" class=\"tsm-btn services\">Explore Other Services</a>").show();
                }
            } else {
                // Show vehicles for the selected location
                var visibleVehicles = $(".tsm-vehicle[data-location=\"" + selectedLocation + "\"]");
                if (visibleVehicles.length > 0) {
                    $(".tsm-vehicle-grid").show();
                    visibleVehicles.show();
                } else {
                    $(".tsm-no-results").html("<p>No vehicles available at this location.</p><a href=\"" + "' . esc_js($services_url) . '" + "\" class=\"tsm-btn services\">Explore Other Services</a>").show();
                }
            }
            
            // Update URL without reloading
            var baseUrl = window.location.href.split("?")[0];
            var newUrl = baseUrl;
            
            if (selectedLocation && selectedLocation !== "") {
                newUrl += "?location=" + encodeURIComponent(selectedLocation);
                if (vehicleType) {
                    newUrl += "&type=" + encodeURIComponent(vehicleType);
                }
            } else if (vehicleType) {
                newUrl += "?type=" + encodeURIComponent(vehicleType);
            }
            
            history.pushState(null, "", newUrl);
        });
        
        // Trigger change on load if location is preselected
        if ($("#tsm-location-filter").val()) {
            $("#tsm-location-filter").trigger("change");
        }
    });
    </script>
    ';
    
    return $output;
}
add_shortcode('show_vehicles', 'tsm_display_vehicles_shortcode');

function tsm_display_tours_shortcode($atts) {
    $atts = shortcode_atts([], $atts, 'show_tours');
    
    // Get button URLs from options
    $book_now_url = get_option('tsm_book_now_url', '#');
    $contact_url = get_option('tsm_contact_url', '#');
    $services_url = get_permalink(get_page_by_path('services')) ?: '#';
    
    $tours = get_posts([
        'post_type' => 'tsm_tour',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    if (empty($tours)) {
        $output = '<div class="tsm-no-results">';
        $output .= '<p>No tour packages available.</p>';
        $output .= '<a href="' . esc_url($services_url) . '" class="tsm-btn services">Explore Other Services</a>';
        $output .= '</div>';
        return $output;
    }
    
    $output = '<div class="tsm-tour-grid">';
    foreach ($tours as $tour) {
        $price = get_post_meta($tour->ID, '_tsm_price_per_person', true);
        $days = get_post_meta($tour->ID, '_tsm_days', true);
        $nights = get_post_meta($tour->ID, '_tsm_nights', true);
        $location = get_post_meta($tour->ID, '_tsm_location', true);
        $places = get_post_meta($tour->ID, '_tsm_places_covered', true) ?: get_post_meta($tour->ID, '_tsm_places', true); // Fallback to _tsm_places
        $tour_type = get_post_meta($tour->ID, '_tsm_tour_type', true);
        $max_people = get_post_meta($tour->ID, '_tsm_max_people', true);
        
        $output .= '<div class="tsm-tour-card">';
        
        // Tour Image
        if (has_post_thumbnail($tour->ID)) {
            $output .= '<div class="tsm-tour-image">';
            $output .= get_the_post_thumbnail($tour->ID, 'large');
            $output .= '</div>';
        }
        
        // Tour Content
        $output .= '<div class="tsm-tour-content">';
        $output .= '<h3 class="tsm-tour-title">' . esc_html($tour->post_title) . '</h3>';
        
        // Tour Meta (Location, Type)
        $output .= '<div class="tsm-tour-meta">';
        if ($location) {
            $output .= '<span class="tsm-tour-location"><i class="fas fa-map-marker-alt"></i> ' . esc_html($location) . '</span>';
        }
        if ($tour_type) {
            $output .= '<span class="tsm-tour-type"><i class="fas fa-tag"></i> ' . esc_html($tour_type) . '</span>';
        }
        $output .= '</div>';
        
        // Tour Details (Duration, Price, Capacity)
        $output .= '<div class="tsm-tour-details">';
        if ($days && $nights) {
            $output .= '<div class="tsm-tour-duration"><i class="fas fa-calendar-alt"></i> <span>' . esc_html($days) . ' Days / ' . esc_html($nights) . ' Nights</span></div>';
        }
        if ($price) {
            $output .= '<div class="tsm-tour-price"><i class="fas fa-rupee-sign"></i> <span>₹' . esc_html(number_format($price, 2)) . ' per person</span></div>';
        }
        if ($max_people) {
            $output .= '<div class="tsm-tour-capacity"><i class="fas fa-users"></i> <span>Max ' . esc_html($max_people) . ' people</span></div>';
        }
        $output .= '</div>';
        
        // Places Covered
        if ($places) {
            $output .= '<div class="tsm-tour-places">';
            $output .= '<h4>Places Covered:</h4>';
            $output .= '<p>' . esc_html($places) . '</p>';
            $output .= '</div>';
        }
        
        // Tour Actions
        $output .= '<div class="tsm-tour-actions">';
        $output .= '<a href="' . esc_url(get_permalink($tour->ID)) . '" class="tsm-btn tsm-view-details">View Details</a>';
        $output .= '<a href="' . esc_url($contact_url) . '" class="tsm-btn tsm-contact-btn">Contact</a>';
        $output .= '</div>';
        
        $output .= '</div>'; // .tsm-tour-content
        $output .= '</div>'; // .tsm-tour-card
    }
    $output .= '</div>'; // .tsm-tour-grid
    
    return $output;
}
add_shortcode('show_tours', 'tsm_display_tours_shortcode');


function tsm_tour_single_template($template) {
    if (is_singular('tsm_tour')) {
        $new_template = plugin_dir_path(__FILE__) . 'templates/single-tsm_tour.php';
        if (file_exists($new_template)) {
            return $new_template;
        }
    }
    return $template;
}
add_filter('single_template', 'tsm_tour_single_template');


// === Enqueue Styles ===
function tsm_enqueue_styles() {
    wp_register_style('tsm-inline-style', false);
    wp_enqueue_style('tsm-inline-style');
    
    // Enqueue Font Awesome for icons
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
    
    $custom_css = "
        /* Tour Grid Styles */
        .tsm-tour-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .tsm-tour-card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .tsm-tour-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }
        
        .tsm-tour-image {
            height: 250px;
            overflow: hidden;
        }
        
        .tsm-tour-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .tsm-tour-card:hover .tsm-tour-image img {
            transform: scale(1.05);
        }
        
        .tsm-tour-content {
            padding: 20px;
        }
        
        .tsm-tour-title {
            font-size: 2rem !important;
            margin: 0 0 10px;
            color: #333;
        }
        
        .tsm-tour-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .tsm-tour-details {
            margin: 15px 0;
            text-align: left;
        }
        
        .tsm-tour-details div {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.95rem;
            color: #555;
        }
        
        .tsm-tour-details i {
            margin-right: 8px;
            color: #0073aa;
            width: 20px;
            text-align: center;
        }
        
        .tsm-tour-places {
            margin: 15px 0;
        }
        
        .tsm-tour-places h4 {
            font-size: 1.1rem;
            margin: 0 0 8px;
            color: #444;
        }
        
        .tsm-tour-places p {
            margin: 0;
            font-size: 0.95rem;
            color: #666;
        }
        
        .tsm-tour-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* Vehicle Grid Styles */
        .tsm-vehicle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .tsm-vehicle {
            border: 1px solid #ddd;
            padding: 16px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
            background-color: #fff;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.2s ease;
        }
        
        .tsm-vehicle:hover {
            transform: translateY(-4px);
        }
        
        .tsm-vehicle img {
            width: 100% !important;
            height: 200px !important;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .tsm-vehicle h3 {
            font-size: 22px;
            margin: 12px 0 8px;
            color: #333;
        }
        
        .tsm-vehicle-details {
            text-align: left;
            margin: 10px 0;
        }
        
        .tsm-vehicle-details p {
            display: flex;
            align-items: center;
            margin: 6px 0;
            font-size: 15px;
            color: #555;
        }
        
        .tsm-vehicle-details i {
            margin-right: 8px;
            color: #e74c3c;
            width: 20px;
            text-align: center;
        }
        
        /* Button Styles */
        .tsm-btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            flex: 1;
            text-align: center;
            margin: 10px 8px 0;
            color: #fff;
            background-color: #0073aa;
        }
        
        .tsm-btn.view-details {
            background-color: #0073aa;
        }
        
        .tsm-btn.view-details:hover {
            background-color: #005f8c;
            color: white;
        }
        
        .tsm-btn.book {
            background-color: #28a745;
        }
        
        .tsm-btn.book:hover {
            background-color: #218838;
            color: white;
        }
        
        .tsm-contact-btn {
            background-color: #28a745;
        }
        
        .tsm-contact-btn:hover {
            background-color: #218838;
            color: white;
        }
        
        /* No Results Styles */
        .tsm-no-results {
            text-align: center;
            padding: 20px;
        }
        
        .tsm-no-results p {
            font-size: 25px;
            color: #555;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .tsm-no-results .tsm-btn {
            background-color: #0073aa;
            padding: 12px 24px;
        }
        
        .tsm-no-results .tsm-btn:hover {
            background-color: #005f8c;
        }
        
        /* Single Tour Page Styles */
        .tsm-tour-single {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .tsm-tour-single h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 20px;
        }
        
        .tsm-tour-featured-image img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .tsm-tour-content {
            margin-bottom: 20px;
            font-size: 16px;
            color: #444;
        }
        
        .tsm-tour-itinerary, .tsm-tour-features, .tsm-tour-guidelines {
            margin-bottom: 30px;
        }
        
        .tsm-tour-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .tsm-itinerary-day {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        
        .tsm-itinerary-day h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .tsm-tour-features {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .tsm-feature {
            text-align: center;
        }
        
        .tsm-feature i {
            font-size: 24px;
            color: #0073aa;
            margin-bottom: 10px;
        }
        
        .tsm-feature h4 {
            font-size: 18px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .tsm-tour-guidelines {
            list-style: disc;
            padding-left: 20px;
        }
        
        .tsm-tour-guidelines li {
            font-size: 16px;
            color: #555;
            margin-bottom: 8px;
        }
        
        .tsm-tour-actions {
            text-align: center;
            margin-top: 20px;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .tsm-tour-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                padding: 15px;
            }
            
            .tsm-vehicle-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 15px;
            }
            
            .tsm-tour-image {
                height: 180px;
            }
            
            .tsm-tour-title {
                font-size: 2rem !important;
            }
            
            .tsm-tour-content {
                padding: 15px;
            }
            
            .tsm-vehicle img {
                height: 180px !important;
            }
        }
        
        @media (max-width: 600px) {
            .tsm-tour-grid {
                grid-template-columns: 1fr;
            }
            
            .tsm-vehicle-grid {
                grid-template-columns: 1fr;
            }
            
            .tsm-tour-image {
                height: 160px;
            }
            
            .tsm-tour-title {
                font-size: 2rem !important;
            }
            
            .tsm-tour-actions {
                flex-direction: column;
            }
            
            .tsm-btn {
                width: 100%;
                margin: 5px 0;
            }
            
            .tsm-vehicle img {
                height: 160px !important;
            }
        }
    ";
    
    wp_add_inline_style('tsm-inline-style', $custom_css);
}
add_action('wp_enqueue_scripts', 'tsm_enqueue_styles');

function tsm_add_rental_type_column() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tsm_vehicle_assignments';
    $column = $wpdb->get_results("SHOW COLUMNS FROM `$table_name` LIKE 'rental_type'");
    if (empty($column)) {
        $wpdb->query("ALTER TABLE `$table_name` ADD `rental_type` VARCHAR(50) NOT NULL DEFAULT 'Round Trip'");
    }
}
add_action('admin_init', 'tsm_add_rental_type_column');