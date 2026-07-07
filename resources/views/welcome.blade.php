<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'YLDA Association') }} - Financial Empowerment</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Styles -->
    <style>
        :root {
            --bg-color: #0b0d19;
            --card-bg: rgba(17, 24, 39, 0.6);
            --border-color: rgba(99, 102, 241, 0.15);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent-indigo: #6366f1;
            --accent-violet: #a855f7;
            --accent-cyan: #06b6d4;
            --nav-bg: rgba(11, 13, 25, 0.7);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-primary);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Gradient Orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(140px);
            z-index: -1;
            opacity: 0.45;
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
            top: 40%;
            left: -15%;
            width: 45vw;
            height: 45vw;
            background: radial-gradient(circle, var(--accent-violet) 0%, transparent 70%);
        }

        .orb-3 {
            bottom: -10%;
            right: -10%;
            width: 40vw;
            height: 40vw;
            background: radial-gradient(circle, var(--accent-cyan) 0%, transparent 70%);
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Header */
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: var(--nav-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-color);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, var(--accent-indigo) 50%, var(--accent-violet) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .logo i {
            -webkit-text-fill-color: initial;
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-violet));
            -webkit-background-clip: text;
            color: transparent;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
            align-items: center;
        }

        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            font-size: 0.95rem;
        }

        .nav-links a:hover {
            color: var(--text-primary);
        }

        .nav-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.95rem;
            gap: 0.5rem;
            cursor: pointer;
        }

        .btn-outline {
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.03);
        }

        .btn-outline:hover {
            border-color: var(--accent-indigo);
            background: rgba(99, 102, 241, 0.1);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-indigo) 0%, var(--accent-violet) 100%);
            color: #ffffff;
            border: none;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(99, 102, 241, 0.5);
        }

        .btn-lg {
            padding: 0.9rem 2.2rem;
            font-size: 1.05rem;
        }

        /* Hero Section */
        .hero {
            padding: 180px 0 100px 0;
            position: relative;
            text-align: center;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            color: #818cf8;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 2rem;
            animation: fadeInDown 0.8s ease;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.03em;
            margin-bottom: 1.5rem;
            background: linear-gradient(to right, #ffffff 30%, #c7d2fe 70%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: fadeInUp 0.8s ease;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            max-width: 700px;
            margin: 0 auto 2.5rem auto;
            animation: fadeInUp 1s ease;
        }

        .hero-ctas {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            animation: fadeInUp 1.2s ease;
        }

        /* Features Section */
        .features {
            padding: 80px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-tag {
            color: var(--accent-indigo);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-weight: 700;
            margin-bottom: 0.75rem;
            display: block;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 2.5rem 2rem;
            border-radius: 24px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.4s;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.1);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon-wrapper {
            width: 60px;
            height: 60px;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(99, 102, 241, 0.2);
            color: #818cf8;
            font-size: 1.5rem;
            transition: all 0.3s;
        }

        .feature-card:hover .feature-icon-wrapper {
            background: var(--accent-indigo);
            color: #fff;
            transform: scale(1.05);
        }

        .feature-title {
            font-size: 1.35rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .feature-desc {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        /* How it works */
        .process {
            padding: 80px 0;
            background: rgba(255, 255, 255, 0.01);
            border-top: 1px solid rgba(255, 255, 255, 0.02);
            border-bottom: 1px solid rgba(255, 255, 255, 0.02);
        }

        .process-timeline {
            display: flex;
            justify-content: space-between;
            gap: 2rem;
            margin-top: 3rem;
            position: relative;
        }

        .process-step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .step-number {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-violet));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 auto 1.5rem auto;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
            border: 3px solid var(--bg-color);
            z-index: 2;
            position: relative;
        }

        .step-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .step-desc {
            color: var(--text-secondary);
            font-size: 0.9rem;
            max-width: 250px;
            margin: 0 auto;
        }

        /* Stats Section */
        .stats {
            padding: 80px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2.5rem;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 32px;
            padding: 3rem 2rem;
            text-align: center;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .stat-number {
            font-size: 2.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, var(--accent-cyan) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 500;
        }

        /* CTA Banner */
        .cta-banner {
            padding: 100px 0;
            position: relative;
        }

        .cta-card {
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent), var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 32px;
            padding: 4rem 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .cta-desc {
            color: var(--text-secondary);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto 2.5rem auto;
        }

        /* Footer */
        footer {
            background: rgba(7, 9, 17, 0.9);
            border-top: 1px solid rgba(255, 255, 255, 0.03);
            padding: 5rem 0 2rem 0;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr repeat(3, 1fr);
            gap: 4rem;
            margin-bottom: 4rem;
        }

        .footer-logo-col {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .footer-desc {
            color: var(--text-secondary);
            font-size: 0.95rem;
            max-width: 320px;
        }

        .footer-socials {
            display: flex;
            gap: 1rem;
        }

        .footer-socials a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.07);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s;
        }

        .footer-socials a:hover {
            background: var(--accent-indigo);
            color: #fff;
            border-color: var(--accent-indigo);
            transform: translateY(-2px);
        }

        .footer-col-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: var(--text-primary);
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 2rem;
            color: rgba(255, 255, 255, 0.3);
            font-size: 0.85rem;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 991px) {
            .hero-title {
                font-size: 3rem;
            }
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 3rem;
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            .process-timeline {
                flex-direction: column;
                gap: 3rem;
            }
            .process-timeline::before {
                display: none;
            }
            .hero {
                padding: 140px 0 60px 0;
            }
            .hero-title {
                font-size: 2.5rem;
            }
            .section-title {
                font-size: 2rem;
            }
            .cta-card {
                padding: 3rem 1.5rem;
            }
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Gradient Background Orbs -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <!-- Header Navigation -->
    <header>
        <div class="container nav-container">
            <a href="/" class="logo">
                <i class="bi bi-wallet2"></i>
                YLDA Association
            </a>
            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#process">How it Works</a></li>
                <li><a href="#stats">Impact</a></li>
            </ul>
            <div class="nav-actions">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline">Sign In</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-primary">Register</a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-badge">
                <i class="bi bi-shield-check"></i> Secure & Managed Financial Association
            </div>
            <h1 class="hero-title">Association Finance,<br>Redefined for Everyone</h1>
            <p class="hero-subtitle">
                Grow your wealth collectively, access easy and soft financing, and enjoy seamless financial returns through our slot-based modern Association system.
            </p>
            <div class="hero-ctas">
                <a href="{{ route('login') }}" class="btn btn-primary btn-lg">Access Portal <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Value Proposition</span>
                <h2 class="section-title">Why Join Yusra Long-Term Development Association (YLDA)?</h2>
            </div>
            <div class="features-grid">
                <!-- Savings slots -->
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i class="bi bi-layers-half"></i>
                    </div>
                    <h3 class="feature-title">Flexible Savings Slots</h3>
                    <p class="feature-desc">
                        Register multiple active savings slots to easily fit your financial capacity. Track monthly collections effortlessly.
                    </p>
                </div>
                <!-- Easy Loans -->
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <h3 class="feature-title">Soft Association Financing</h3>
                    <p class="feature-desc">
                        Access fast financing with structured monthly profit and easy repayment pathways mapped directly to your savings history.
                    </p>
                </div>
                <!-- Investments -->
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <h3 class="feature-title">Strategic Investments</h3>
                    <p class="feature-desc">
                        Your funds are actively invested in secure assets to grow capital, backed by professional financial oversight.
                    </p>
                </div>
                <!-- Dividends -->
                <div class="feature-card">
                    <div class="feature-icon-wrapper">
                        <i class="bi bi-gift"></i>
                    </div>
                    <h3 class="feature-title">Annual Dividends</h3>
                    <p class="feature-desc">
                        Enjoy the fruits of collective growth. Earn and withdraw dividends generated annually from profit and capital gains.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Process Section -->
    <section id="process" class="process">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Process Flow</span>
                <h2 class="section-title">How It Works</h2>
            </div>
            <div class="process-timeline">
                <div class="process-step">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Admin Onboarding</h3>
                    <p class="step-desc">Get registered directly by the system admin with your next-of-kin safety details.</p>
                </div>
                <div class="process-step">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Save & Invest</h3>
                    <p class="step-desc">Commit monthly savings based on slots and watch the capital base expand securely.</p>
                </div>
                <div class="process-step">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Access Benefits</h3>
                    <p class="step-desc">Apply for soft financing, monitor savings balances, and receive yearly dividend shares directly.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section id="stats" class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number">500+</span>
                    <span class="stat-label">Active Members</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">₦25M+</span>
                    <span class="stat-label">Total Savings Pool</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">₦18M+</span>
                    <span class="stat-label">Financing Disbursed</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">100%</span>
                    <span class="stat-label">Trust & Security</span>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-banner">
        <div class="container">
            <div class="cta-card">
                <h2 class="cta-title">Ready to secure your financial future?</h2>
                <p class="cta-desc">
                    Join Yusra Long-Term Development Association today and explore the best way to save, borrow, and build wealth with a trusted community.
                </p>
                <a href="{{ route('login') }}" class="btn btn-primary btn-lg">Access Portal</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-logo-col">
                    <a href="/" class="logo">
                        <i class="bi bi-wallet2"></i>
                        YLDA Association
                    </a>
                    <p class="footer-desc">
                        Providing flexible, secure, and modern financial Association services for personal and collective growth.
                    </p>
                    <div class="footer-socials">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-twitter-x"></i></a>
                        <a href="#"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div>
                    <h4 class="footer-col-title">Service</h4>
                    <ul class="footer-links">
                        <li><a href="#features">Savings Slots</a></li>
                        <li><a href="#features">Association Financing</a></li>
                        <li><a href="#features">Investments</a></li>
                        <li><a href="#features">Annual Dividends</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="footer-col-title">Company</h4>
                    <ul class="footer-links">
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Terms & Conditions</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Support Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="footer-col-title">Member portal</h4>
                    <ul class="footer-links">
                        <li><a href="{{ route('login') }}">Sign In</a></li>

                        <li><a href="{{ route('login') }}">Treasurer login</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} YLDA Association. All rights reserved.</span>
                <span>Designed for excellence</span>
            </div>
        </div>
    </footer>
</body>
</html>
