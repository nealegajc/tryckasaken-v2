   - Navigate to `http://localhost/code2
   - The database will be automatically created 
   - Default admin credentials: `admin@gmail.com` / `admin`

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
