/* DataTables Assets Loader */

// Check if jQuery is loaded
if (typeof jQuery === 'undefined') {
    console.error('jQuery is required for DataTables');
}

// DataTables initialization function
function initSimpleDataTable(tableId, customOptions = {}) {
    // Check if DataTable already exists and destroy it
    if ($.fn.DataTable.isDataTable(tableId)) {
        $(tableId).DataTable().destroy();
    }
    
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
                    return document.title.replace(' - ', '_') + '_' + new Date().toISOString().slice(0,10);
                }
            },
            {
                extend: 'pdf',
                text: '<i class="bi bi-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm',
                orientation: 'landscape',
                pageSize: 'A4',
                title: function() {
                    return document.title.replace(' - ', ' ');
                }
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i> Print',
                className: 'btn btn-info btn-sm',
                title: function() {
                    return document.title.replace(' - ', ' ');
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
            emptyTable: "No data available"
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

// Utility functions
function formatCurrency(amount) {
    if (!amount || amount === 0) return '₹0.00';
    return '₹' + parseFloat(amount).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-IN');
}

function createBadge(value, type = 'primary') {
    return `<span class="badge bg-${type}">${value}</span>`;
}
