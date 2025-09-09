<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'AGROEMSE - Sistema de Gestión')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Laravel Vite Assets -->
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #1e3a8a;
            --primary-light: #3b82f6;
            --secondary-color: #f8fafc;
            --accent-color: #0ea5e9;
            --accent-green: #84cc16;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --white: #ffffff;
            --blue-gradient-start: #1e3a8a;
            --blue-gradient-end: #0ea5e9;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        .sidebar {
            background-color: var(--white);
            color: var(--text-primary);
            min-height: 100vh;
            border-right: 1px solid var(--border-color);
        }
        
        .sidebar .nav-link {
            color: var(--text-secondary);
            padding: 12px 20px;
            border-radius: 6px;
            margin: 2px 8px;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover {
            background-color: var(--secondary-color);
            color: var(--text-primary);
        }
        
        .sidebar .nav-link.active {
            background-color: var(--accent-color);
            color: var(--white);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 12px;
            font-size: 14px;
        }
        
        .main-content {
            background-color: var(--white);
            border-radius: 8px;
            margin: 16px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .header {
            background-color: var(--white);
            color: var(--text-primary);
            padding: 24px 32px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .content-area {
            padding: 32px;
            background-color: var(--secondary-color);
        }
        
        .card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--white);
            transition: all 0.2s ease;
        }
        
        .card:hover {
            border-color: var(--accent-light);
        }
        
        .btn {
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 500;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            font-size: 14px;
        }
        
        .btn-primary {
            background-color: var(--accent-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: var(--accent-light);
        .btn-primary:hover {
            background-color: var(--accent-light);
            color: var(--white);
        }
        
        .btn-light {
            background-color: var(--white);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        
        .btn-light:hover {
            background-color: var(--secondary-color);
            color: var(--text-primary);
        }
        
        .btn-outline-primary {
            background-color: transparent;
            color: var(--accent-color);
            border: 1px solid var(--accent-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--accent-color);
            color: var(--white);
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: var(--white);
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            color: var(--white);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: var(--white);
        }
        
        .form-control {
            border-radius: 6px;
            border: 1px solid var(--border-color);
            padding: 8px 12px;
            transition: all 0.2s ease;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        
        .table {
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .table thead th {
            background-color: var(--secondary-color);
            border: none;
            font-weight: 600;
            padding: 16px;
            font-size: 14px;
            color: var(--text-primary);
        }
        
        .table tbody td {
            padding: 16px;
            vertical-align: middle;
            border-color: var(--border-color);
            font-size: 14px;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 12px;
        }
        
        .stats-card {
            background-color: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 16px;
            transition: all 0.2s ease;
        }
        
        .stats-card:hover {
            border-color: var(--accent-color);
        }
        
        .stats-card h3 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--text-primary);
        }
        
        .stats-card p {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .stats-card small {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .stats-card i {
            font-size: 1.5rem;
            color: var(--accent-color);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                width: 250px;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s ease;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin: 10px;
            }
            
            .content-area {
                padding: 15px;
            }
        }
        
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            padding: 15px 20px;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    @yield('content')
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Mobile menu toggle
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
        }
        
        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
    
    @stack('scripts')
</body>
</html>
