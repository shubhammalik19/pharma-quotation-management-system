# Contributing to Pharma Quotation Management System

Thank you for your interest in contributing to our project! This document provides guidelines and information for contributors.

## 🤝 Code of Conduct

By participating in this project, you agree to abide by our code of conduct:
- Be respectful and inclusive
- Provide constructive feedback
- Focus on what is best for the community
- Show empathy towards other contributors

## 🚀 Getting Started

### Prerequisites
- PHP 8.2+
- MySQL/MariaDB
- Composer
- Git
- Basic understanding of web development

### Development Setup

1. **Fork and Clone**
   ```bash
   git clone https://github.com/your-username/pharma-quotation-system.git
   cd pharma-quotation-system
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Database Setup**
   ```bash
   # Create database
   mysql -u root -p -e "CREATE DATABASE quotation_dev;"
   
   # Import schema
   mysql -u root -p quotation_dev < quotation_management.sql
   ```

4. **Configuration**
   ```bash
   # Copy and configure database settings
   cp common/conn.php.example common/conn.php
   # Edit database credentials
   ```

5. **Start Development Server**
   ```bash
   php -S localhost:8000
   ```

## 📝 How to Contribute

### Reporting Issues
- Use GitHub Issues to report bugs
- Include detailed steps to reproduce
- Provide system information (PHP version, browser, etc.)
- Include screenshots if applicable

### Suggesting Features
- Open a GitHub Issue with the "enhancement" label
- Clearly describe the feature and its benefits
- Include mockups or examples if possible

### Code Contributions

#### 1. Choose an Issue
- Look for issues labeled "good first issue" for beginners
- Comment on the issue to indicate you're working on it
- Ask questions if you need clarification

#### 2. Create a Branch
```bash
git checkout -b feature/your-feature-name
# or
git checkout -b bugfix/issue-number
```

#### 3. Make Your Changes
- Follow our coding standards (see below)
- Write clean, documented code
- Test your changes thoroughly

#### 4. Commit Your Changes
```bash
git add .
git commit -m "feat: add new feature description"
# or
git commit -m "fix: resolve issue with specific component"
```

#### 5. Push and Create Pull Request
```bash
git push origin feature/your-feature-name
```
Then create a Pull Request on GitHub.

## 📋 Coding Standards

### PHP Standards
- Follow **PSR-12** coding standards
- Use **camelCase** for variables and functions
- Use **PascalCase** for class names
- Include PHPDoc comments for functions

```php
/**
 * Calculate discount amount based on percentage
 * 
 * @param float $total The total amount
 * @param float $percentage The discount percentage
 * @return float The discount amount
 */
function calculateDiscount($total, $percentage) {
    return ($total * $percentage) / 100;
}
```

### JavaScript Standards
- Use **camelCase** for variables and functions
- Include JSDoc comments for complex functions
- Use modern ES6+ features where appropriate

```javascript
/**
 * Initialize DataTable with common configurations
 * @param {string} tableId - The table element ID
 * @param {Object} options - Additional DataTable options
 */
function initializeDataTable(tableId, options = {}) {
    // Implementation
}
```

### CSS/HTML Standards
- Use **semantic HTML5** elements
- Follow **BEM methodology** for CSS classes
- Ensure **accessibility** (WCAG 2.1 AA)
- Test **responsive design** on multiple devices

### Database Standards
- Use **descriptive table and column names**
- Include **proper foreign key constraints**
- Add **indexes** for frequently queried columns
- Include **comments** for complex queries

```sql
-- Get active quotations with customer details
SELECT 
    q.quotation_number,
    c.company_name,
    q.total_amount
