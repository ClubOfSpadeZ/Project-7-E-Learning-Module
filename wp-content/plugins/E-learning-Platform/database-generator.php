<?php

function elearn_database_generator() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $prefix = $wpdb->prefix . 'elearn_';

    $tables = [];

    $tables[] = "CREATE TABLE {$prefix}organisation (
        organisation_id CHAR(20) NOT NULL,
        organisation_name VARCHAR(45) NULL,
        organisation_address VARCHAR(255) NULL,
        organisation_phone VARCHAR(45) NULL,
        organisation_email VARCHAR(100) NULL,
        organisation_abn VARCHAR(45) NULL,
        organisation_created DATETIME NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (organisation_id)
    ) $charset_collate;";

    $tables[] = "CREATE TABLE {$prefix}attempt (
        attempt_id BIGINT NOT NULL AUTO_INCREMENT,
        attempt_time DATETIME NULL DEFAULT '0000-00-00 00:00:00',
        attempt_score VARCHAR(45) NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        module_module_id BIGINT NOT NULL,
        PRIMARY KEY (attempt_id)
    ) $charset_collate;";

    $tables[] = "CREATE TABLE {$prefix}certificate (
        certificate_id BIGINT NOT NULL AUTO_INCREMENT,
        certificate_completion DATETIME NULL,
        attempt_id BIGINT NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (certificate_id)
    ) $charset_collate;";

    $tables[] = "CREATE TABLE {$prefix}module (
        module_id BIGINT NOT NULL AUTO_INCREMENT,
        module_name VARCHAR(45) NULL,
        module_description LONGTEXT NULL,
        module_created DATETIME NULL DEFAULT '0000-00-00 00:00:00',
        module_pdf_path VARCHAR(255) NULL,
        module_thumbnail_path VARCHAR(255) NULL,
        PRIMARY KEY (module_id)
    ) $charset_collate;";

    $tables[] = "CREATE TABLE {$prefix}question (
        question_id BIGINT NOT NULL AUTO_INCREMENT,
        question_type ENUM('multiple_choice', 'true_false', 'short_answer') NULL,
        question_text LONGTEXT NULL,
        PRIMARY KEY (question_id)
    ) $charset_collate;";

    $tables[] = "CREATE TABLE {$prefix}licence (
        licence_id BIGINT NOT NULL AUTO_INCREMENT,
        licence_name VARCHAR(45) NULL,
        licence_description LONGTEXT NULL,
        user_amount VARCHAR(45) NULL,
        licence_cost VARCHAR(45) NULL,
        licence_created DATETIME NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (licence_id)
    ) $charset_collate;";

    $tables[] = "CREATE TABLE {$prefix}choice (
        choice_id BIGINT NOT NULL AUTO_INCREMENT,
        question_id BIGINT NOT NULL,
        choice_data LONGTEXT NULL,
        choice_correct TINYINT NULL,
        PRIMARY KEY (choice_id)
    ) $charset_collate;";

    $tables[] = "CREATE TABLE {$prefix}licences_in_organisation (
        licence_licence_id BIGINT NOT NULL,
        organisation_organisation_id CHAR(20) NOT NULL,
        PRIMARY KEY (licence_licence_id, organisation_organisation_id)
    ) $charset_collate;";

    $tables[] = "CREATE TABLE {$prefix}content_in_modules (
        module_module_id BIGINT NOT NULL,
        question_question_id BIGINT NOT NULL,
        PRIMARY KEY (module_module_id, question_question_id)
    ) $charset_collate;";

    $tables[] = "CREATE TABLE {$prefix}module_in_licence (
        module_module_id BIGINT NOT NULL,
        licence_licence_id BIGINT NOT NULL,
        PRIMARY KEY (module_module_id, licence_licence_id)
    ) $charset_collate;";

    $tables[] = "CREATE TABLE {$prefix}access (
        access_id INT AUTO_INCREMENT,
        access_code VARCHAR(100) NOT NULL UNIQUE,
        organisation_organisation_id CHAR(20) NOT NULL,
        is_used TINYINT(1) DEFAULT 0,
        access_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        access_used TIMESTAMP DEFAULT NULL,
        PRIMARY KEY (access_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    foreach ($tables as $sql) {
        dbDelta($sql);
    }

    foreach ($tables as $sql) {
        $wpdb->query($sql);

        if (!empty($wpdb->last_error)) {
            $error_message = "SQL Error: " . $wpdb->last_error;
            error_log($error_message, 3, __DIR__ . '/sql-errors.log'); // Log to a custom file
            echo "<pre>" . esc_html($error_message) . "</pre>"; // Display the error
        }
    }

    wp_cache_flush(); 
}