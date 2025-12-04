# Smart Chrism Shop - E-Commerce Platform

A complete e-commerce solution with M-Pesa payment integration, admin dashboard, and product management built with PHP and MySQL.

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-Proprietary-red)

## ✨ Features

- 🛍️ **Product Catalog** - Display and manage products with images
- 🛒 **Shopping Cart** - Add to cart functionality with localStorage
- 💳 **M-Pesa Payment** - Manual payment instructions with copy-paste functionality
- 👨‍💼 **Admin Dashboard** - Complete admin panel for managing products, orders, and settings
- 📊 **Analytics** - Sales statistics, charts, and reports
- 📱 **Responsive Design** - Mobile-friendly interface
- 🔒 **Secure** - Password hashing, prepared statements, XSS protection
- 📧 **Contact Form** - Customer contact with WhatsApp integration

## 🚀 Quick Start

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- cURL extension (for M-Pesa API)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/smart-chrism-shop.git
   cd smart-chrism-shop
   ```

2. **Configure Database**
   - Edit `config.php` with your database credentials
   - Or use the setup script: `http://localhost/kk/setup_database.php`

3. **Setup Database**
   ```bash
   # Option 1: Automatic setup
   # Visit: http://localhost/kk/setup_database.php
   
   # Option 2: Manual setup
   # Import database.sql via phpMyAdmin
   ```

4. **Configure M-Pesa (Optional)**
   - Edit `config.php`
   - Add your M-Pesa Daraja API credentials
   - See [M-Pesa Setup Guide](#m-pesa-setup) below

5. **Start Using**
   - Homepage: `http://localhost/kk/`
   - Admin Login: `http://localhost/kk/login.php`

### Default Admin Credentials

- **Email:** `yegoniccc@gmail.com`
- **Password:** `@ODero#2030$2616@`

⚠️ **Important:** Change the default password after first login!

## 📁 Project Structure

```
smart-chrism-shop/
├── config.php              # Main configuration file
├── index.html              # Homepage
├── login.php               # Admin login page
├── dashboard.php           # Admin dashboard
├── products.php            # Product management
├── create_order.php        # Order processing
├── mpesa_callback.php      # M-Pesa payment callback
├── setup_database.php      # Database setup script
├── uploads/                # Product images directory
├── style.css               # Main stylesheet
├── script.js               # Frontend JavaScript
└── database.sql            # Database schema
```

## 🔧 Configuration

### Database Configuration

Edit `config.php`:

```php
$servername = "localhost";
$username = "root";
$password = "";  // Your MySQL password
$dbname = "smartchrism";
```

### M-Pesa Configuration

Get credentials from [Safaricom Daraja Portal](https://developer.safaricom.co.ke/):

```php
define('MPESA_ENV', 'production');
define('MPESA_CONSUMER_KEY', 'your_consumer_key');
define('MPESA_CONSUMER_SECRET', 'your_consumer_secret');
define('MPESA_SHORTCODE', 'your_paybill_number');
define('MPESA_PASSKEY', 'your_passkey');
define('MPESA_CALLBACK_URL', 'https://yourdomain.com/kk/mpesa_callback.php');
```

## 🌐 Online Deployment

### Option 1: Traditional Web Hosting (cPanel, Plesk, etc.)

1. **Upload Files**
   - Upload all files via FTP/SFTP to `public_html/kk/`
   - Set folder permissions: `755` for folders, `644` for files
   - Ensure `uploads/` folder is writable (`755`)

2. **Create Database**
   - Create MySQL database via hosting control panel
   - Note database name, username, and password

3. **Configure**
   - Edit `config.php` with production database credentials
   - Update `BASE_URL` to your domain
   - Run `setup_database.php` on your server

4. **Security**
   - Delete `setup_database.php` after setup
   - Remove test files (`test_*.php`, `debug_*.php`)
   - Enable HTTPS
   - Change default admin password

### Option 2: VPS/Cloud Server

1. **Server Setup**
   ```bash
   # Install LAMP stack
   sudo apt update
   sudo apt install apache2 mysql-server php php-mysql
   ```

2. **Deploy Code**
   ```bash
   git clone https://github.com/yourusername/smart-chrism-shop.git
   sudo mv smart-chrism-shop /var/www/html/kk
   sudo chown -R www-data:www-data /var/www/html/kk
   ```

3. **Database Setup**
   ```bash
   mysql -u root -p
   CREATE DATABASE smartchrism;
   exit
   ```

4. **Configure**
   - Edit `config.php` with server database credentials
   - Run `setup_database.php` via browser

### Option 3: GitHub Pages (Static Frontend Only)

For static hosting, you'll need a separate backend API:

1. **Frontend (GitHub Pages)**
   - Host `index.html`, `style.css`, `script.js` on GitHub Pages
   - Update API endpoints to point to your backend

2. **Backend (Separate Server)**
   - Deploy PHP files to a PHP-enabled server
   - Configure CORS if needed

## 🔒 Security Features

- ✅ Password hashing with bcrypt
- ✅ Prepared statements (SQL injection prevention)
- ✅ XSS protection (htmlspecialchars)
- ✅ Session security
- ✅ Input validation
- ✅ Error logging (no display in production)
- ✅ File upload validation

## 📋 Requirements

- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- cURL extension
- GD extension (for image processing)
- mod_rewrite (Apache)

## 🛠️ Technologies Used

- **Backend:** PHP 7.4+
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Payment:** M-Pesa Daraja API
- **Server:** Apache/Nginx

## 📖 Documentation

- **Installation:** See installation steps above
- **M-Pesa Setup:** Configure in `config.php` (see M-Pesa section)
- **Database Setup:** Run `setup_database.php` or import `database.sql`

## 🤝 Contributing

This is a proprietary project. For issues or questions, please contact the repository owner.

## 📝 License

This project is proprietary software. All rights reserved.

## 🆘 Support

For issues:
1. Check error logs
2. Verify database connection
3. Review configuration in `config.php`
4. Test with `test_db.php`

## 🎯 Features Overview

### Customer Features
- Browse product catalog
- Add products to cart
- Complete checkout
- Receive payment instructions
- Copy-paste payment details
- Contact via WhatsApp

### Admin Features
- Product management (Create, Read, Update, Delete)
- Order management and tracking
- Dashboard with statistics
- Sales analytics and charts
- Contact message management
- Shop settings configuration

## 📸 Screenshots

_Add screenshots of your shop here_

## 🚀 Deployment Checklist

- [ ] Database created and configured
- [ ] All files uploaded to server
- [ ] File permissions set correctly
- [ ] `config.php` updated with production credentials
- [ ] M-Pesa credentials configured (if using)
- [ ] HTTPS enabled
- [ ] Default password changed
- [ ] Setup files removed
- [ ] Error logging enabled
- [ ] Tested complete order flow

## 📞 Contact

For support or questions, please open an issue in this repository.

---

**Made with ❤️ for Smart Chrism Shop**

