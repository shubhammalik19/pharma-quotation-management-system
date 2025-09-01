<?php
/**
 * DataTables Plugin for Reports
 * Contains all necessary CSS and JS for DataTables with export functionality
 * Includes Bootstrap and jQuery dependencies
 */
?>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<!-- DataTables JavaScript -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<!-- Export Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/datatables-utils.js"></script>

<style>
/* Simple, Clean DataTable Styles */
.dataTables_wrapper {
    font-size: 14px;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
    padding: 12px 8px;
    font-size: 13px;
}

.table td {
    padding: 10px 8px;
    font-size: 13px;
    vertical-align: middle;
}

.dataTables_length, 
.dataTables_filter, 
.dataTables_info, 
.dataTables_paginate {
    margin: 10px 0;
}

.dt-buttons {
    margin-bottom: 15px;
}

.dt-button {
    margin: 0 2px;
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 4px;
}

.dataTables_paginate .paginate_button {
    padding: 0.5rem 0.75rem;
    margin: 0 2px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .table th, .table td {
        padding: 8px 4px;
        font-size: 12px;
    }
}

/* Print styles */
@media print {
    .dt-buttons, .dataTables_length, .dataTables_filter, .dataTables_paginate {
        display: none !important;
    }
}
</style>

<script>
/**
 * Initialize DataTable with standard configuration
 * @param {string} tableId - The ID of the table to initialize
 * @param {object} customOptions - Custom options to override defaults
 */
function initDataTable(tableId, customOptions = {}) {
    const defaultOptions = {
        pageLength: 25,
        responsive: true,
        order: [[0, 'desc']],
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="bi bi-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm',
                title: function() {
                    return document.title + '_' + new Date().toISOString().slice(0,10);
                }
            },
            {
                extend: 'pdf',
                text: '<i class="bi bi-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm',
                orientation: 'landscape',
                pageSize: 'A4',
                title: function() {
                    return document.title;
                }
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i> Print',
                className: 'btn btn-info btn-sm',
                title: function() {
                    return document.title;
                }
            }
        ],
        language: {
            search: "Search:",
            searchPlaceholder: "Type to search...",
            lengthMenu: "Show _MENU_ entries",
            info: "_START_ to _END_ of _TOTAL_ entries",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            },
            emptyTable: "No data available in table"
        }
    };

    // Merge custom options with defaults
    const options = { ...defaultOptions, ...customOptions };
    
    // Initialize DataTable
    const table = $(tableId).DataTable(options);
    
    // Auto-resize columns on window resize
    $(window).on('resize', function() {
        table.columns.adjust().responsive.recalc();
    });
    
    return table;
}

/**
 * Add export button handlers
 * @param {object} table - DataTable instance
 */
function addExportHandlers(table) {
    $('#exportExcel').on('click', function() {
        table.button('.buttons-excel').trigger();
    });

    $('#exportPDF').on('click', function() {
        table.button('.buttons-pdf').trigger();
    });

    $('#printReport').on('click', function() {
        table.button('.buttons-print').trigger();
    });
}

/**
 * Simple badge helper for status columns
 * @param {string} value - The value to display
 * @param {string} type - Badge type (success, danger, warning, info, primary)
 */
function createBadge(value, type = 'primary') {
    return `<span class="badge bg-${type}">${value}</span>`;
}

/**
 * Format currency for display
 * @param {number} amount - The amount to format
 */
function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Format date for display
 * @param {string} date - The date string to format
 */
function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-IN');
}
</script>
