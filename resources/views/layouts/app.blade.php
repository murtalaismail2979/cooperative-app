<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $currentCoop = auth()->check() ? auth()->user()->cooperative : null;
        $coopTitle = $currentCoop ? ($currentCoop->slug ?: $currentCoop->name) : config('app.name', 'MyCoopSuite');
    @endphp
    <title>@yield('title', 'Dashboard') - {{ $coopTitle }}</title>
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
        .card { border-radius: 12px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.03); overflow: hidden; }
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

        /* Prevent select and flex items from overflowing containers */
        .form-select, .form-control { max-width: 100%; text-overflow: ellipsis; }
        .input-group > .form-select, .input-group > .form-control, .input-group > div { min-width: 0; }

        /* Pagination & SVG constraints */
        svg { max-width: 100%; max-height: 100%; }
        .pagination { margin-bottom: 0; }
        .pagination svg { width: 1.25em; height: 1.25em; vertical-align: middle; }
        .pagination .page-link { border-radius: 6px; margin: 0 2px; color: #4f46e5; border: 1px solid #e2e8f0; }
        .pagination .page-item.active .page-link { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border-color: #4f46e5; color: #fff; }

        /* Badge contrast enhancement */
        .badge.bg-primary, .badge.bg-success, .badge.bg-danger, .badge.bg-info, .badge.bg-warning, .badge.bg-secondary {
            color: #ffffff !important;
            font-weight: 600;
        }

        /* Spacing utilities compatibility (me-*, ms-*, gap-*) */
        .me-1 { margin-right: 0.25rem !important; }
        .me-2 { margin-right: 0.5rem !important; }
        .me-3 { margin-right: 1rem !important; }
        .me-4 { margin-right: 1.5rem !important; }
        .ms-1 { margin-left: 0.25rem !important; }
        .ms-2 { margin-left: 0.5rem !important; }
        .ms-3 { margin-left: 1rem !important; }
        .ms-4 { margin-left: 1.5rem !important; }
        .gap-1 { gap: 0.25rem !important; }
        .gap-2 { gap: 0.5rem !important; }
        .gap-3 { gap: 1rem !important; }
        .gap-4 { gap: 1.5rem !important; }
    </style>
</head>
<body>
    <!-- Glowing top loader -->
    <div id="top-loader" style="position: fixed; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #6366f1, #a855f7, #06b6d4); z-index: 9999; transform: translateX(-100%); transition: transform 0.25s ease; box-shadow: 0 0 8px rgba(99, 102, 241, 0.6);"></div>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 sidebar">
                <div class="brand text-truncate d-flex align-items-center" title="{{ auth()->user()?->cooperative?->name ?? config('app.name', 'MyCoopSuite') }}">
                    @if(!auth()->check() || auth()->user()->isSuperAdmin() || !auth()->user()->cooperative)
                        <a href="{{ auth()->check() ? route('admin.dashboard') : url('/') }}" class="d-inline-flex align-items-center text-decoration-none">
                            <img src="{{ asset('images/mycoopsuite-logo.png') }}" alt="{{ config('app.name', 'MyCoopSuite') }}" style="height: 38px; max-width: 100%; object-fit: contain;">
                        </a>
                    @else
                        <span class="d-inline-flex align-items-center"><i class="bi bi-building me-2"></i> {{ auth()->user()->cooperative->slug ?? config('app.name', 'MyCoopSuite') }}</span>
                    @endif
                </div>
                <nav class="p-3">
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="nav-link d-block {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                            @if(auth()->user()->isSuperAdmin())
                                <a href="{{ route('admin.cooperatives.index') }}" class="nav-link d-block {{ request()->routeIs('admin.cooperatives.*') ? 'active' : '' }}">
                                    <i class="bi bi-buildings"></i> Cooperatives
                                </a>
                                <a href="{{ route('admin.platform-charges.index') }}" class="nav-link d-block {{ request()->routeIs('admin.platform-charges.*') ? 'active' : '' }}">
                                    <i class="bi bi-cash-stack"></i> Platform Charges
                                </a>
                            @endif
                            <a href="{{ route('admin.members.index') }}" class="nav-link d-block {{ request()->routeIs('admin.members.*') ? 'active' : '' }}">
                                <i class="bi bi-people"></i> Manage Users
                            </a>
                            @if(!auth()->user()->isSuperAdmin())
                                <a href="{{ route('admin.slots.index') }}" class="nav-link d-block {{ request()->routeIs('admin.slots.*') ? 'active' : '' }}">
                                    <i class="bi bi-grid-3x3-gap"></i> Slot Settings
                                </a>
                            @endif
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
                            <a href="{{ route('profile.edit') }}" class="nav-link d-block {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
                                <i class="bi bi-person"></i> My Profile
                            </a>
                            @if(auth()->user()->isApprovedMembership())
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
                    <div class="d-flex align-items-center flex-wrap" style="gap: 16px;">
                        @auth
                            @if(auth()->user()->isSuperAdmin())
                                <form method="GET" action="{{ request()->url() }}" class="me-3 mr-3 d-inline-block" style="margin-right: 14px;">
                                    @foreach(request()->except('cooperative_id') as $key => $val)
                                        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                                    @endforeach
                                    <select name="cooperative_id" onchange="this.form.submit()" class="form-select form-select-sm border-primary text-primary fw-bold shadow-sm" style="min-width: 200px;">
                                        <option value="">🏢 All Cooperatives</option>
                                        @foreach(\App\Models\Cooperative::all() as $coop)
                                            <option value="{{ $coop->id }}" {{ request('cooperative_id') == $coop->id ? 'selected' : '' }}>
                                                {{ $coop->name }} ({{ $coop->slug }})
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            @endif
                        @endauth
                        <a href="{{ route('password.recovery') }}" class="btn btn-outline-primary btn-sm me-3 mr-3 shadow-sm" style="margin-right: 16px;">
                            <i class="bi bi-shield-lock me-1 mr-1" style="margin-right: 4px;"></i> Retrieve / Reset Password
                        </a>
                        @auth
                        <a href="{{ route('profile.edit') }}" class="text-decoration-none text-muted profile-link d-inline-flex align-items-center" style="gap: 8px;">
                            <i class="bi bi-person-circle me-1 mr-1" style="font-size: 1.15rem; margin-right: 6px;"></i> 
                            <span class="fw-semibold text-dark">{{ auth()->user()->name ?? '' }}</span>
                            @if(!empty(auth()->user()->member_code))
                                <span class="badge bg-primary ms-3 ml-3 shadow-sm" style="margin-left: 12px; padding: 6px 12px; font-size: 0.82rem; letter-spacing: 0.5px;">{{ auth()->user()->member_code }}</span>
                            @elseif(auth()->user()?->isPendingMembership())
                                <span class="badge bg-warning text-dark ms-3 ml-3 shadow-sm" style="margin-left: 12px; padding: 6px 10px;"><i class="bi bi-clock me-1"></i> Pending Approval</span>
                            @endif
                        </a>
                        @endauth
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show">
                        {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        {{ session('error') }}
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
            let searchTimeout = null;
            let currentFetchController = null;

            function buildFormUrl(form) {
                const action = form.getAttribute('action') || window.location.pathname;
                const formData = new FormData(form);
                const params = new URLSearchParams();
                for (const [key, value] of formData.entries()) {
                    if (value !== null && value !== '') {
                        params.append(key, value);
                    }
                }
                const queryString = params.toString();
                return action + (queryString ? '?' + queryString : '');
            }

            function executeAjaxSearch(url, activeInputId, selectionStart, selectionEnd) {
                const mainContent = document.querySelector('.main-content');
                if (mainContent) mainContent.classList.add('loading');

                const loader = document.getElementById('top-loader');
                if (loader) {
                    loader.style.display = 'block';
                    loader.style.transform = 'translateX(-30%)';
                }

                if (currentFetchController) {
                    currentFetchController.abort();
                }
                currentFetchController = new AbortController();

                fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: currentFetchController.signal
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response failed');
                    return response.text();
                })
                .then(html => {
                    const parser = new DOMParser();
                    const newDoc = parser.parseFromString(html, 'text/html');
                    const newMainContent = newDoc.querySelector('.main-content');

                    if (mainContent && newMainContent) {
                        mainContent.innerHTML = newMainContent.innerHTML;
                        window.history.replaceState(null, '', url);
                    }

                    if (activeInputId) {
                        const restoredInput = document.getElementById(activeInputId);
                        if (restoredInput) {
                            restoredInput.focus();
                            const len = restoredInput.value ? restoredInput.value.length : 0;
                            const start = typeof selectionStart === 'number' ? selectionStart : len;
                            const end = typeof selectionEnd === 'number' ? selectionEnd : len;
                            try { restoredInput.setSelectionRange(start, end); } catch (e) {}
                        }
                    }

                    initAutoSearch();
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        console.error('Search error:', err);
                    }
                })
                .finally(() => {
                    if (mainContent) mainContent.classList.remove('loading');
                    if (loader) {
                        loader.style.transform = 'translateX(0%)';
                        setTimeout(() => { loader.style.display = 'none'; }, 200);
                    }
                });
            }

            function initAutoSearch() {
                const inputs = document.querySelectorAll('.auto-search');
                inputs.forEach(input => {
                    if (input.dataset.autoSearchBound) return;
                    input.dataset.autoSearchBound = 'true';

                    if (localStorage.getItem('focused_search_id') === input.id) {
                        input.focus();
                        const len = input.value ? input.value.length : 0;
                        input.setSelectionRange(len, len);
                        localStorage.removeItem('focused_search_id');
                    }

                    input.addEventListener('input', function() {
                        const inputId = this.id;
                        const start = this.selectionStart;
                        const end = this.selectionEnd;
                        const form = this.form;

                        clearTimeout(searchTimeout);
                        searchTimeout = setTimeout(() => {
                            if (form) {
                                const url = buildFormUrl(form);
                                executeAjaxSearch(url, inputId, start, end);
                            }
                        }, 250);
                    });
                });

                // Attach to search forms
                const searchForms = document.querySelectorAll('form');
                searchForms.forEach(form => {
                    if (!form.querySelector('.auto-search')) return;
                    if (form.dataset.formSearchBound) return;
                    form.dataset.formSearchBound = 'true';

                    form.querySelectorAll('select, input[type="date"]').forEach(select => {
                        select.removeAttribute('onchange');
                        select.addEventListener('change', function(e) {
                            const activeInput = form.querySelector('.auto-search');
                            const activeId = activeInput ? activeInput.id : null;
                            const url = buildFormUrl(form);
                            executeAjaxSearch(url, activeId);
                        });
                    });

                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        clearTimeout(searchTimeout);
                        const activeInput = form.querySelector('.auto-search');
                        const activeId = activeInput ? activeInput.id : null;
                        const url = buildFormUrl(form);
                        executeAjaxSearch(url, activeId);
                    });
                });

                // Intercept pagination links inside main-content for instant page transitions
                const mainContent = document.querySelector('.main-content');
                if (mainContent && !mainContent.dataset.paginationBound) {
                    mainContent.dataset.paginationBound = 'true';
                    mainContent.addEventListener('click', function(e) {
                        const pageLink = e.target.closest('.pagination a, .page-link');
                        if (pageLink && pageLink.href && !pageLink.href.startsWith('#') && !pageLink.href.startsWith('javascript:')) {
                            e.preventDefault();
                            const activeInput = document.querySelector('.auto-search:focus') || document.querySelector('.auto-search');
                            const activeId = activeInput ? activeInput.id : null;
                            executeAjaxSearch(pageLink.href, activeId);
                        }
                    });
                }
            }

            initAutoSearch();

            // Real-time phone input filtering to prevent typing/pasting invalid characters
            document.querySelectorAll('input[type="tel"], input[name="phone"], input[name="nok_phone"], input[name="cooperative_phone"]').forEach(function (input) {
                input.addEventListener('input', function () {
                    var cleaned = this.value.replace(/[^0-9+\s\-()]/g, '');
                    if (cleaned.indexOf('+') > 0) {
                        cleaned = cleaned.charAt(0) + cleaned.slice(1).replace(/\+/g, '');
                    }
                    if (this.value !== cleaned) {
                        this.value = cleaned;
                    }
                });
            });
        });
    </script>
    @stack('scripts')
</body>
</html>