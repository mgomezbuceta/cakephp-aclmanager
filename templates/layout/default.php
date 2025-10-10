<?php
/**
 * Authorization Manager Layout
 *
 * @var \Cake\View\View $this
 */

$title = $this->fetch('title');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ? h($title) . ' - ' : '' ?><?= __d('acl_manager', 'Authorization Manager') ?></title>
    <?= $this->Html->meta('icon') ?>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>

    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-bg: #ecf0f1;
            --border-color: #bdc3c7;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: #2c3e50;
        }

        /* Navbar */
        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 0.75rem 0;
        }

        .navbar-brand {
            font-weight: 600;
            color: white !important;
            font-size: 1.25rem;
        }

        .navbar-brand i {
            margin-right: 0.5rem;
        }

        .nav-link {
            color: rgba(255,255,255,0.85) !important;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: white !important;
            background-color: var(--secondary-color);
            border-radius: 0.25rem;
        }

        .nav-link i {
            margin-right: 0.5rem;
        }

        /* Main Container */
        .main-container {
            padding: 2rem 0;
            min-height: calc(100vh - 180px);
        }

        /* Cards */
        .card {
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            background-color: white;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-bottom: none;
            padding: 1rem 1.25rem;
            font-weight: 600;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Buttons */
        .btn {
            border-radius: 0.375rem;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        /* Tables */
        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: var(--light-bg);
            border-bottom: 2px solid var(--border-color);
            font-weight: 600;
            color: var(--primary-color);
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,0.02);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }

        /* Badges */
        .badge {
            padding: 0.375rem 0.75rem;
            font-weight: 500;
        }

        /* Flash Messages */
        .flash-messages {
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .alert {
            border-radius: 0.375rem;
            border: none;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .page-header h1 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .page-header .lead {
            color: #7f8c8d;
        }

        /* Footer */
        footer {
            background-color: var(--primary-color);
            color: rgba(255,255,255,0.8);
            padding: 1.5rem 0;
            margin-top: 3rem;
        }

        footer a {
            color: var(--accent-color);
            text-decoration: none;
        }

        footer a:hover {
            color: #5dade2;
            text-decoration: underline;
        }

        /* Form Controls */
        .form-control, .form-select {
            border-radius: 0.375rem;
            border: 1px solid var(--border-color);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem 0;
            }

            .navbar-brand {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= $this->Url->build(['plugin' => 'AclManager', 'controller' => 'Permissions', 'action' => 'index']) ?>">
                <i class="fas fa-shield-alt"></i> <?= __d('acl_manager', 'Authorization Manager') ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->Url->build(['plugin' => 'AclManager', 'controller' => 'Permissions', 'action' => 'index']) ?>">
                            <i class="fas fa-home"></i> <?= __d('acl_manager', 'Dashboard') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->Url->build(['plugin' => 'AclManager', 'controller' => 'Permissions', 'action' => 'roles']) ?>">
                            <i class="fas fa-users"></i> <?= __d('acl_manager', 'Roles') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->Url->build(['plugin' => 'AclManager', 'controller' => 'Permissions', 'action' => 'syncResources']) ?>">
                            <i class="fas fa-sync"></i> <?= __d('acl_manager', 'Sync Resources') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->Url->build('/') ?>">
                            <i class="fas fa-arrow-left"></i> <?= __d('acl_manager', 'Back to App') ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <div class="container flash-messages">
        <?= $this->Flash->render() ?>
    </div>

    <!-- Main Content -->
    <main class="main-container">
        <div class="container">
            <?= $this->fetch('content') ?>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container text-center">
            <p class="mb-0">
                <?= __d('acl_manager', 'Authorization Manager') ?> v<?= Configure::read('AclManager.version', '3.1.0') ?> |
                <a href="https://github.com/mgomezbuceta/cakephp-aclmanager" target="_blank">GitHub</a>
            </p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?= $this->fetch('script') ?>
</body>
</html>
