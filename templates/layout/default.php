<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var \Cake\View\View $this
 */

$cakeDescription = 'Authorization Manager';
?>
<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $cakeDescription ?>:
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->Html->meta('icon') ?>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <?= $this->Html->css(['normalize.min', 'milligram.min', 'fonts', 'cake']) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>

    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .navbar-brand {
            font-weight: 600;
            color: white !important;
        }
        .container-main {
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .page-header {
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }
        .card {
            box-shadow: 0 2px 4px rgba(0,0,0,.08);
            border: none;
            margin-bottom: 20px;
        }
        .flash-message {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= $this->Url->build(['plugin' => 'AclManager', 'controller' => 'Permissions', 'action' => 'index']) ?>">
                <i class="fas fa-shield-alt"></i> Authorization Manager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->Url->build(['plugin' => 'AclManager', 'controller' => 'Permissions', 'action' => 'index']) ?>">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->Url->build(['plugin' => 'AclManager', 'controller' => 'Permissions', 'action' => 'roles']) ?>">
                            <i class="fas fa-users"></i> Roles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->Url->build(['plugin' => 'AclManager', 'controller' => 'Permissions', 'action' => 'syncResources']) ?>">
                            <i class="fas fa-sync"></i> Sync Resources
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->Url->build('/') ?>">
                            <i class="fas fa-arrow-left"></i> Back to App
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <div class="container">
        <div class="flash-message">
            <?= $this->Flash->render() ?>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container-main">
        <div class="container">
            <?= $this->fetch('content') ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="text-center text-muted py-4">
        <div class="container">
            <p class="mb-0">
                Powered by <a href="https://github.com/mgomezbuceta/cakephp-aclmanager" target="_blank">CakePHP Authorization Manager</a>
            </p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?= $this->fetch('script') ?>
</body>
</html>
