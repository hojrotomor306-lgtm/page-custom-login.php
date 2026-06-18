<?php
/*
Plugin Name: Custom Login Page Widget
Description: Automatically updates from GitHub.
Version: 1.0
Author: Tomor
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Template Name: WooCommerce ERP Login
 * Description: Standalone premium login page with glassmorphic secure authentication, neon red styling, and reactive AJAX.
 */

// Redirect already logged-in users with correct ERP capabilities.
add_action( 'template_redirect', 'erp_redirect_logged_in_users' );
function erp_redirect_logged_in_users() {
    if ( is_page_template( 'page-custom-login.php' ) && is_user_logged_in() ) {
        if ( current_user_can( 'manage_woocommerce' ) || current_user_can( 'administrator' ) ) {
            wp_safe_redirect( site_url( '/custom-dashboard/' ) );
            exit;
        }
    }
}

// SECURE AJAX AUTHENTICATION HANDLER
add_action( 'init', 'erp_handle_secure_ajax_login' );
function erp_handle_secure_ajax_login() {
    if ( isset( $_POST['erp_action'] ) && $_POST['erp_action'] === 'erp_ajax_login' ) {
        
        if ( ob_get_length() ) {
            ob_clean();
        }

        // 1. Verify Nonce
        if ( ! isset( $_POST['erp_security_nonce'] ) || ! wp_verify_nonce( $_POST['erp_security_nonce'], 'erp_standalone_login_nonce' ) ) {
            wp_send_json( array(
                'success' => false,
                'message' => 'Security token verification failed. Please refresh the page.'
            ) );
        }

        // 2. Validate inputs
        $username = isset( $_POST['erp_username'] ) ? sanitize_user( wp_unslash( $_POST['erp_username'] ) ) : '';
        $password = isset( $_POST['erp_password'] ) ? $_POST['erp_password'] : '';

        if ( empty( $username ) || empty( $password ) ) {
            wp_send_json( array(
                'success' => false,
                'message' => 'Please fill in all security credentials.'
            ) );
        }

        if ( ! class_exists( 'WooCommerce' ) ) {
            wp_send_json( array(
                'success' => false,
                'message' => 'Critical System Failure: WooCommerce is disabled.'
            ) );
        }

        // 3. Authenticate User
        $user = wp_authenticate( $username, $password );

        if ( is_wp_error( $user ) ) {
            wp_send_json( array(
                'success' => false,
                'message' => 'Access Denied: Invalid credentials or insufficient permissions.'
            ) );
        }

        // 4. Authorize Role/Capability
        if ( ! user_can( $user, 'manage_woocommerce' ) && ! user_can( $user, 'administrator' ) ) {
            wp_send_json( array(
                'success' => false,
                'message' => 'Access Restricted: You do not possess the required ERP clearance.'
            ) );
        }

        // 5. Sign In
        wp_clear_auth_cookie();
        wp_set_current_user( $user->ID, $user->user_login );
        wp_set_auth_cookie( $user->ID, isset( $_POST['erp_remember'] ) && $_POST['erp_remember'] === 'true', is_ssl() );

        do_action( 'wp_login', $user->user_login, $user );

        wp_send_json( array(
            'success' => true,
            'message' => 'Access Granted! Synchronizing ERP secure terminal...',
            'redirect' => site_url( '/custom-dashboard/' )
        ) );
    }
}

// Load Custom Template Output
add_filter( 'template_include', 'erp_custom_login_template_include' );
function erp_custom_login_template_include( $template ) {
    if ( get_query_var( 'pagename' ) === 'erp-login' || is_page( 'ERP Login' ) ) {
        // Render the UI directly if this page is called
        erp_render_custom_login_html();
        exit;
    }
    return $template;
}

