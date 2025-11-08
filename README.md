# TrycKaSaken v2.0 🚲

A modern, responsive tricycle booking platform built with PHP, MySQL, and Bootstrap 5. This system connects passengers with tricycle drivers for efficient urban transportation.

## 🌟 Features

### **Multi-Role Authentication System**
- **Passengers**: Book rides, view trip history, manage profile
- **Drivers**: Accept ride requests, manage availability, vehicle verification
- **Administrators**: Complete platform management, analytics, user verification

### **Modern UI/UX Design**
- 🎨 **Green Theme**: Professional emerald green color scheme
- 📱 **Fully Responsive**: Optimized for desktop, tablet, and mobile
- ✨ **Glassmorphic Design**: Modern translucent effects and smooth animations
- 🧭 **Intuitive Navigation**: Tab-based interfaces and clear user flows

### **Admin Dashboard**
- 📊 **Real-time Analytics**: User statistics, booking trends, performance metrics
- 👥 **User Management**: Complete CRUD operations for passengers and drivers
- 🚗 **Driver Verification**: Streamlined verification workflow with status tracking
- 📅 **Booking Management**: View, assign, and manage all ride bookings
- 🔍 **Advanced Filtering**: Search and filter capabilities across all data

### **Booking System**
- 🗺️ **Location-based Booking**: Pickup and destination selection
- ⏱️ **Real-time Status**: Live booking status updates
- 💰 **Fare Calculation**: Dynamic pricing based on route
- 📱 **Driver Assignment**: Automatic and manual driver assignment options

## 🛠️ Technology Stack

### **Backend**
- **PHP 8.x**: Server-side logic and API endpoints
- **MySQL**: Relational database with optimized schema
- **Session Management**: Secure authentication and authorization

### **Frontend**
- **HTML5**: Semantic markup and accessibility
- **CSS3**: Custom variables, flexbox, grid, animations
- **Bootstrap 5**: Responsive framework and components
- **JavaScript**: Interactive elements and form validation

### **Development Tools**
- **Git**: Version control and collaboration
- **XAMPP**: Local development environment
- **VS Code**: Integrated development environment

## 🚀 Installation & Setup

### **Prerequisites**
- XAMPP or similar LAMP stack
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Modern web browser

### **Installation Steps**

1. **Clone the Repository**
   ```bash
   git clone https://github.com/yourusername/tryckasaken-v2.git
   cd tryckasaken-v2
   ```

2. **Setup Database**
   ```bash
   # Start XAMPP services
   sudo /opt/lampp/lampp start
   
   # Access the application
   http://localhost/code2
   ```

3. **Database Configuration**
   - Navigate to `http://localhost/code2/database/schema.php`
   - The database will be automatically created with sample data
   - Default admin credentials: `admin@gmail.com` / `admin`

4. **File Permissions** (Linux/Mac)
   ```bash
   chmod -R 755 /opt/lampp/htdocs/code2
   ```

## 📁 Project Structure

```
tryckasaken-v2/
├── 📄 index.php                 # Landing page
├── 📄 package.json             # Project metadata
├── 📄 README.md                # This documentation
├── 📄 ADMIN_FEATURES_TRACKER.md # Admin feature tracking
├── 📁 config/
│   └── 📄 dbConnection.php     # Database configuration
├── 📁 database/
│   ├── 📄 SCHEMA_INFO.md       # Database documentation
│   └── 📄 schema.php           # Schema definition & seed data
├── 📁 pages/
│   ├── 📁 admin/               # Admin dashboard pages
│   │   ├── 📄 admin_layout.php # Shared admin template
│   │   ├── 📄 admin.php        # Main dashboard
│   │   ├── 📄 users.php        # User management
│   │   ├── 📄 driver_management.php # Driver oversight
│   │   ├── 📄 bookings.php     # Booking management
│   │   ├── 📄 analytics.php    # Analytics dashboard
│   │   └── 📄 ...              # Other admin pages
│   ├── 📁 auth/                # Authentication
│   │   ├── 📄 login.php        # Multi-role login
│   │   ├── 📄 register.php     # User registration
│   │   └── 📄 logout.php       # Session termination
│   ├── 📁 driver/              # Driver interface
│   │   ├── 📄 loginDriver.php  # Driver dashboard
│   │   └── 📄 request.php      # Ride requests
│   └── 📁 passenger/           # Passenger interface
│       ├── 📄 book.php         # Booking interface
│       ├── 📄 loginUser.php    # Passenger dashboard
│       └── 📄 trip_history.php # Booking history
└── 📁 public/
    └── 📁 css/
        └── 📄 style.css        # Global styles & theme
```

## 🎨 Design System

### **Color Palette**
- **Primary Green**: `#16a34a` (Emerald-600)
- **Dark Green**: `#15803d` (Emerald-700)
- **Light Green**: `#dcfce7` (Emerald-100)
- **Background**: `#f0fdf4` (Emerald-50)

### **Typography**
- **Font**: Segoe UI, Tahoma, Geneva, Verdana, sans-serif
- **Headings**: 700 weight, primary green color
- **Body**: 400 weight, neutral gray

### **Components**
- **Glass Cards**: Translucent backgrounds with backdrop blur
- **Status Badges**: Color-coded with rounded corners
- **Action Buttons**: Consistent hover effects and transitions
- **Form Controls**: Enhanced styling with focus states

## 📊 Database Schema

### **Users Table**
```sql
users (
  user_id, user_type, name, email, phone, password,
  license_number, tricycle_info, verification_status,
  is_verified, is_active, created_at, status
)
```

### **Bookings Table**
```sql
tricycle_bookings (
  id, user_id, name, location, destination,
  notes, fare, booking_time, driver_id, status
)
```

### **Drivers Table**
```sql
drivers (
  driver_id, user_id, or_cr_path, license_path,
  picture_path, verification_status, created_at
)
```

## 🔐 Authentication & Security

### **Role-Based Access Control**
- **Passengers**: Access to booking and trip history
- **Drivers**: Access to ride requests and profile management
- **Admins**: Full system access and management capabilities

### **Security Features**
- Password hashing with PHP's password_hash()
- Session-based authentication
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- CSRF protection on forms

## 🚀 Features Roadmap

### **Version 2.0 - Current** ✅
- [x] Modern UI/UX with green theme
- [x] Responsive admin dashboard
- [x] Enhanced user management
- [x] Booking system improvements
- [x] Driver verification workflow

### **Version 2.1 - Planned** 🔄
- [ ] Driver interface modernization
- [ ] Passenger dashboard updates
- [ ] Real-time notifications
- [ ] Mobile app integration
- [ ] Payment gateway integration

### **Version 2.2 - Future** 📋
- [ ] GPS tracking integration
- [ ] Advanced analytics
- [ ] Multi-language support
- [ ] API development
- [ ] Performance optimizations

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📜 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support, please contact:
- **Email**: support@tryckasaken.com
- **Issues**: [GitHub Issues](https://github.com/yourusername/tryckasaken-v2/issues)
- **Documentation**: [Wiki](https://github.com/yourusername/tryckasaken-v2/wiki)

## 🙏 Acknowledgments

- Bootstrap team for the excellent CSS framework
- PHP community for robust backend capabilities
- Open source contributors and testers

---

**TrycKaSaken v2.0** - Making urban transportation accessible and efficient! 🚲✨