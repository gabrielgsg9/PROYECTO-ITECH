<?php
require_once __DIR__ . '/../Helpers/auth.php';
require_role(['administrador'], '../login.php');
$dashboardBasePath = '../';
require __DIR__ . '/../Components/backoffice-dashboard.php';