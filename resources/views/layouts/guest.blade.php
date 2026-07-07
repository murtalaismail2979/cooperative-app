<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'YLDA Cooperative') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">

        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" media="print" onload="this.media='all'">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --bg-color: #0b0d19;
                --card-bg: rgba(17, 24, 39, 0.6);
                --border-color: rgba(99, 102, 241, 0.2);
                --text-primary: #f8fafc;
                --text-secondary: #94a3b8;
                --accent-indigo: #6366f1;
                --accent-violet: #a855f7;
                --accent-cyan: #06b6d4;
                --input-bg: rgba(15, 23, 42, 0.65);
            }

            * {
                box-sizing: border-box;
            }

            body {
                font-family: 'Outfit', sans-serif !important;
                background-color: var(--bg-color) !important;
                color: var(--text-primary) !important;
                min-height: 100vh;
                position: relative;
                overflow-x: hidden;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 2rem 1rem;
                margin: 0;
            }

            /* Gradient Orbs */
            .orb {
                position: absolute;
                border-radius: 50%;
                filter: blur(140px);
                z-index: -1;
                opacity: 0.4;
                pointer-events: none;
            }

            .orb-1 {
                top: -10%;
                right: -10%;
                width: 50vw;
                height: 50vw;
                background: radial-gradient(circle, var(--accent-indigo) 0%, transparent 70%);
            }

            .orb-2 {
                bottom: -10%;
                left: -10%;
                width: 45vw;
                height: 45vw;
                background: radial-gradient(circle, var(--accent-violet) 0%, transparent 70%);
            }

            .orb-3 {
                top: 40%;
                right: 30%;
                width: 30vw;
                height: 30vw;
                background: radial-gradient(circle, var(--accent-cyan) 0%, transparent 70%);
            }

            /* Wrapper */
            .auth-container {
                width: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                z-index: 10;
            }

            /* Logo Link */
            .logo {
                font-size: 2rem;
                font-weight: 800;
                background: linear-gradient(135deg, #fff 0%, var(--accent-indigo) 50%, var(--accent-violet) 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.6rem;
                text-decoration: none;
                margin-bottom: 0.25rem;
                transition: transform 0.3s ease;
            }

            .logo:hover {
                transform: scale(1.02);
            }

            .logo i {
                -webkit-text-fill-color: initial;
                background: linear-gradient(135deg, var(--accent-indigo), var(--accent-violet));
                -webkit-background-clip: text;
                color: transparent;
            }

            .tagline {
                color: var(--text-secondary);
                font-size: 0.95rem;
                font-weight: 400;
                margin-bottom: 2rem;
                text-align: center;
            }

            /* Glassmorphism Card */
            .auth-card {
                background: var(--card-bg);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid var(--border-color);
                width: 100%;
                max-width: 460px;
                border-radius: 24px;
                padding: 2.5rem;
                box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
                transition: max-width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            }

            body.route-register .auth-card {
                max-width: 860px;
            }

            /* Input Element Styles */
            .auth-card input[type="text"],
            .auth-card input[type="email"],
            .auth-card input[type="password"],
            .auth-card select,
            .auth-card textarea {
                background-color: var(--input-bg) !important;
                border: 1px solid var(--border-color) !important;
                color: var(--text-primary) !important;
                border-radius: 12px !important;
                padding: 0.75rem 1rem !important;
                font-size: 0.95rem !important;
                width: 100% !important;
                transition: all 0.3s ease !important;
                box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2) !important;
            }

            .auth-card input[type="text"]:focus,
            .auth-card input[type="email"]:focus,
            .auth-card input[type="password"]:focus,
            .auth-card select:focus,
            .auth-card textarea:focus {
                border-color: var(--accent-indigo) !important;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25), inset 0 2px 4px rgba(0, 0, 0, 0.1) !important;
                outline: none !important;
            }

            /* Input placeholder overrides */
            .auth-card ::placeholder {
                color: rgba(148, 163, 184, 0.5) !important;
            }

            /* Select arrow styling in dark mode */
            .auth-card select {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
                background-position: right 0.75rem center !important;
                background-repeat: no-repeat !important;
                background-size: 1.5em 1.5em !important;
                padding-right: 2.5rem !important;
                -webkit-appearance: none !important;
                -moz-appearance: none !important;
                appearance: none !important;
            }

            .auth-card select option {
                background-color: #111827 !important;
                color: var(--text-primary) !important;
            }

            /* Form Label Styles */
            .auth-card label, 
            .auth-card .form-label-custom {
                color: var(--text-secondary) !important;
                font-size: 0.85rem !important;
                font-weight: 600 !important;
                margin-bottom: 0.5rem !important;
                text-transform: uppercase !important;
                letter-spacing: 0.05em !important;
                display: inline-block !important;
            }

            /* Form Header Styles inside Card */
            .auth-card h3 {
                color: var(--text-primary) !important;
                font-size: 1.15rem !important;
                font-weight: 700 !important;
                letter-spacing: -0.01em !important;
            }

            /* Buttons Styles */
            .auth-card button[type="submit"],
            .auth-card .btn-primary-custom {
                background: linear-gradient(135deg, var(--accent-indigo) 0%, var(--accent-violet) 100%) !important;
                color: #ffffff !important;
                border: none !important;
                border-radius: 50px !important;
                padding: 0.8rem 2rem !important;
                font-weight: 600 !important;
                font-size: 0.95rem !important;
                cursor: pointer !important;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35) !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 0.5rem !important;
            }

            .auth-card button[type="submit"]:hover,
            .auth-card .btn-primary-custom:hover {
                transform: translateY(-2px) !important;
                box-shadow: 0 6px 20px rgba(99, 102, 241, 0.55) !important;
            }

            .auth-card button[type="submit"]:active,
            .auth-card .btn-primary-custom:active {
                transform: translateY(0) !important;
            }

            /* Form Links styling */
            .auth-card a,
            .auth-card .form-link-custom {
                color: var(--text-secondary) !important;
                text-decoration: none !important;
                font-size: 0.9rem !important;
                transition: color 0.2s ease !important;
                font-weight: 500 !important;
            }

            .auth-card a:hover,
            .auth-card .form-link-custom:hover {
                color: var(--text-primary) !important;
                text-decoration: underline !important;
            }

            /* Validation Errors overrides */
            .auth-card ul.text-red-600 {
                background: rgba(239, 68, 68, 0.1) !important;
                border: 1px solid rgba(239, 68, 68, 0.25) !important;
                padding: 0.75rem 1rem !important;
                border-radius: 12px !important;
                color: #f87171 !important;
                list-style-type: none !important;
                margin-bottom: 1.5rem !important;
            }

            .auth-card ul.text-red-600 li {
                font-size: 0.85rem !important;
                font-weight: 500 !important;
            }

            /* Session Status updates */
            .auth-card .text-green-600 {
                background: rgba(34, 197, 94, 0.1) !important;
                border: 1px solid rgba(34, 197, 94, 0.25) !important;
                padding: 0.75rem 1rem !important;
                border-radius: 12px !important;
                color: #4ade80 !important;
                font-size: 0.875rem !important;
                font-weight: 500 !important;
                margin-bottom: 1.5rem !important;
            }

            /* Custom Grid for Multi-column Layouts */
            .register-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1.75rem 2rem;
            }

            .register-col {
                display: flex;
                flex-direction: column;
                gap: 1.25rem;
            }

            .register-footer {
                grid-column: span 2;
                border-top: 1px solid rgba(255, 255, 255, 0.08);
                padding-top: 1.5rem;
                margin-top: 1rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            /* Checkbox */
            .auth-card input[type="checkbox"] {
                background-color: var(--input-bg) !important;
                border: 1px solid var(--border-color) !important;
                border-radius: 4px !important;
                color: var(--accent-indigo) !important;
                width: 1.15rem !important;
                height: 1.15rem !important;
                cursor: pointer !important;
                transition: all 0.2s ease !important;
            }

            .auth-card input[type="checkbox"]:focus {
                ring-color: var(--accent-indigo) !important;
                box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.3) !important;
            }

            .auth-card .text-gray-600,
            .auth-card .text-sm.text-gray-600 {
                color: var(--text-secondary) !important;
            }

            .auth-card .text-gray-900,
            .auth-card .text-gray-800 {
                color: var(--text-primary) !important;
            }

            @media (max-width: 768px) {
                body.route-register .auth-card {
                    max-width: 460px;
                }
                .register-grid {
                    grid-template-columns: 1fr;
                    gap: 1.25rem;
                }
                .register-footer {
                    grid-column: span 1;
                    flex-direction: column;
                    gap: 1.25rem;
                    align-items: center;
                    text-align: center;
                }
            }
        </style>
    </head>
    <body class="antialiased {{ request()->routeIs('register') ? 'route-register' : '' }}">
        <!-- Gradient Background Orbs -->
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>

        <div class="auth-container">
            <a href="/" class="logo">
                <i class="bi bi-wallet2"></i>
                YLDA Association
            </a>
            <p class="tagline">Financial Empowerment Cooperative</p>

            <div class="auth-card">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
