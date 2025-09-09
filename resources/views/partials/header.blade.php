<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'AGROEMSE - Agro Empaques y Servicios')</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Scripts -->

    
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #2c3e50;
            color: white;
            position: relative;
        }
        .sidebar .nav-link {
            color: #bdc3c7;
            padding: 12px 20px;
            border-radius: 5px;
            margin: 2px 10px;
            display: flex;
            align-items: center;
        }
        .sidebar .nav-link:hover {
            background-color: #34495e;
            color: white;
            text-decoration: none;
        }
        .sidebar .nav-link.active {
            background-color: #3498db;
            color: white;
        }
        .brand-section {
            background-color: #1a252f;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #34495e;
        }
        .brand-icon {
            width: 40px;
            height: 40px;
            background-color: #2ecc71;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        .main-content {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .badge-count {
            background-color: #2ecc71;
            color: white;
            font-size: 0.75em;
            padding: 2px 6px;
            border-radius: 10px;
        }
        .section-title {
            color: #7f8c8d;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
            padding: 15px 20px 5px 20px;
            margin-bottom: 0;
        }
        .user-section {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
        }
        .user-avatar {
            width: 35px;
            height: 35px;
            background-color: #3498db;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }
        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }
        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }
        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }
        .text-gray-800 {
            color: #5a5c69 !important;
        }
        .text-gray-300 {
            color: #dddfeb !important;
        }
        .shadow {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
        }
    </style>
</head>
<body>