FROM quotations q
INNER JOIN customers c ON q.customer_id = c.id
WHERE q.status = 'active';
```

## 🧪 Testing Guidelines

### Manual Testing
- Test all new features thoroughly
- Verify existing functionality isn't broken
- Test with different user roles and permissions
- Check responsive design on various screen sizes

### Test Scenarios
- **Authentication**: Login/logout, session management
- **CRUD Operations**: Create, read, update, delete for all entities
- **Permissions**: Role-based access control
- **Email**: Document sending and logging
- **PDF Generation**: Document formatting and content
- **Data Validation**: Form validation and error handling

### Browser Support
Test on the following browsers:
- Chrome (latest 2 versions)
- Firefox (latest 2 versions)
- Safari (latest 2 versions)
- Edge (latest 2 versions)

## 📁 Project Structure

```
pharma-quotation-system/
├── assets/              # Static assets
├── auth/               # Authentication
├── common/             # Shared utilities
├── quotations/         # Quotation management
├── sales/              # Sales modules
├── reports/            # Reporting
├── email/              # Email services
├── docs/               # Documentation
├── ajax/               # AJAX endpoints
└── js/                 # JavaScript modules
```

## 🔍 Code Review Process

### Pull Request Requirements
- [ ] Code follows project standards
- [ ] All tests pass
- [ ] Documentation is updated
- [ ] No breaking changes (or properly documented)
- [ ] Security considerations addressed

### Review Checklist
- **Functionality**: Does the code work as intended?
- **Security**: Are there any security vulnerabilities?
- **Performance**: Are there any performance issues?
- **Maintainability**: Is the code easy to understand and maintain?
- **Documentation**: Is the code properly documented?

## 🏷️ Commit Message Format

Use conventional commit messages:

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

### Types
- **feat**: New feature
- **fix**: Bug fix
- **docs**: Documentation changes
- **style**: Code style changes (formatting, etc.)
- **refactor**: Code refactoring
- **test**: Adding or updating tests
- **chore**: Maintenance tasks

### Examples
```
feat(quotations): add PDF export functionality
fix(auth): resolve session timeout issue
docs(readme): update installation instructions
style(css): improve responsive design for mobile
refactor(database): optimize query performance
test(auth): add unit tests for login function
chore(deps): update PHP dependencies
```

## 🐛 Debugging

### Common Issues
1. **Database Connection**: Check credentials in `common/conn.php`
2. **Permissions**: Ensure file permissions are set correctly
3. **Email**: Verify SMTP configuration
4. **PDF Generation**: Check temporary directory permissions

### Debug Mode
Enable debug mode for development:
```php
// In common/conn.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Logging
Use the built-in logging functions:
```php
// Log activities
logActivity('user_login', 'User logged in successfully');

// Error logging
error_log('Custom error message: ' . $error_details);
```

## 📚 Resources

### Documentation
- [PHP Documentation](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Bootstrap Documentation](https://getbootstrap.com/docs/)
- [jQuery Documentation](https://api.jquery.com/)

### Tools
- **IDE**: VS Code, PhpStorm
- **Database**: phpMyAdmin, MySQL Workbench
- **Version Control**: Git, GitHub Desktop
- **Testing**: Browser DevTools, Postman

## 🎯 Development Roadmap

### Upcoming Features
- [ ] REST API development
- [ ] Mobile application
- [ ] Advanced analytics
- [ ] Integration with accounting software
- [ ] Multi-language support
- [ ] Workflow automation
- [ ] Document versioning
- [ ] Audit trail enhancements

### Technical Improvements
- [ ] Unit testing implementation
- [ ] Continuous integration setup
- [ ] Performance optimization
- [ ] Code coverage analysis
- [ ] Automated deployment
- [ ] Security audit

## 🤔 Questions?

If you have questions about contributing:

1. Check existing [GitHub Issues](https://github.com/your-username/pharma-quotation-system/issues)
2. Join our discussions in [GitHub Discussions](https://github.com/your-username/pharma-quotation-system/discussions)
3. Email us at: dev-support@pharmamachinery.com

## 🙏 Recognition

Contributors will be recognized in:
- README.md contributors section
- Release notes
- Project documentation

Thank you for helping make this project better! 🚀
