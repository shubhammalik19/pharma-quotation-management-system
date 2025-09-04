# 🏭 Pharma Quotation Management System

A comprehensive web-based quotation and business management system designed specifically for pharmaceutical machinery companies. This system provides complete business process management from customer relations to sales documentation with advanced features for modern business operations.

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-00000F?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white)
![jQuery](https://img.shields.io/badge/jQuery-3.7+-0769AD?style=for-the-badge&logo=jquery&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)
![Version](https://img.shields.io/badge/Version-2.0.0-blue?style=for-the-badge)

## 📋 Table of Contents

- [🚀 Quick Start](#-quick-start)
- [✨ Features](#-features)
- [🛠 Technology Stack](#-technology-stack)
- [📦 Installation](#-installation)
- [⚙️ Configuration](#-configuration)
- [📖 Usage Guide](#-usage-guide)
- [🗄️ Database Schema](#-database-schema)
- [🔌 API Documentation](#-api-documentation)
- [🤝 Contributing](#-contributing)
- [🔒 Security](#-security)
- [📄 License](#-license)
- [📞 Support](#-support)

## 🚀 Quick Start

### Demo Credentials
- **URL**: `http://your-domain.com`
- **Username**: `admin`
- **Password**: `admin123`

⚠️ **Security Notice**: Change default credentials immediately after first login!

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

### � System Requirements
- **PHP**: Version 8.2 or higher with extensions (PDO, MySQLi, GD, cURL, OpenSSL)
- **Database**: MySQL 8.0+ or MariaDB 10.6+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Memory**: Minimum 512MB RAM (1GB recommended)
- **Storage**: 500MB free space minimum
- **Browser**: Modern browsers (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)

### 🚀 Installation

#### Method 1: Quick Installation Script
#### Method 1: Quick Installation Script
```bash
# Download and run the installation script
curl -sSL https://raw.githubusercontent.com/shubhammalik19/pharma-quotation-management-system/main/install.sh | bash
```

#### Method 2: Manual Installation

##### Step 1: Clone the Repository
```bash
git clone https://github.com/shubhammalik19/pharma-quotation-management-system.git
cd pharma-quotation-management-system
```

##### Step 2: Install Dependencies
##### Step 2: Install Dependencies
```bash
# Install PHP dependencies via Composer
composer install

# Set proper permissions
chmod -R 755 uploads/ common/temp/ storage/
chmod 644 common/conn.php
```

##### Step 3: Database Setup
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
Update company details in the database `company_info` table or through the admin panel:

```sql
UPDATE company_info SET 
    company_name = 'Your Company Name',
    address = 'Your Address',
    phone = 'Your Phone',
    email = 'your-email@company.com',
    gst_number = 'Your GST Number'
WHERE id = 1;
```

### Environment Configuration
Create a `.env` file for environment-specific settings:
```env
DB_HOST=localhost
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_DATABASE=quotation_management

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password

APP_DEBUG=false
APP_URL=https://your-domain.com
```

### Default Login Credentials
- **Username**: `admin`
- **Password**: `admin123`

⚠️ **Important**: Change the default password immediately after first login.

## 📖 Usage Guide

### 🎯 Getting Started
1. **Login**: Access the system using your credentials
2. **Dashboard**: Review key metrics and system status
3. **Setup**: Configure company information and user roles
4. **First Quotation**: Create your first quotation following the guided process

### 💼 Business Workflow
### 💼 Business Workflow

#### 1. Customer Management
```
Add Customer → Set GST Details → Configure Addresses → Assign Categories
```

#### 2. Product Catalog Setup
```
Add Machines → Set Specifications → Configure Pricing → Link Spare Parts
```

#### 3. Quotation Process
```
Create Quotation → Add Items → Calculate Totals → Generate PDF → Send Email
```

#### 4. Order Management
```
Convert Quotation → Create Sales Order → Track Status → Generate Invoice
```

### Dashboard Overview
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

### 🔧 Maintenance & Updates

#### Regular Maintenance Tasks
```bash
# Update system dependencies
composer update

# Clear temporary files
rm -rf common/temp/*

# Backup database (daily recommended)
mysqldump -u username -p quotation_management > backup_$(date +%Y%m%d).sql

# Check system logs
tail -f /var/log/apache2/error.log
```

#### Performance Optimization
- **Database**: Regular OPTIMIZE TABLE operations
- **Cache**: Implement Redis/Memcached for session storage
- **Files**: Regular cleanup of temporary and uploaded files
- **Monitoring**: Set up monitoring for database queries and page load times

## 📊 Performance Metrics

### Recommended Benchmarks
- **Page Load Time**: < 2 seconds
- **Database Queries**: < 50 per page
- **Memory Usage**: < 128MB per request
- **File Upload**: Support up to 10MB files

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
- **📚 Documentation**: Comprehensive guides in the [docs/](docs/) directory
- **🐛 Bug Reports**: Submit issues via [GitHub Issues](https://github.com/shubhammalik19/pharma-quotation-management-system/issues)
- **💬 Discussions**: Join our [GitHub Discussions](https://github.com/shubhammalik19/pharma-quotation-management-system/discussions)
- **📧 Email Support**: admin@pharmamachinery.com
- **📞 Phone Support**: +1-800-PHARMA-1

### FAQ
**Q: How do I reset a forgotten password?**
A: Contact your system administrator or use the password reset feature.

**Q: Can I customize the PDF templates?**
A: Yes, templates are located in the `docs/` directory and can be modified.

**Q: Is multi-currency support available?**
A: Currently, the system supports single currency. Multi-currency is planned for v3.0.

### Community
- **⭐ Star us on GitHub**: https://github.com/shubhammalik19/pharma-quotation-management-system
- **🍴 Fork the project**: Contribute to the development
- **📢 Follow updates**: Watch the repository for updates

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

### Version 2.1 (Q4 2024) ✅
- [x] Enhanced security features
- [x] Improved mobile responsiveness
- [x] Advanced reporting capabilities
- [x] Email template customization

### Version 3.0 (Q2 2025) 🚧
- [ ] REST API development with OpenAPI documentation
- [ ] Progressive Web App (PWA) support
- [ ] Real-time notifications via WebSockets
- [ ] Multi-currency and multi-language support
- [ ] Advanced workflow automation
- [ ] Integration with popular accounting software (QuickBooks, Tally)

### Version 3.5 (Q4 2025) 📋
- [ ] Mobile application (iOS/Android)
- [ ] AI-powered analytics and insights
- [ ] Document versioning system with Git-like tracking
- [ ] Advanced audit trail and compliance features
- [ ] Cloud storage integration (AWS S3, Google Drive)
- [ ] Advanced inventory management with barcode scanning

### Long-term Vision 🔮
- [ ] Machine learning for price optimization
- [ ] Blockchain integration for document verification
- [ ] IoT integration for real-time machine monitoring
- [ ] Advanced CRM with lead scoring
- [ ] Multi-tenant SaaS platform

---

**Made with ❤️ for the Pharmaceutical Machinery Industry**

### 🌟 Project Stats
![GitHub stars](https://img.shields.io/github/stars/shubhammalik19/pharma-quotation-management-system?style=social)
![GitHub forks](https://img.shields.io/github/forks/shubhammalik19/pharma-quotation-management-system?style=social)
![GitHub issues](https://img.shields.io/github/issues/shubhammalik19/pharma-quotation-management-system)
![GitHub last commit](https://img.shields.io/github/last-commit/shubhammalik19/pharma-quotation-management-system)

### 🔗 Useful Links
- **🏠 Homepage**: [Project Website](https://pharmaquotation.com)
- **📖 Documentation**: [Full Documentation](https://docs.pharmaquotation.com)
- **🎬 Video Tutorials**: [YouTube Channel](https://youtube.com/pharmaquotation)
- **💬 Community**: [Discord Server](https://discord.gg/pharmaquotation)
- **📧 Newsletter**: [Stay Updated](https://newsletter.pharmaquotation.com)

For more information, visit our [documentation](docs/) or contact our [support team](mailto:admin@pharmamachinery.com).

**Copyright © 2024 Pharma Quotation Management System. All rights reserved.**
