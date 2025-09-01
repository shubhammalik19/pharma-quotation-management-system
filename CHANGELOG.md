# Changelog

All notable changes to the Pharma Quotation Management System will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2025-09-01

### Added
- **Complete Role-Based Access Control (RBAC)** system
  - Granular permission management
  - Multi-role support (Super Admin, Admin, Manager, Operator, Viewer)
  - Module-wise access control
- **Sales Order Management** module
  - Create and manage sales orders from quotations
  - Status tracking and workflow management
- **Purchase Order Management** module
  - Vendor purchase order creation and tracking
  - Integration with sales orders
- **Sales Invoice Management** system
  - Professional invoice generation with GST calculations
  - PDF export and email functionality
- **Credit/Debit Notes** management
  - Financial document handling
  - Integration with invoicing system
- **Enhanced Reporting System**
  - Comprehensive reports for all modules
  - Advanced filtering and search capabilities
  - PDF and Excel export options
- **Email Integration**
  - PHPMailer integration for document sharing
  - Email logging and tracking
  - Professional email templates
- **Dashboard Analytics**
  - Real-time business metrics
  - Quick action buttons
  - Recent activity tracking
- **Advanced Search and Filter**
  - Unified search across all modules
  - Date range filtering
  - Status-based filtering

### Enhanced
- **User Interface**
  - Bootstrap 5 integration
  - Responsive design improvements
  - Modern icon library (Bootstrap Icons)
- **Security Features**
  - Enhanced input validation
  - SQL injection protection
  - XSS prevention
  - Secure session management
- **Database Schema**
  - Optimized table structure
  - Foreign key constraints
  - Indexing for better performance
- **Code Organization**
  - Modular architecture
  - Improved error handling
  - Comprehensive helper functions

### Fixed
- Database connection stability
- Session timeout issues
- PDF generation reliability
- Form validation edge cases
- Mobile responsiveness issues

### Security
- Implemented prepared statements for all database queries
- Added CSRF protection for forms
- Enhanced password hashing with bcrypt
- Secure file upload validation

## [2.0.0] - 2025-08-28

### Added
- **Core Quotation Management System**
  - Create, edit, and manage quotations
  - PDF generation with company branding
  - Email quotations to customers
- **Customer/Vendor Management**
  - Complete CRM functionality
  - Support for customers, vendors, or both
  - Contact information and GST details
- **Machine Catalog Management**
  - Comprehensive machinery database
  - Technical specifications
  - Part codes and descriptions
- **Spare Parts Inventory**
  - Spare parts management
  - Machine association
  - Pricing information
- **Price Master System**
  - Dynamic pricing with validity periods
  - Machine-specific pricing
  - Historical price tracking
- **User Authentication System**
  - Secure login functionality
  - Session management
  - Basic user roles

### Technical Implementation
- **PHP 8.2+ Backend**
  - Object-oriented programming
  - MySQLi database connectivity
  - Secure coding practices
- **MySQL Database**
  - Normalized database design
  - Proper indexing
  - Data integrity constraints
- **Frontend Technologies**
  - Bootstrap 4 responsive design
  - jQuery for dynamic interactions
  - AJAX for seamless user experience
- **PDF Generation**
  - Custom PDF library integration
  - Professional document templates
  - Company branding integration

### Initial Features
- Dashboard with basic statistics
- Customer and machine management
- Basic quotation workflow
- PDF document generation
- Email functionality foundation

## [1.0.0] - 2025-08-01

### Added
- Initial project setup
- Basic PHP framework structure
- Database schema design
- Core business logic implementation
- Authentication system foundation

---

## Release Notes

### Version 2.1.0 - Major Feature Release
This version represents a significant milestone in the system's evolution, transforming it from a basic quotation tool to a comprehensive business management platform for pharmaceutical machinery companies.

#### Key Highlights:
- **Complete Business Process Management**: From quotations to invoicing
- **Advanced Security**: Role-based access control with granular permissions
- **Professional Documentation**: Enhanced PDF generation and email integration
- **Analytics and Reporting**: Comprehensive business intelligence features
- **Modern UI/UX**: Bootstrap 5 with responsive design

#### Migration Notes:
- Database schema updates are included in the SQL file
- Existing users will need role assignments
- Email configuration is required for full functionality
- File upload directories need proper permissions

#### System Requirements:
- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache/Nginx)
- Composer for dependency management

For detailed installation and upgrade instructions, please refer to the README.md file.
