<?php
/*
Plugin Name: Custom Login Page Widget
Description: Automatically updates from GitHub.
Version: 1.0
Author: Tomor
*/
‎/**
· ‎Template Name: WooCommerce ERP Login
· ‎Description: Standalone premium login page with glassmorphic secure authentication, neon red styling, and reactive AJAX.
· ‎Version: 1.0.0
· ‎Package: WooCommerce_ERP
· ‎Author: Custom ERP Developer
· ‎
· ‎Instructions:
· ‎1. Upload this file as 'page-custom-login.php' to your active theme directory (wp-content/themes/your-theme/).
· ‎2. In WordPress Admin, create a new Page named 'ERP Login'.
· ‎3. In the Page Attributes panel check "Template" and select "WooCommerce ERP Login".
· ‎4. Publish page. It will act as the standalone secure gateway.
‎*/
‎
‎// Exit if accessed directly.
‎if ( ! defined( 'ABSPATH' ) ) {
‎    exit;
‎}
‎
‎// Redirect already logged-in users with correct ERP capabilities.
‎if ( is_user_logged_in() ) {
‎    if ( current_user_can( 'manage_woocommerce' ) || current_user_can( 'administrator' ) ) {
‎        if ( ! headers_sent() ) {
‎            wp_safe_redirect( site_url( '/custom-dashboard/' ) );
‎            exit;
‎        } else {
‎            echo '<script>window.location.href="' . esc_url( site_url( '/custom-dashboard/' ) ) . '";</script>';
‎            exit;
‎        }
‎    }
‎}
‎
‎// -------------------------------------------------------------
‎// SECURE AJAX AUTHENTICATION HANDLER (Intercepts requests)
‎// -------------------------------------------------------------
‎if ( isset( $_POST['erp_action'] ) && $_POST['erp_action'] === 'erp_ajax_login' ) {
‎
‎    // Clear any previous output buffers to avoid PHP notices corrupting the JSON
‎    if ( ob_get_length() ) {
‎        ob_clean();
‎    }
‎
‎    // 1. Verify Nonce to prevent CSRF
‎    if ( ! isset( $_POST['erp_security_nonce'] ) || ! wp_verify_nonce( $_POST['erp_security_nonce'], 'erp_standalone_login_nonce' ) ) {
‎        wp_send_json( array(
‎            'success' => false,
‎            'message' => 'Security token verification failed. Please refresh the page.'
‎        ) );
‎    }
‎
‎    // 2. Validate inputs
‎    $username = isset( $_POST['erp_username'] ) ? sanitize_user( wp_unslash( $_POST['erp_username'] ) ) : '';
‎    $password = isset( $_POST['erp_password'] ) ? $_POST['erp_password'] : ''; // Authenticate function handles plaintext password validation in crypt
‎
‎    if ( empty( $username ) || empty( $password ) ) {
‎        wp_send_json( array(
‎            'success' => false,
‎            'message' => 'Please fill in all security credentials.'
‎        ) );
‎    }
‎
‎    // Check if WooCommerce is active
‎    if ( ! class_exists( 'WooCommerce' ) ) {
‎        wp_send_json( array(
‎            'success' => false,
‎            'message' => 'Critical System Failure: WooCommerce is disabled.'
‎        ) );
‎    }
‎
‎    // 3. Authenticate User securely via native wp_authenticate
‎    $user = wp_authenticate( $username, $password );
‎
‎    if ( is_wp_error( $user ) ) {
‎        // Detailed log check but return simplified generic safe error to user to avoid enumeration vulnerabilities
‎        wp_send_json( array(
‎            'success' => false,
‎            'message' => 'Access Denied: Invalid credentials or insufficient permissions.'
‎        ) );
‎    }
‎
‎    // 4. Authorize Role/Capability
‎    if ( ! user_can( $user, 'manage_woocommerce' ) && ! user_can( $user, 'administrator' ) ) {
‎        wp_send_json( array(
‎            'success' => false,
‎            'message' => 'Access Restricted: You do not possess the required ERP clearance.'
‎        ) );
‎    }
‎
‎    // 5. SECURELY SIGN IN & ESTABLISH WORDPRESS SESSION (Eliminates double verification overhead)
‎    // Directly log the user in since we have already cryptographically verified their credentials above.
‎    wp_clear_auth_cookie();
‎    wp_set_current_user( $user->ID, $user->user_login );
‎    wp_set_auth_cookie( $user->ID, isset( $_POST['erp_remember'] ) && $_POST['erp_remember'] === 'true', is_ssl() );
‎
‎    // Trigger the standard core WordPress login hooks for full compatibility with monitoring/security plugins
‎    do_action( 'wp_login', $user->user_login, $user );
‎
‎    // Success response - Redirect to dashboard template page
‎    wp_send_json( array(
‎        'success' => true,
‎        'message' => 'Access Granted! Synchronizing ERP secure terminal...',
‎        'redirect' => site_url( '/custom-dashboard/' )
‎    ) );
‎}
‎?>
‎<!DOCTYPE html>
‎<html <?php language_attributes(); ?>>
‎<head>
‎    <meta charset="<?php bloginfo( 'charset' ); ?>">
‎    <meta name="viewport" content="width=device-width, initial-scale=1.0">
‎    <title>ERP Security Terminal - <?php bloginfo( 'name' ); ?></title>
‎    <?php wp_head(); ?>
‎
‎    <style id="erp-login-styles">
‎        /* Reset and Base Styles */
‎        :root {
‎            --primary-red: #dc2626;
‎            --hover-red: #ef4444;
‎            --neon-glow: rgba(220, 38, 38, 0.45);
‎            --glass-bg: rgba(13, 14, 18, 0.85);
‎            --border-glass: rgba(220, 38, 38, 0.25);
‎            --charcoal: #090a0f;
‎        }
‎
‎        body.erp-login-template {
‎            margin: 0;
‎            padding: 0;
‎            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
‎            background-color: var(--charcoal);
‎            min-height: 100vh;
‎            display: flex;
‎            align-items: center;
‎            justify-content: center;
‎            overflow-x: hidden;
‎            position: relative;
‎        }
‎
‎        /* Abstract High-End Geometric Background SVG overlays and metallic shards */
‎        .erp-cyber-bg {
‎            position: absolute;
‎            top: 0;
‎            left: 0;
‎            width: 100%;
‎            height: 100%;
‎            z-index: 1;
‎            background: 
‎                radial-gradient(circle at 15% 15%, rgba(220, 38, 38, 0.15) 0%, transparent 60%),
‎                radial-gradient(circle at 85% 85%, rgba(220, 38, 38, 0.12) 0%, transparent 65%);
‎            background-size: cover;
‎            overflow: hidden;
‎        }
‎
‎        .erp-cyber-bg::before {
‎            content: '';
‎            position: absolute;
‎            top: 0;
‎            left: 0;
‎            right: 0;
‎            bottom: 0;
‎            background: linear-gradient(135deg, transparent 40%, rgba(220, 38, 38, 0.05) 45%, rgba(0,0,0,0) 50%, rgba(220, 38, 38, 0.05) 55%, transparent 60%);
‎            opacity: 0.8;
‎            pointer-events: none;
‎        }
‎
‎        /* Ambient Geometric Shards via SVG CSS overlays */
‎        .geometry-polygon {
‎            position: absolute;
‎            stroke: rgba(220,38,38,0.25);
‎            stroke-width: 1;
‎            fill: rgba(0,0,0,0.4);
‎            filter: drop-shadow(0 0 12px rgba(220, 38, 38, 0.15));
‎        }
‎
‎        /* Container & Elegant Glassmorphism Card */
‎        .erp-login-wrapper {
‎            position: relative;
‎            z-index: 10;
‎            width: 100%;
‎            max-width: 440px;
‎            padding: 20px;
‎            box-sizing: border-box;
‎        }
‎
‎        .erp-glass-card {
‎            background: var(--glass-bg);
‎            border: 1px solid var(--border-glass);
‎            border-radius: 16px;
‎            padding: 40px 32px;
‎            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.7), 
‎                        0 0 30px var(--neon-glow);
‎            backdrop-filter: blur(20px);
‎            -webkit-backdrop-filter: blur(20px);
‎            transition: border-color 0.4s ease, box-shadow 0.4s ease;
‎        }
‎
‎        .erp-glass-card.card-pulse {
‎            border-color: rgba(239, 68, 68, 0.65);
‎            box-shadow: 0 10px 45px rgba(0, 0, 0, 0.8), 
‎                        0 0 40px rgba(239, 68, 68, 0.6);
‎        }
‎
‎        /* Brand Logo Placeholder */
‎        .erp-logo-container {
‎            width: 200px;
‎            height: 72px;
‎            background: rgba(0, 0, 0, 0.72);
‎            border: 1px solid var(--border-glass);
‎            border-radius: 12px;
‎            margin: 0 auto 30px auto;
‎            display: flex;
‎            align-items: center;
‎            justify-content: center;
‎            box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.8), 0 0 15px var(--neon-glow);
‎            position: relative;
‎            overflow: hidden;
‎        }
‎
‎        .erp-logo-container svg {
‎            width: 100%;
‎            height: 100%;
‎        }
‎
‎        .erp-logo-container::after {
‎            content: '';
‎            position: absolute;
‎            top: 0;
‎            left: -100%;
‎            width: 50%;
‎            height: 100%;
‎            background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(220,38,38,0.25) 50%, rgba(255,255,255,0) 100%);
‎            transform: skewX(-25deg);
‎            animation: erp-sweep 6s infinite ease-in-out;
‎        }
‎
‎        @keyframes erp-sweep {
‎            0% { left: -100%; }
‎            50%, 100% { left: 150%; }
‎        }
‎
‎        /* Title & Meta Metadata */
‎        .erp-login-title {
‎            color: #ffffff;
‎            font-size: 22px;
‎            font-weight: 700;
‎            letter-spacing: 0.05em;
‎            text-transform: uppercase;
‎            text-align: center;
‎            margin: 0 0 8px 0;
‎            text-shadow: 0 0 10px rgba(255,255,255,0.1);
‎        }
‎
‎        .erp-login-subtitle {
‎            color: #9ca3af;
‎            font-size: 13px;
‎            text-align: center;
‎            margin: 0 0 35px 0;
‎            text-transform: uppercase;
‎            letter-spacing: 0.1em;
‎        }
‎
‎        /* Reactive Non-Intrusive Notification */
‎        .erp-response-banner {
‎            border-radius: 8px;
‎            padding: 12px 16px;
‎            margin-bottom: 24px;
‎            font-size: 13px;
‎            display: none;
‎            line-height: 1.4;
‎            transition: all 0.3s ease;
‎            box-sizing: border-box;
‎            border: 1px solid transparent;
‎        }
‎
‎        .erp-response-banner.banner-error {
‎            display: block;
‎            background: rgba(220, 38, 38, 0.15);
‎            border-color: rgba(220, 38, 38, 0.4);
‎            color: #fca5a5;
‎            box-shadow: inset 0 0 10px rgba(220, 38, 38, 0.1);
‎        }
‎
‎        .erp-response-banner.banner-success {
‎            display: block;
‎            background: rgba(16, 185, 129, 0.15);
‎            border-color: rgba(16, 185, 129, 0.4);
‎            color: #a7f3d0;
‎            box-shadow: inset 0 0 10px rgba(16, 185, 129, 0.1);
‎        }
‎
‎        /* Stylized Form Inputs */
‎        .erp-form-group {
‎            margin-bottom: 24px;
‎            position: relative;
‎        }
‎
‎        .erp-form-group label {
‎            display: block;
‎            color: #9ca3af;
‎            font-size: 11px;
‎            font-weight: 600;
‎            text-transform: uppercase;
‎            letter-spacing: 0.05em;
‎            margin-bottom: 8px;
‎        }
‎
‎        .erp-input-wrapper {
‎            position: relative;
‎        }
‎
‎        .erp-input-icon {
‎            position: absolute;
‎            left: 14px;
‎            top: 50%;
‎            transform: translateY(-50%);
‎            color: rgba(255, 255, 255, 0.4);
‎            pointer-events: none;
‎            display: flex;
‎            align-items: center;
‎        }
‎
‎        .erp-input-icon svg {
‎            width: 18px;
‎            height: 18px;
‎            transition: color 0.3s ease;
‎        }
‎
‎        .erp-form-control {
‎            width: 100%;
‎            background: rgba(0, 0, 0, 0.5);
‎            border: 1px solid rgba(255, 255, 255, 0.1);
‎            color: #ffffff;
‎            border-radius: 8px;
‎            padding: 14px 16px 14px 44px;
‎            font-size: 14px;
‎            line-height: 1.4;
‎            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
‎            outline: none;
‎            box-sizing: border-box;
‎        }
‎
‎        .erp-form-control:focus {
‎            border-color: var(--primary-red);
‎            background: rgba(13, 14, 18, 0.95);
‎            box-shadow: 0 0 12px rgba(220, 38, 38, 0.25);
‎        }
‎
‎        .erp-form-control:focus + .erp-input-icon svg {
‎            color: var(--hover-red);
‎        }
‎
‎        /* Remember Me & Aux Info Layout */
‎        .erp-form-utilities {
‎            display: flex;
‎            align-items: center;
‎            justify-content: space-between;
‎            margin-bottom: 30px;
‎        }
‎
‎        .erp-checkbox-group {
‎            display: flex;
‎            align-items: center;
‎            cursor: pointer;
‎        }
‎
‎        .erp-checkbox-group input {
‎            display: none;
‎        }
‎
‎        .erp-checkbox-indicator {
‎            width: 16px;
‎            height: 16px;
‎            border: 1px solid rgba(255,255,255,0.25);
‎            background: rgba(0,0,0,0.3);
‎            border-radius: 4px;
‎            margin-right: 8px;
‎            position: relative;
‎            transition: all 0.2s ease;
‎        }
‎
‎        .erp-checkbox-group input:checked + .erp-checkbox-indicator {
‎            background: var(--primary-red);
‎            border-color: var(--primary-red);
‎            box-shadow: 0 0 8px var(--primary-red);
‎        }
‎
‎        .erp-checkbox-group input:checked + .erp-checkbox-indicator::after {
‎            content: '';
‎            position: absolute;
‎            left: 5px;
‎            top: 2px;
‎            width: 4px;
‎            height: 8px;
‎            border: solid white;
‎            border-width: 0 2px 2px 0;
‎            transform: rotate(45deg);
‎        }
‎
‎        .erp-checkbox-group span {
‎            color: #9ca3af;
‎            font-size: 12px;
‎            user-select: none;
‎        }
‎
‎        .erp-forgot-link {
‎            color: #9ca3af;
‎            font-size: 12px;
‎            text-decoration: none;
‎            transition: color 0.2s ease;
‎        }
‎
‎        .erp-forgot-link:hover {
‎            color: var(--hover-red);
‎            text-decoration: underline;
‎        }
‎
‎        /* High-End Submitting State Action Button */
‎        .erp-submit-button {
‎            width: 100%;
‎            background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%);
‎            border: 1px solid rgba(220, 38, 38, 0.4);
‎            color: #ffffff;
‎            padding: 15px 24px;
‎            font-size: 13px;
‎            font-weight: 700;
‎            text-transform: uppercase;
‎            letter-spacing: 0.1em;
‎            border-radius: 8px;
‎            cursor: pointer;
‎            display: flex;
‎            align-items: center;
‎            justify-content: center;
‎            gap: 10px;
‎            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
‎            position: relative;
‎            overflow: hidden;
‎            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
‎        }
‎
‎        .erp-submit-button:hover:not(:disabled) {
‎            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
‎            border-color: rgba(239, 68, 68, 0.6);
‎            transform: translateY(-2px);
‎            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.5), 
‎                        0 0 10px rgba(220, 38, 38, 0.3);
‎        }
‎
‎        .erp-submit-button:active:not(:disabled) {
‎            transform: translateY(1px);
‎        }
‎
‎        .erp-submit-button:disabled {
‎            opacity: 0.6;
‎            cursor: not-allowed;
‎        }
‎
‎        .erp-submit-button svg {
‎            width: 18px;
‎            height: 18px;
‎            fill: currentColor;
‎            display: inline-block;
‎            transition: transform 0.3s ease;
‎        }
‎
‎        .erp-submit-button:hover:not(:disabled) svg {
‎            transform: translateX(3px);
‎        }
‎
‎        /* Loading Spinner */
‎        .submit-spinner {
‎            display: none;
‎            width: 18px;
‎            height: 18px;
‎            border: 2px solid rgba(255,255,255,0.3);
‎            border-radius: 50%;
‎            border-top-color: #ffffff;
‎            animation: m-spin 0.8s linear infinite;
‎        }
‎
‎        @keyframes m-spin {
‎            to { transform: rotate(360deg); }
‎        }
‎
‎        .erp-submit-button.is-loading svg {
‎            display: none;
‎        }
‎
‎        .erp-submit-button.is-loading .submit-spinner {
‎            display: block;
‎        }
‎
‎        /* Responsive */
‎        @media (max-width: 480px) {
‎            .erp-glass-card {
‎                padding: 30px 20px;
‎            }
‎        }
‎    </style>
‎</head>
‎<body class="wp-core-ui erp-login-template">
‎
‎    <!-- High-end dark abstract geometric background overlay -->
‎    <div class="erp-cyber-bg">
‎        <svg width="100%" height="100%">
‎            <!-- Sharply defined polygon geometric overlays to represent abstract metallic shards -->
‎            <polygon points="100,200 450,150 300,550" class="geometry-polygon" />
‎            <polygon points="900,100 1150,450 850,550" class="geometry-polygon" />
‎            <polygon points="150,750 400,900 100,950" class="geometry-polygon" style="opacity:0.35;" />
‎            <polygon points="1100,700 1400,600 1300,900" class="geometry-polygon" style="opacity:0.5;" />
‎        </svg>
‎    </div>
‎
‎    <div class="erp-login-wrapper">
‎        <div class="erp-glass-card" id="erp-login-card">
‎
‎            <!-- Brand Centered Logo Placeholder with Red Neon Glow Ring -->
‎            <div class="erp-logo-container">
‎                <!-- Native SVG Stylish JOGOOT.COM Face/Character Logo representing circular goggles/eyes with eyebrows -->
‎                <svg viewBox="0 0 200 80" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" id="jogoot-cyber-logo">
‎                    <!-- J O G -->
‎                    <text x="12" y="52" fill="#ffffff" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-weight="900" font-size="28" letter-spacing="1">JOG</text>
‎
‎                    <!-- Googly Cyber Eyes for O O -->
‎                    <!-- Left Eye Group -->
‎                    <circle cx="94" cy="45" r="16" fill="none" stroke="#dc2626" stroke-width="3.5" style="filter: drop-shadow(0 0 4px rgba(220, 38, 38, 0.8));" />
‎                    <circle id="left-pupil" cx="94" cy="45" r="4.5" fill="#ffffff" style="transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);" />
‎                    <!-- Right Eye Group -->
‎                    <circle cx="132" cy="45" r="16" fill="none" stroke="#dc2626" stroke-width="3.5" style="filter: drop-shadow(0 0 4px rgba(220, 38, 38, 0.8));" />
‎                    <circle id="right-pupil" cx="132" cy="45" r="4.5" fill="#ffffff" style="transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);" />
‎
‎                    <!-- Happy eye paths (hidden by default) -->
‎                    <path id="left-happy-eye" d="M 88 47 Q 94 38 100 47" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" style="opacity: 0; transition: all 0.25s ease;" />
‎                    <path id="right-happy-eye" d="M 126 47 Q 132 38 138 47" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" style="opacity: 0; transition: all 0.25s ease;" />
‎
‎                    <!-- Blushing cheeks (hidden by default) -->
‎                    <circle id="left-blush" cx="83" cy="58" r="4.5" fill="#f43f5e" opacity="0" style="filter: blur(1px); transition: all 0.3s ease;" />
‎                    <circle id="right-blush" cx="143" cy="58" r="4.5" fill="#f43f5e" opacity="0" style="filter: blur(1px); transition: all 0.3s ease;" />
‎
‎                    <!-- Eyebrows with modern curve -->
‎                    <path id="left-eyebrow" d="M 83 24 Q 94 18 105 25" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" style="transition: all 0.25s ease;" />
‎                    <path id="right-eyebrow" d="M 121 25 Q 132 18 143 24" fill="none" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" style="transition: all 0.25s 
