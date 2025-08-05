@include('partials.header')

<div id="app">
    <div class="container-fluid">
        <div class="row">
            @include('partials.sidebar')
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="container-fluid py-4">
                    <!-- Page Header -->
                    @hasSection('page-header')
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h1 class="h3 mb-0">@yield('page-title', 'Dashboard')</h1>
                                        @hasSection('breadcrumb')
                                            <nav aria-label="breadcrumb">
                                                <ol class="breadcrumb mb-0">
                                                    @yield('breadcrumb')
                                                </ol>
                                            </nav>
                                        @endif
                                    </div>
                                    <div>
                                        @yield('page-actions')
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Alerts -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Main Content Area -->
                    @yield('content')
                </div>

@include('partials.footer')
