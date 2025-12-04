# Deployment Guide - Smart Chrism Shop

## 🌐 Online Deployment Options

### Option 1: Traditional Web Hosting (Recommended)

**Best for:** Shared hosting, cPanel, Plesk

#### Steps:

1. **Prepare Files**
   ```bash
   # Clone or download repository
   git clone https://github.com/yourusername/smart-chrism-shop.git
   cd smart-chrism-shop
   ```

2. **Upload to Server**
   - Use FTP client (FileZilla, WinSCP) or hosting File Manager
   - Upload all files to: `public_html/kk/` or `www/kk/`
   - Ensure `uploads/` folder exists and is writable

3. **Create Database**
   - Login to hosting control panel (cPanel/Plesk)
   - Go to MySQL Databases
   - Create new database: `smartchrism`
   - Create database user and grant privileges
   - Note: username, password, host

4. **Configure**
   - Edit `config.php` on server:
     ```php
     $servername = "localhost";  // or your DB host
     $username = "your_db_user";
     $password = "your_db_password";
     $dbname = "smartchrism";
     ```

5. **Setup Database**
   - Visit: `https://yourdomain.com/kk/setup_database.php`
   - Or import `database.sql` via phpMyAdmin

6. **Security**
   - Delete `setup_database.php` after setup
   - Remove test files
   - Enable HTTPS
   - Change admin password

---

### Option 2: VPS/Cloud Server (AWS, DigitalOcean, etc.)

**Best for:** Full control, scalability

#### Steps:

1. **Server Setup**
   ```bash
   # Update system
   sudo apt update && sudo apt upgrade -y
   
   # Install LAMP stack
   sudo apt install apache2 mysql-server php php-mysql php-curl php-gd php-mbstring -y
   
   # Start services
   sudo systemctl start apache2
   sudo systemctl start mysql
   sudo systemctl enable apache2
   sudo systemctl enable mysql
   ```

2. **Clone Repository**
   ```bash
   cd /var/www/html
   sudo git clone https://github.com/yourusername/smart-chrism-shop.git kk
   sudo chown -R www-data:www-data kk
   sudo chmod -R 755 kk
   sudo chmod -R 775 kk/uploads
   ```

3. **Database Setup**
   ```bash
   sudo mysql -u root -p
   ```
   ```sql
   CREATE DATABASE smartchrism CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'shop_user'@'localhost' IDENTIFIED BY 'strong_password';
   GRANT ALL PRIVILEGES ON smartchrism.* TO 'shop_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

4. **Configure**
   ```bash
   sudo nano /var/www/html/kk/config.php
   ```
   Update database credentials

5. **Run Setup**
   - Visit: `http://your-server-ip/kk/setup_database.php`
   - Or import `database.sql`

6. **Apache Configuration**
   ```bash
   sudo nano /etc/apache2/sites-available/000-default.conf
   ```
   Ensure `DocumentRoot` points to `/var/www/html/kk`

7. **Enable HTTPS (Let's Encrypt)**
   ```bash
   sudo apt install certbot python3-certbot-apache
   sudo certbot --apache -d yourdomain.com
   ```

---

### Option 3: GitHub Pages + Backend API

**Best for:** Static frontend with separate API

#### Frontend (GitHub Pages):

1. **Create GitHub Repository**
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   git remote add origin https://github.com/yourusername/smart-chrism-shop.git
   git push -u origin main
   ```

2. **Enable GitHub Pages**
   - Go to repository Settings → Pages
   - Select branch: `main`
   - Select folder: `/ (root)`
   - Save

3. **Update API Endpoints**
   - Edit `script.js` to point to your backend API
   - Update all fetch URLs to your backend domain

#### Backend (Separate PHP Server):

1. Deploy PHP files to PHP-enabled server
2. Configure CORS if needed
3. Update frontend to use backend API URLs

---

## 🔧 Configuration for Production

### 1. Update config.php

```php
// Database
$servername = "your_db_host";
$username = "your_db_user";
$password = "your_db_password";
$dbname = "smartchrism";

// Base URL
define("BASE_URL", "https://yourdomain.com/kk/");

// M-Pesa (if using)
define('MPESA_ENV', 'production');
define('MPESA_CONSUMER_KEY', 'your_production_key');
define('MPESA_CONSUMER_SECRET', 'your_production_secret');
define('MPESA_SHORTCODE', 'your_paybill');
define('MPESA_PASSKEY', 'your_passkey');
define('MPESA_CALLBACK_URL', 'https://yourdomain.com/kk/mpesa_callback.php');
```

### 2. File Permissions

```bash
# Folders
chmod 755 kk/
chmod 755 kk/uploads/

# Files
find kk/ -type f -exec chmod 644 {} \;

# PHP files (if needed)
chmod 644 kk/*.php
```

### 3. Security

**Remove Setup Files:**
```bash
rm setup_database.php
rm setup.php
rm test_*.php
rm debug_*.php
```

**Or protect with .htaccess:**
```apache
<FilesMatch "^(setup_|test_|debug_).*\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

---

## 📋 Pre-Deployment Checklist

- [ ] All files uploaded
- [ ] Database created
- [ ] `config.php` configured
- [ ] Database setup completed
- [ ] File permissions set
- [ ] HTTPS enabled
- [ ] Default password changed
- [ ] Setup files removed
- [ ] Test order flow
- [ ] Error logging enabled
- [ ] Backup created

---

## 🚀 Post-Deployment

1. **Test Everything**
   - Homepage loads
   - Products display
   - Cart works
   - Checkout works
   - Admin login works
   - Orders save correctly

2. **Monitor**
   - Check error logs
   - Monitor server resources
   - Review access logs

3. **Backup**
   - Setup automated database backups
   - Backup files regularly

---

## 🆘 Troubleshooting

### 500 Internal Server Error
- Check PHP error logs
- Verify file permissions
- Check database connection
- Review `.htaccess` syntax

### Database Connection Failed
- Verify credentials in `config.php`
- Check MySQL is running
- Verify database exists
- Check user permissions

### Images Not Uploading
- Check `uploads/` folder exists
- Verify folder permissions (755)
- Check PHP upload settings
- Review error logs

---

## 📞 Support

For deployment issues:
1. Check error logs
2. Verify server requirements
3. Test database connection
4. Review configuration

---

**Ready to deploy!** 🚀

