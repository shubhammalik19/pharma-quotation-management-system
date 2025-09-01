# 🏭 Pharma Quotation Management System

A comprehensive web-based quotation and business management system designed specifically for pharmaceutical machinery companies. This system provides complete business process management from customer relations to sales documentation.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-00000F?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white)
![jQuery](https://img.shields.io/badge/jQuery-0769AD?style=for-the-badge&logo=jquery&logoColor=white)

## 📋 Table of Contents

- [Features](#-features)
- [Technology Stack](#-technology-stack)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage](#-usage)
- [Database Schema](#-database-schema)
- [API Documentation](#-api-documentation)
- [Contributing](#-contributing)
- [License](#-license)
- [Support](#-support)

## ✨ Features

### 🏢 Core Business Management
- **Customer/Vendor Management**: Complete CRM with support for customers, vendors, or both
- **Machine Catalog**: Comprehensive machinery database with specifications and technical details
- **Spare Parts Management**: Inventory management for machine spare parts
- **Price Master**: Dynamic pricing system with validity periods

### 📊 Sales & Documentation
- **Quotation Management**: 
  - Create, edit, and track quotations
  - PDF generation with company branding
  - Email quotations directly to customers
  - Revision tracking and validity management
  
- **Sales Orders**: Convert quotations to sales orders with status tracking
- **Purchase Orders**: Vendor purchase order management
- **Sales Invoices**: Generate professional invoices with GST calculations
- **Credit/Debit Notes**: Financial document management

### 👥 User Management & Security
- **Role-Based Access Control (RBAC)**: Granular permission system
- **User Authentication**: Secure login with session management
- **Permission Management**: Module-wise access control
- **Multi-Role Support**: Super Admin, Admin, Manager, Operator, Viewer roles

### 📈 Reporting & Analytics
- **Dashboard Analytics**: Real-time business metrics and KPIs
- **Comprehensive Reports**: 
  - Customer/Vendor reports
  - Machine inventory reports
  - Sales transaction reports
  - Financial document reports
- **Advanced Filtering**: Date range, customer, and status-based filtering
- **Export Capabilities**: PDF and Excel export functionality

### 📧 Communication Features
- **Email Integration**: PHPMailer integration for document sharing
- **Email Logs**: Track all sent communications
- **Template System**: Professional email templates

### 🔧 Technical Features
- **Responsive Design**: Mobile-friendly Bootstrap 5 interface
- **AJAX Integration**: Seamless user experience with real-time updates
- **PDF Generation**: Professional document generation
- **Search & Filter**: Advanced search across all modules
- **Data Validation**: Comprehensive input validation and sanitization

## 🛠 Technology Stack

### Backend
- **PHP 8.2+**: Core application logic
- **MySQL/MariaDB**: Database management
- **PDO/MySQLi**: Database connectivity

### Frontend
- **HTML5/CSS3**: Modern web standards
- **Bootstrap 5**: Responsive UI framework
- **jQuery**: DOM manipulation and AJAX
- **Bootstrap Icons**: Icon library
- **DataTables**: Advanced table functionality

### Libraries & Dependencies
- **PHPMailer**: Email functionality
- **Composer**: Dependency management
- **Custom PHP Libraries**: Business logic and utilities

## 🚀 Installation

### Prerequisites
- **Web Server**: Apache/Nginx
- **PHP**: Version 8.2 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Composer**: For dependency management

### Step 1: Clone the Repository
```bash
git clone https://github.com/yourusername/pharma-quotation-system.git
cd pharma-quotation-system
```

### Step 2: Install Dependencies
```bash
composer install
```

### Step 3: Database Setup
1. Create a new MySQL database:
```sql
CREATE DATABASE quotation_management;
```

2. Import the database schema:
```bash
mysql -u your_username -p quotation_management < quotation_management.sql
```

### Step 4: Configuration
1. Update database credentials in `common/conn.php`:
```php
$host = 'localhost';
$username = 'your_username';
$password = 'your_password';
$database = 'quotation_management';
```

2. Configure base URL and other settings in `common/conn.php`

### Step 5: Web Server Configuration

#### Apache Configuration
Create `.htaccess` file in the root directory:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/quotation-system;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Step 6: Set Permissions
```bash
# Set proper permissions for upload directories
chmod 755 uploads/
chmod 755 common/temp/
chmod 644 common/conn.php
```

## ⚙️ Configuration

### Email Configuration
Configure email settings in `email/email_config.php`:
```php
$email_config = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'your-email@gmail.com',
    'smtp_password' => 'your-app-password',
    'smtp_secure' => 'tls'
];
```

### Company Information
Update company details in the database `company_info` table or through the admin panel.

### Default Login Credentials
- **Username**: `admin`
- **Password**: `admin123`

⚠️ **Important**: Change the default password immediately after first login.

## 📖 Usage

### Dashboard Overview
The dashboard provides a comprehensive view of:
- Customer/Vendor statistics
- Machine and spare parts inventory
- Recent quotations and activities
- Quick action buttons for common tasks

### Creating a Quotation
1. Navigate to **Quotations** → **Create New**
2. Select customer and add quotation details
3. Add machines/spare parts with quantities and prices
4. Review and save the quotation
5. Generate PDF or send via email

### Managing Customers/Vendors
1. Go to **Customers** section
2. Add new customers with complete contact information
3. Set entity type (Customer, Vendor, or Both)
4. Manage GST details and addresses

### User Role Management
1. Access **Users & Roles** (Admin only)
2. Create custom roles with specific permissions
3. Assign roles to users
4. Manage module-wise access control

## 🗄️ Database Schema

### Core Tables
- **customers**: Customer and vendor information
- **machines**: Machinery catalog with specifications
- **spares**: Spare parts inventory
- **price_master**: Dynamic pricing with validity periods

### Transaction Tables
- **quotations** & **quotation_items**: Quotation management
- **sales_orders** & **sales_order_items**: Sales order processing
- **purchase_orders** & **purchase_order_items**: Purchase order management
- **sales_invoices** & **sales_invoice_items**: Invoice generation
- **credit_notes** & **debit_notes**: Financial document management

### Security Tables
- **users**: User accounts and authentication
- **roles**: Role definitions
- **permissions**: System permissions
- **role_permissions**: Role-permission mappings
- **user_roles**: User-role assignments

### System Tables
- **company_info**: Company configuration
- **email_logs**: Communication tracking

## 🔌 API Documentation

### AJAX Endpoints
The system provides various AJAX endpoints for dynamic functionality:

#### Customer Management
```javascript
// Get customer details
GET /ajax/get_customer_details.php?id={customer_id}

// Search customers
POST /ajax/unified_search.php
```

#### Quotation Management
```javascript
// Get quotation details
GET /ajax/get_quotation_details.php?id={quotation_id}

// Search quotations
POST /ajax/search_quotations.php
```

#### Email Functions
```javascript
// Send quotation email
POST /ajax/send_quotation_email.php
```

## 🤝 Contributing

We welcome contributions! Please follow these guidelines:

### Development Setup
1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-feature`
3. Make your changes and test thoroughly
4. Commit with descriptive messages: `git commit -m "Add new feature"`
5. Push to your fork: `git push origin feature/new-feature`
6. Create a Pull Request

### Coding Standards
- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Comment complex logic appropriately
- Maintain consistent indentation (4 spaces)

### Testing
- Test all functionality before submitting
- Ensure responsive design works across devices
- Validate with different user roles
- Check database queries for security vulnerabilities

## 📁 Project Structure

```
pharma-quotation-system/
├── assets/                 # Static assets (CSS, JS, images)
│   ├── css/
│   ├── js/
│   ├── images/
│   └── fonts/
├── auth/                   # Authentication related files
│   ├── login.php
│   ├── logout.php
│   ├── users.php
│   └── roles.php
├── common/                 # Common utilities and configurations
│   ├── conn.php           # Database connection
│   ├── functions.php      # Helper functions
│   └── temp/              # Temporary files
├── quotations/            # Quotation management
│   ├── quotations.php
│   ├── create_quotation.php
│   └── view_quotation.php
├── sales/                 # Sales management
│   ├── sales_orders.php
│   ├── purchase_orders.php
│   └── sales_invoices.php
├── reports/               # Reporting module
├── email/                 # Email configuration and services
├── docs/                  # Document generation (PDFs)
├── ajax/                  # AJAX endpoints
├── js/                    # JavaScript modules
├── uploads/               # File uploads
├── vendor/                # Composer dependencies
├── dashboard.php          # Main dashboard
├── index.php             # Entry point
├── header.php            # Common header
├── footer.php            # Common footer
├── menu.php              # Navigation menu
└── README.md             # This file
```

## 🔒 Security Features

### Data Protection
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- CSRF protection for forms
- Session security with proper configuration

### Access Control
- Role-based permission system
- Module-wise access restrictions
- Secure password hashing (bcrypt)
- Session timeout management

### File Security
- Upload validation and restrictions
- Secure file storage outside web root
- Proper file permissions

## 🔧 Maintenance

### Regular Tasks
- **Database Backup**: Schedule regular database backups
- **Log Monitoring**: Monitor error logs and email logs
- **Security Updates**: Keep PHP and dependencies updated
- **Performance Monitoring**: Monitor database queries and page load times

### Troubleshooting
- Check `error_log` files for PHP errors
- Verify database connection settings
- Ensure proper file permissions
- Check email configuration for delivery issues

## 📞 Support

### Getting Help
- **Documentation**: Check this README and inline code comments
- **Issues**: Report bugs or request features via GitHub Issues
- **Email**: Contact support at admin@pharmamachinery.com

### System Requirements
- **Minimum PHP Version**: 8.2
- **Recommended Memory**: 256MB
- **Disk Space**: 100MB minimum
- **Browser Support**: Modern browsers (Chrome, Firefox, Safari, Edge)

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🏆 Acknowledgments

- **Bootstrap Team**: For the excellent UI framework
- **PHPMailer**: For reliable email functionality
- **jQuery Team**: For DOM manipulation capabilities
- **MariaDB/MySQL**: For robust database management

## 🚀 Roadmap

### Upcoming Features
- [ ] REST API development
- [ ] Mobile application
- [ ] Advanced analytics and reporting
- [ ] Integration with accounting software
- [ ] Multi-language support
- [ ] Advanced workflow automation
- [ ] Document versioning system
- [ ] Audit trail and logging enhancements

---

**Made with ❤️ for the Pharmaceutical Machinery Industry**

For more information, visit our [documentation](docs/) or contact our [support team](mailto:admin@pharmamachinery.com).
