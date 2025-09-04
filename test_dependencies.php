<?php
session_start();
include_once 'common/functions.php';
include_once 'common/conn.php';

// Simple test page to verify dependency checking
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Customer Dependencies</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <script src="assets/js/jquery-3.7.1.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <h2>Test Customer Dependency Checking</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Test Dependency Check</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="customerId" class="form-label">Customer ID to test:</label>
                            <input type="number" class="form-control" id="customerId" value="1" min="1">
                        </div>
                        <button type="button" class="btn btn-primary" id="checkBtn">Check Dependencies</button>
                        <div id="result" class="mt-3"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Available Tables</h5>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">The system checks these tables for customer references:</small>
                        <ul class="mt-2">
                            <li>sales_orders (customer_id)</li>
                            <li>sales_invoices (customer_id)</li>
                            <li>quotations (customer_id)</li>
                            <li>purchase_orders (vendor_id)</li>
                            <li>purchase_invoices (vendor_id)</li>
                            <li>credit_notes (customer_id)</li>
                            <li>debit_notes (vendor_name)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    $('#checkBtn').on('click', function() {
        const customerId = $('#customerId').val();
        const btn = $(this);
        const result = $('#result');
        
        if (!customerId) {
            result.html('<div class="alert alert-warning">Please enter a customer ID</div>');
            return;
        }
        
        btn.prop('disabled', true).text('Checking...');
        result.html('<div class="text-muted">Checking dependencies...</div>');
        
        $.ajax({
            url: 'ajax/check_customer_dependencies.php',
            type: 'GET',
            data: { id: customerId },
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).text('Check Dependencies');
                
                if (response.success) {
                    let html = '<div class="alert alert-' + (response.can_delete ? 'success' : 'warning') + '">';
                    html += '<h6>' + response.customer_name + ' (' + response.entity_type + ')</h6>';
                    html += '<p>' + response.message + '</p>';
                    
                    if (!response.can_delete && response.dependencies) {
                        html += '<strong>Dependencies found:</strong><br>';
                        html += '<ul>';
                        response.dependencies.forEach(function(dep) {
                            html += '<li>' + dep + '</li>';
                        });
                        html += '</ul>';
                    }
                    html += '</div>';
                    result.html(html);
                } else {
                    result.html('<div class="alert alert-danger">Error: ' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false).text('Check Dependencies');
                result.html('<div class="alert alert-danger">AJAX Error: ' + error + '<br>Status: ' + status + '</div>');
            }
        });
    });
    </script>
</body>
</html>