function erp_render_custom_login_html() {
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ERP Security Terminal - <?php bloginfo( 'name' ); ?></title>
        <?php wp_head(); ?>
        <style>
            :root {
                --primary-red: #dc2626;
                --hover-red: #ef4444;
                --neon-glow: rgba(220, 38, 38, 0.45);
                --glass-bg: rgba(13, 14, 18, 0.85);
                --border-glass: rgba(220, 38, 38, 0.25);
                --charcoal: #090a0f;
            }
            body.erp-login-template {
                margin: 0; padding: 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background-color: var(--charcoal);
                min-height: 100vh;
                display: flex; align-items: center; justify-content: center;
                overflow: hidden; position: relative;
            }
            .erp-cyber-bg {
                position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1;
                background: radial-gradient(circle at 15% 15%, rgba(220, 38, 38, 0.15) 0%, transparent 60%),
                            radial-gradient(circle at 85% 85%, rgba(220, 38, 38, 0.12) 0%, transparent 65%);
            }
            .geometry-polygon { position: absolute; stroke: rgba(220,38,38,0.25); stroke-width: 1; fill: rgba(0,0,0,0.4); }
            .erp-login-wrapper { position: relative; z-index: 10; width: 100%; max-width: 440px; padding: 20px; }
            .erp-glass-card { background: var(--glass-bg); border: 1px solid var(--border-glass); border-radius: 16px; padding: 40px 32px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.7), 0 0 30px var(--neon-glow); backdrop-filter: blur(20px); }
            .erp-form-group { margin-bottom: 20px; }
            .erp-form-group label { display: block; color: #9ca3af; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; }
            .erp-form-control { width: 100%; background: rgba(0, 0, 0, 0.5); border: 1px solid rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 8px; padding: 14px; font-size: 14px; box-sizing: border-box; }
            .erp-form-control:focus { border-color: var(--primary-red); outline: none; }
            .erp-submit-button { width: 100%; background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%); border: none; color: white; padding: 15px; border-radius: 8px; font-weight: bold; cursor: pointer; text-transform: uppercase; }
            .erp-response-banner { padding: 12px; margin-bottom: 20px; border-radius: 8px; display: none; font-size: 13px; }
            .banner-error { background: rgba(220, 38, 38, 0.2); color: #fca5a5; border: 1px solid var(--primary-red); display: block; }
        </style>
    </head>
    <body class="erp-login-template">
        <div class="erp-cyber-bg">
            <svg width="100%" height="100%">
                <polygon points="100,200 450,150 300,550" class="geometry-polygon" />
            </svg>
        </div>
        <div class="erp-login-wrapper">
            <div class="erp-glass-card">
                <h2 style="color:white; text-align:center; margin-bottom: 5px;">ERP TERMINAL</h2>
                <p style="color:#9ca3af; text-align:center; font-size:12px; margin-bottom:30px;">SECURE GATEWAY</p>
                
                <div id="erp-banner" class="erp-response-banner" style="display:none;"></div>

                <form id="erp-login-form" method="POST">
                    <input type="hidden" name="erp_action" value="erp_ajax_login">
                    <?php wp_nonce_field( 'erp_standalone_login_nonce', 'erp_security_nonce' ); ?>
                    
                    <div class="erp-form-group">
                        <label>Username or Email</label>
                        <input type="text" name="erp_username" class="erp-form-control" required>
                    </div>
                    
                    <div class="erp-form-group">
                        <label>Password</label>
                        <input type="password" name="erp_password" class="erp-form-control" required>
                    </div>

                    <button type="submit" class="erp-submit-button">Authenticate</button>
                </form>
            </div>
        </div>
        <?php wp_footer(); ?>
        <script>
            document.getElementById('erp-login-form').addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                var banner = document.getElementById('erp-banner');
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    banner.style.display = 'block';
                    banner.innerText = data.message;
                    if(data.success) {
                        banner.className = 'erp-response-banner';
                        banner.style.backgroundColor = 'rgba(16, 185, 129, 0.2)';
                        banner.style.color = '#a7f3d0';
                        setTimeout(() => { window.location.href = data.redirect; }, 1500);
                    } else {
                        banner.className = 'erp-response-banner banner-error';
                    }
                })
                .catch(() => {
                    banner.style.display = 'block';
                    banner.className = 'erp-response-banner banner-error';
                    banner.innerText = 'An unexpected server error occurred.';
                });
            });
        </script>
    </body>
    </html>
    <?php
}
