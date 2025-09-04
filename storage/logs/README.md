# Document Logs Directory

This directory contains log files for various document operations in the quotation management system.

## Log Files:

- **credit_notes.log** - Logs for credit note document operations
- **debit_notes.log** - Logs for debit note document operations  
- **purchase_orders.log** - Logs for purchase order document operations
- **quotations.log** - Logs for quotation document operations
- **sales_invoices.log** - Logs for sales invoice document operations
- **sales_orders.log** - Logs for sales order document operations

## Log Format:

Each log entry follows this format:
```
[YYYY-MM-DD HH:MM:SS] User: user_id | IP: ip_address | DOC_TYPE ID: document_id | message
```

## Log Viewer:

Access the log viewer at `/docs/view_logs.php` to view and manage these logs.

## Features:

- **Real-time Logging**: All document access and generation activities are logged
- **User Tracking**: Each log entry includes user ID and IP address
- **Document Tracking**: Document IDs are tracked for all operations
- **Error Logging**: Failed operations and errors are logged with details
- **Success Logging**: Successful document generations are logged
- **Log Viewer Interface**: Web-based interface to view, filter, and download logs
- **Auto-refresh**: Log viewer automatically refreshes every 30 seconds
- **Download Capability**: Individual log files can be downloaded
- **Statistics**: Shows total entries and file sizes

## Security:

- Logs are stored outside the web root for security
- Access to logs requires user authentication
- IP addresses are logged for security auditing
- No sensitive data (like passwords) is logged

## Maintenance:

Log files should be rotated periodically to prevent excessive disk usage. Consider implementing log rotation based on file size or age.
