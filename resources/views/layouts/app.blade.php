<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'YLDA Cooperative') - {{ config('app.name', 'YLDA Cooperative') }}</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" media="print" onload="this.media='all'">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; color: #1e293b; }
        
        /* Modern Sidebar styling */
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%); box-shadow: 4px 0 10px rgba(0,0,0,0.05); }
        .sidebar .brand { padding: 1.5rem 1rem; color: #f8fafc; font-size: 1.3rem; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.06); letter-spacing: 0.5px; }
        .sidebar .nav-link { color: rgba(248, 250, 252, 0.7); padding: .8rem 1rem; border-radius: .5rem; margin: .2rem 0; transition: all 0.2s ease; }
        .sidebar .nav-link:hover { color: #fff; background: rgba(255,255,255,.08); transform: translateX(3px); }
        .sidebar .nav-link.active { color: #fff; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); }
        .sidebar .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        
        /* Main Content and stats styling */
        .main-content { padding: 30px; }
        .stat-card { border-radius: 12px; border: none; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05) !important; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.15) !important; }
        
        /* Overriding plain Bootstrap colors with premium gradients */
        .stat-card.bg-primary { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important; }
        .stat-card.bg-success { background: linear-gradient(135deg, #10b981 0%, #047857 100%) !important; }
        .stat-card.bg-warning { background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%) !important; }
        .stat-card.bg-info { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%) !important; }
        
        .stat-card .icon { font-size: 2.2rem; opacity: .25; position: absolute; right: 15px; top: 15px; transition: transform 0.3s ease; }
        .stat-card:hover .icon { transform: scale(1.1) rotate(5deg); }
        .stat-card .card-body { position: relative; padding: 1.25rem; }
        .stat-label { font-size: .8rem; text-transform: uppercase; letter-spacing: 0.75px; font-weight: 600; opacity: 0.85; }
        .stat-value { font-size: 1.9rem; font-weight: 700; margin-top: 5px; }
        
        .page-header { padding: 1rem 0; margin-bottom: 1.5rem; border-bottom: 2px solid #e2e8f0; }
        
        /* Subtle transition for content loading state */
        .main-content.loading { opacity: 0.5; pointer-events: none; transition: opacity 0.15s ease; }
        
        /* General Dashboard card & button polish */
        .card { border-radius: 12px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.03); }
        .card-header { background-color: #fff; border-bottom: 1px solid rgba(0,0,0,0.05); font-weight: 600; }
        .btn { border-radius: 8px; font-weight: 500; transition: all 0.2s ease; }
        .btn-primary { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2); }
        .btn-primary:hover { background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); transform: translateY(-1px); box-shadow: 0 6px 15px rgba(99, 102, 241, 0.3); }
        .btn-outline-primary { color: #4f46e5; border-color: #6366f1; }
        .btn-outline-primary:hover { background-color: #4f46e5; border-color: #4f46e5; }
        .table { border-radius: 8px; overflow: hidden; }
        .table th { font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
        .profile-link { transition: opacity 0.2s ease; }
        .profile-link:hover { opacity: 0.8; }
    </style>
</head>
<body>
    <!-- Glowing top loader -->
    <div id="top-loader" style="position: fixed; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #6366f1, #a855f7, #06b6d4); z-index: 9999; transform: translateX(-100%); transition: transform 0.25s ease; box-shadow: 0 0 8px rgba(99, 102, 241, 0.6);"></div>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 sidebar">
                <div class="brand">
                    <i class="bi bi-building"></i> YLDA Coop
                </div>
                <nav class="p-3">
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="nav-link d-block {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                            <a href="{{ route('admin.members.index') }}" class="nav-link d-block {{ request()->routeIs('admin.members.*') ? 'active' : '' }}">
                                <i class="bi bi-people"></i> Members
                            </a>
                            <a href="{{ route('admin.registration-fees.index') }}" class="nav-link d-block {{ request()->routeIs('admin.registration-fees.*') ? 'active' : '' }}">
                                <i class="bi bi-card-checklist"></i> Registration Fees
                            </a>
                            <a href="{{ route('admin.savings.index') }}" class="nav-link d-block {{ request()->routeIs('admin.savings.*') ? 'active' : '' }}">
                                <i class="bi bi-piggy-bank"></i> Savings
                            </a>
                            <a href="{{ route('admin.running-charges.index') }}" class="nav-link d-block {{ request()->routeIs('admin.running-charges.*') ? 'active' : '' }}">
                                <i class="bi bi-receipt"></i> Charges
                            </a>
                            <a href="{{ route('admin.loans.index') }}" class="nav-link d-block {{ request()->routeIs('admin.loans.*') ? 'active' : '' }}">
                                <i class="bi bi-cash-stack"></i> Financing
                            </a>
                            <a href="{{ route('admin.investments.index') }}" class="nav-link d-block {{ request()->routeIs('admin.investments.*') ? 'active' : '' }}">
                                <i class="bi bi-graph-up-arrow"></i> Investments
                            </a>
                            <a href="{{ route('admin.expenses.index') }}" class="nav-link d-block {{ request()->routeIs('admin.expenses.*') ? 'active' : '' }}">
                                <i class="bi bi-cart"></i> Expenses
                            </a>
                            <a href="{{ route('admin.dividends.index') }}" class="nav-link d-block {{ request()->routeIs('admin.dividends.index') || request()->routeIs('admin.dividends.create') || request()->routeIs('admin.dividends.show') ? 'active' : '' }}">
                                <i class="bi bi-gift"></i> Dividends
                            </a>
                            <a href="{{ route('admin.dividends.reconciliation.index') }}" class="nav-link d-block {{ request()->routeIs('admin.dividends.reconciliation.*') ? 'active' : '' }}">
                                <i class="bi bi-calculator"></i> Annual Reconciliation
                            </a>
                            <hr class="border-light">
                            <a href="{{ route('admin.reports.savings') }}" class="nav-link d-block {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-bar-graph"></i> Reports
                            </a>
                            <a href="{{ route('admin.batch-upload.index') }}" class="nav-link d-block {{ request()->routeIs('admin.batch-upload.*') ? 'active' : '' }}">
                                <i class="bi bi-cloud-arrow-up"></i> Batch Upload
                            </a>
                        @elseif(auth()->user()->isTreasurer())
                            <a href="{{ route('treasurer.dashboard') }}" class="nav-link d-block {{ request()->routeIs('treasurer.dashboard') ? 'active' : '' }}">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                            <a href="{{ route('treasurer.registration-fees.index') }}" class="nav-link d-block {{ request()->routeIs('treasurer.registration-fees.*') ? 'active' : '' }}">
                                <i class="bi bi-card-checklist"></i> Registration Fees
                            </a>
                            <a href="{{ route('treasurer.savings.index') }}" class="nav-link d-block {{ request()->routeIs('treasurer.savings.*') ? 'active' : '' }}">
                                <i class="bi bi-piggy-bank"></i> Record Savings
                            </a>
                            <a href="{{ route('treasurer.running-charges.index') }}" class="nav-link d-block {{ request()->routeIs('treasurer.running-charges.*') ? 'active' : '' }}">
                                <i class="bi bi-receipt"></i> Charges
                            </a>
                            <a href="{{ route('treasurer.loans.index') }}" class="nav-link d-block {{ request()->routeIs('treasurer.loans.*') ? 'active' : '' }}">
                                <i class="bi bi-cash-stack"></i> Financing Repayments
                            </a>
                            <a href="{{ route('treasurer.investments.index') }}" class="nav-link d-block {{ request()->routeIs('treasurer.investments.*') ? 'active' : '' }}">
                                <i class="bi bi-graph-up-arrow"></i> Investments
                            </a>
                            <a href="{{ route('treasurer.expenses.index') }}" class="nav-link d-block {{ request()->routeIs('treasurer.expenses.*') ? 'active' : '' }}">
                                <i class="bi bi-cart"></i> Expenses
                            </a>
                            <a href="{{ route('admin.batch-upload.index') }}" class="nav-link d-block {{ request()->routeIs('admin.batch-upload.*') ? 'active' : '' }}">
                                <i class="bi bi-cloud-arrow-up"></i> Batch Upload
                            </a>
                        @else
                            <a href="{{ route('member.dashboard') }}" class="nav-link d-block {{ request()->routeIs('member.dashboard') ? 'active' : '' }}">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                            <a href="{{ route('member.registration-fee.index') }}" class="nav-link d-block {{ request()->routeIs('member.registration-fee.*') ? 'active' : '' }}">
                                <i class="bi bi-card-checklist"></i> Registration Fee
                            </a>
                            <a href="{{ route('member.savings') }}" class="nav-link d-block {{ request()->routeIs('member.savings') ? 'active' : '' }}">
                                <i class="bi bi-piggy-bank"></i> My Savings
                            </a>
                            <a href="{{ route('member.loans') }}" class="nav-link d-block {{ request()->routeIs('member.loans') ? 'active' : '' }}">
                                <i class="bi bi-cash-stack"></i> My Financing
                            </a>
                            <a href="{{ route('member.dividends') }}" class="nav-link d-block {{ request()->routeIs('member.dividends') ? 'active' : '' }}">
                                <i class="bi bi-gift"></i> Dividends
                            </a>
                        @endif
                        <a href="{{ route('password.recovery') }}" class="nav-link d-block {{ request()->routeIs('password.recovery*') ? 'active' : '' }}">
                            <i class="bi bi-shield-lock"></i> Retrieve Password
                        </a>
                        <hr class="border-light">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="nav-link d-block w-100 text-start border-0 bg-transparent">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    @endauth
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        @yield('page-title', 'Dashboard')
                    </div>
                    <div class="d-flex align-items-center">
                        <a href="{{ route('password.recovery') }}" class="btn btn-outline-primary btn-sm me-3 shadow-sm">
                            <i class="bi bi-shield-lock me-1"></i> Retrieve / Reset Password
                        </a>
                        <a href="{{ route('profile.edit') }}" class="text-decoration-none text-muted profile-link d-inline-flex align-items-center">
                            <i class="bi bi-person-circle me-1"></i> 
                            <span>{{ auth()->user()->name ?? '' }}</span>
                            @if(!empty(auth()->user()->member_code))
                                <span class="badge bg-primary ms-2">{{ auth()->user()->member_code }}</span>
                            @endif
                        </a>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.auto-search');
            inputs.forEach(input => {
                let timeout = null;
                
                // Restore focus and cursor position at the end of the text
                if (localStorage.getItem('focused_search_id') === input.id) {
                    input.focus();
                    const len = input.value ? input.value.length : 0;
                    input.setSelectionRange(len, len);
                }

                input.addEventListener('input', function() {
                    localStorage.setItem('focused_search_id', this.id);
                    
                    const mainContent = document.querySelector('.main-content');
                    if (mainContent) {
                        mainContent.classList.add('loading');
                    }

                    const loader = document.getElementById('top-loader');
                    if (loader) {
                        loader.style.transform = 'translateX(-30%)';
                        let progress = -30;
                        const interval = setInterval(() => {
                            if (progress < -10) {
                                progress += 3;
                                loader.style.transform = `translateX(${progress}%)`;
                            } else {
                                clearInterval(interval);
                            }
                        }, 50);
                    }

                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        this.form.submit();
                    }, 300); // 300ms debounce for snappier response
                });

                // Clear ID storage on manual form submission
                input.form?.addEventListener('submit', function() {
                    localStorage.removeItem('focused_search_id');
                });
            });
        });
    </script>
    @stack('scripts')
</body>
</html>