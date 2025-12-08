<?php
// index.php - simple router
require_once __DIR__ . '/header.php';


// Use 'route' from rewrite rule to avoid collision with pagination 'page' param
$page = $_GET['route'] ?? 'home';

switch ($page) {
    case 'home':
        include __DIR__ . '/home.php';
        break;
    case 'login':
        include __DIR__ . '/login.php';
        break;
    case 'logout':
        include __DIR__ . '/logout.php';
        break;
    case 'customers':
        include __DIR__ . '/customers.php';
        break;
    case 'customer_form':
        include __DIR__ . '/customer_form.php';
        break;
    case 'customer_delete':
        include __DIR__ . '/customer_delete.php';
        break;
    case 'customer_prices':
        include __DIR__ . '/customer_prices.php';
        break;
    case 'items':
        include __DIR__ . '/items.php';
        break;
    case 'item_form':
        include __DIR__ . '/item_form.php';
        break;
    case 'users':
        include __DIR__ . '/users.php';
        break;
    case 'user_form':
        include __DIR__ . '/user_form.php';
        break;
    case 'user_delete':
        include __DIR__ . '/user_delete.php';
        break;
    case 'item_delete':
        include __DIR__ . '/item_delete.php';
        break;
    case 'invoices':
        include __DIR__ . '/invoices.php';
        break;
    case 'invoices_new':
        include __DIR__ . '/invoices_new.php';
        break;
    case 'invoices_save':
        include __DIR__ . '/invoices_save.php';
        break;
    case 'invoice_view':
        include __DIR__ . '/invoice_view.php';
        break;
    case 'invoice_pdf':
        include __DIR__ . '/invoice_pdf.php';
        break;
    case 'ajax_get_customer_prices.php':
        include __DIR__ . '/ajax_get_customer_prices.php';
        break;
    case 'invoice_delete':
        include __DIR__ . '/invoice_delete.php';
        break;
    case 'dashboard':
        include __DIR__ . '/dashboard.php';
        break;
    case 'item_prices':
        include __DIR__ . '/item_prices.php';
        break;
    default:
        http_response_code(404);
        echo '<div class="container p-4">404 Not Found</div>';
}
