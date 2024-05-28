<?php

/*
Plugin name: BackStopJs
Version: 1.0.0
Author: Ed Ellingham
Author URI: https://cloudnineweb.co/
Description: This plugin intigrates BackStopJs with Wordpress
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


// Hook into the admin menu
add_action('admin_menu', 'backstopjs_plugin_settings_page');

function backstopjs_plugin_settings_page() {
    // Add a new top-level menu (main menu)
    add_menu_page(
        'BackStopJs Test', // Page title
        'BackStopJs',          // Menu title
        'manage_options',      // Capability
        'backstopjs-plugin',   // Menu slug
        'backstopjs_plugin_settings_page_html' // Callback function
    );
}

function backstopjs_plugin_settings_page_html() {
    $home_url = home_url();
    ?>
        <style>
            .backstopjs-admin-buttons{
                margin-top: 30px;
                margin-bottom: 30px;
                display: flex;
                gap: 20px;
            }
            .backstopjs-admin-button, .backstopjs-install-button{
                padding: 10px 20px;
                background: #ddd;
                color: #000;
                text-decoration: none;
            }
            .backstopjs-admin-button{
                display: none;
            }

            .backstopjs-admin-button:hover{
                color: #fff;
                background: #000;
            }

            .backstopjs-admin-button.pass:hover{
                padding: 10px 20px;
                background: green;
                color: #fff;
                text-decoration: none;
            }
            pre.backstopjs-log{
                background: black;
                padding: 20px;
                color: #fff;
                width: calc(100% - 80px);
                min-height: 400px;
            }
        </style>
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="backstopjs-admin-buttons">
            <a class="backstopjs-install-button" href="<?php echo $home_url ?>/wp-json/backstop/v1/install">InstalL using NPM</a>
            <a class="backstopjs-admin-button" href="<?php echo $home_url ?>/wp-json/backstop/v1/reference">Take reference Screenshoot</a>
            <a class="backstopjs-admin-button" href="<?php echo $home_url ?>/wp-json/backstop/v1/test">Test</a>
            <a class="backstopjs-admin-button pass" href="<?php echo $home_url ?>/wp-json/backstop/v1/approve">Approve</a>
        </div>

        <pre class="backstopjs-log">BackStopJs Test Log</pre>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var backstopjsInstalled = localStorage.getItem('backstopjsInstalled');
                console.log(backstopjsInstalled);
                
                if(backstopjsInstalled == 'yes'){
                    $('.backstopjs-install-button').hide();
                    $('.backstopjs-admin-button').show();
                }

                $('.backstopjs-install-button').click(function(event){
                    event.preventDefault(); // Prevent the default behavior
                    $('.backstopjs-log').html("Installing backstopjs ...");

                    var url = $(this).attr('href'); // Get the href attribute value

                    // Send AJAX request to the URL
                    $.ajax({
                        url: url,
                        type: 'GET',
                        success: function(response) {
                            console.log(response.output);
                            // Log the success message inside .backstop-js div
                            $('.backstopjs-log').html(response.output);

                            localStorage.setItem('backstopjsInstalled', 'yes')
                            $('.backstopjs-install-button').hide();
                            $('.backstopjs-admin-button').show();
                        },
                        error: function(xhr, status, error) {
                            // Log the error message inside .backstop-js div
                            $('.backstop-js').html('<p>Error: ' + xhr.responseText + '</p>');
                        }
                    });
                });

                $('.backstopjs-admin-button').click(function(event){
                    event.preventDefault(); // Prevent the default behavior
                    $('.backstopjs-log').html("Loading ...");

                    var url = $(this).attr('href'); // Get the href attribute value

                    // Send AJAX request to the URL
                    $.ajax({
                        url: url,
                        type: 'GET',
                        success: function(response) {
                            console.log(response.output);
                            // Log the success message inside .backstop-js div
                            $('.backstopjs-log').html(response.output);
                        },
                        error: function(xhr, status, error) {
                            // Log the error message inside .backstop-js div
                            $('.backstop-js').html('<p>Error: ' + xhr.responseText + '</p>');
                        }
                    });
                });
            });
        </script>
    <?php
}


// Register the custom REST API endpoint

add_action('rest_api_init', function () {
    // http://localhost/wordpress/wp-json/backstop/v1/install
    register_rest_route('backstop/v1', '/install', [
        'methods' => 'GET',
        'callback' => 'run_backstop_install',
        'permission_callback' => '__return_true', // You can add custom permissions here
    ]);
    // http://localhost/wordpress/wp-json/backstop/v1/reference
    register_rest_route('backstop/v1', '/reference', [
        'methods' => 'GET',
        'callback' => 'run_backstop_reference',
        'permission_callback' => '__return_true', // You can add custom permissions here
    ]);
    // http://localhost/wordpress/wp-json/backstop/v1/test
    register_rest_route('backstop/v1', '/test', [
        'methods' => 'GET',
        'callback' => 'run_backstop_test',
        'permission_callback' => '__return_true', // You can add custom permissions here
    ]);
    // http://localhost/wordpress/wp-json/backstop/v1/approve
    register_rest_route('backstop/v1', '/approve', [
        'methods' => 'GET',
        'callback' => 'run_backstop_approve',
        'permission_callback' => '__return_true', // You can add custom permissions here
    ]);
});

function run_backstop_install() {

    // Execute the npm install -g backstopjs command and capture the output and errors
    $output = shell_exec('npm install -g backstopjs');

    // Return the output
    return new WP_REST_Response(['output' => $output], 200);
}

function run_backstop_reference() {
    // Get the directory of the current plugin
    $pluginBaseDir = __DIR__; // This will be the base directory of your plugin

    // Dynamically get the full path to the backstop executable
    $backstopPath = shell_exec('where backstop.cmd'); // Use `which backstop` for Unix-like systems

    // Clean up the path (remove any extra whitespace or newline characters)
    $backstopPath = trim($backstopPath);

    // Check if the path was found
    if (!$backstopPath) {
        return new WP_REST_Response(['error' => 'backstop executable not found.'], 500);
    }

    // Change the working directory to the plugin's base directory
    chdir($pluginBaseDir);

    // Execute the backstop reference command and capture the output and errors
    $output = shell_exec("$backstopPath reference 2>&1"); // Redirect stderr to stdout

    // Return the output
    return new WP_REST_Response(['output' => $output], 200);
}

function run_backstop_test() {
    // Get the directory of the current plugin
    $pluginBaseDir = __DIR__; // This will be the base directory of your plugin

    // Dynamically get the full path to the backstop executable
    $backstopPath = shell_exec('where backstop.cmd'); // Use `which backstop` for Unix-like systems

    // Clean up the path (remove any extra whitespace or newline characters)
    $backstopPath = trim($backstopPath);

    // Check if the path was found
    if (!$backstopPath) {
        return new WP_REST_Response(['error' => 'backstop executable not found.'], 500);
    }

    // Change the working directory to the plugin's base directory
    chdir($pluginBaseDir);

    // Execute the backstop reference command and capture the output and errors
    $output = shell_exec("$backstopPath test 2>&1"); // Redirect stderr to stdout

    // Return the output
    return new WP_REST_Response(['output' => $output], 200);
}

function run_backstop_approve() {
    // Get the directory of the current plugin
    $pluginBaseDir = __DIR__; // This will be the base directory of your plugin

    // Dynamically get the full path to the backstop executable
    $backstopPath = shell_exec('where backstop.cmd'); // Use `which backstop` for Unix-like systems

    // Clean up the path (remove any extra whitespace or newline characters)
    $backstopPath = trim($backstopPath);

    // Check if the path was found
    if (!$backstopPath) {
        return new WP_REST_Response(['error' => 'backstop executable not found.'], 500);
    }

    // Change the working directory to the plugin's base directory
    chdir($pluginBaseDir);

    // Execute the backstop reference command and capture the output and errors
    $output = shell_exec("$backstopPath approve 2>&1"); // Redirect stderr to stdout

    // Return the output
    return new WP_REST_Response(['output' => $output], 200);
}