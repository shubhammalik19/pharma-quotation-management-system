// Common JavaScript functions for the Quotation Management System

// Consistent page reload function
function reloadPage(message = null, type = 'success') {
    if (message) {
        // Store message in sessionStorage for display after reload
        sessionStorage.setItem('flashMessage', message);
        sessionStorage.setItem('flashType', type);
    }
    window.location.href = window.location.pathname + window.location.search;
}

// Function to display flash messages after page reload
function displayFlashMessage() {
    const message = sessionStorage.getItem('flashMessage');
    const type = sessionStorage.getItem('flashType');
    
    if (message) {
        // Clear the stored message
        sessionStorage.removeItem('flashMessage');
        sessionStorage.removeItem('flashType');
        
        // Create and display the alert
        const alertClass = type === 'error' ? 'alert-danger' : 'alert-success';
        const alertHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="bi bi-${type === 'error' ? 'exclamation-triangle' : 'check-circle'}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Insert at the top of the main content area
        const container = document.querySelector('.container-fluid');
        if (container) {
            container.insertAdjacentHTML('afterbegin', alertHTML);
        }
    }
}

// Initialize flash message display on page load
document.addEventListener('DOMContentLoaded', function() {
    displayFlashMessage();
});

// Global autocomplete function with unified endpoint
function initAutocomplete(inputSelector, type, minLength = 2, selectCallback = null) {
    $(inputSelector).autocomplete({
        source: function(request, response) {
            $.ajax({
                url: 'ajax/unified_search.php',
                type: 'GET',
                data: { 
                    term: request.term,
                    type: type
                },
                dataType: 'json',
                success: function(data) {
                    response(data);
                },
                error: function() {
                    response([]);
                }
            });
        },
        minLength: minLength,
        select: function(event, ui) {
            if (selectCallback && typeof selectCallback === 'function') {
                selectCallback(event, ui);
            }
        },
        response: function(event, ui) {
            if (ui.content.length === 0) {
                ui.content.push({
                    label: "No results found",
                    value: ""
                });
            }
        }
    });
}

// Helper function for backward compatibility
function initAutocompleteOld(inputSelector, source, minLength = 2, selectCallback = null) {
    $(inputSelector).autocomplete({
        source: source,
        minLength: minLength,
        select: function(event, ui) {
            if (selectCallback && typeof selectCallback === 'function') {
                selectCallback(event, ui);
            }
        },
        response: function(event, ui) {
            if (ui.content.length === 0) {
                ui.content.push({
                    label: "No results found",
                    value: ""
                });
            }
        }
    });
}

// Enable edit mode for a row
function enableEditMode(row) {
    const $row = $(row);
    $row.addClass('edit-mode');
    
    // Convert text to inputs
    $row.find('[data-field]').each(function() {
        const $cell = $(this);
        const fieldName = $cell.data('field');
        const currentValue = $cell.text().trim();
        
        if (fieldName && fieldName !== 'actions') {
            const input = $('<input>', {
                type: 'text',
                class: 'form-control form-control-sm',
                value: currentValue,
                'data-original': currentValue,
                'data-field': fieldName
            });
            $cell.html(input);
        }
    });
}

// Disable edit mode for a row
function disableEditMode(row) {
    const $row = $(row);
    $row.removeClass('edit-mode');
    
    // Convert inputs back to text
    $row.find('input[data-field]').each(function() {
        const $input = $(this);
        const $cell = $input.parent();
        const value = $input.val();
        $cell.text(value);
    });
}

// Cancel edit mode and restore original values
function cancelEditMode(row) {
    const $row = $(row);
    $row.removeClass('edit-mode');
    
    // Restore original values
    $row.find('input[data-field]').each(function() {
        const $input = $(this);
        const $cell = $input.parent();
        const originalValue = $input.data('original');
        $cell.text(originalValue);
    });
}

// Get form data from editable row
function getRowData(row) {
    const $row = $(row);
    const data = {};
    
    $row.find('input[data-field]').each(function() {
        const $input = $(this);
        const fieldName = $input.data('field');
        data[fieldName] = $input.val();
    });
    
    return data;
}

// Show loading state
function showLoading(element) {
    $(element).prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i>Loading...');
}

// Hide loading state
function hideLoading(element, originalText) {
    $(element).prop('disabled', false).html(originalText);
}

// Show success message
function showSuccess(message) {
    showAlert('success', message);
}

// Show error message
function showError(message) {
    showAlert('danger', message);
}

// Show alert message
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert at the top of the container
    $('.container-fluid').prepend(alertHtml);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}

// Confirm delete action
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR'
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN');
}

// Validate email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Validate phone number
function isValidPhone(phone) {
    const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
    return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
}

// Initialize page-specific functionality
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
