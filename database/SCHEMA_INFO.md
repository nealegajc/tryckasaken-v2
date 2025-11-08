# Schema-as-Code Pattern

## Overview
Your TrycKaSaken project now uses **pure PHP for database setup** - no .sql files needed!

## How It Works

### 1. **Schema Definition** (`database/schema.php`)
All database tables are defined as PHP code:
\`\`\`php
DatabaseSchema::getTables() // Returns CREATE TABLE statements
DatabaseSchema::getSeedData() // Returns default data
DatabaseSchema::createSchema($conn) // Executes everything
\`\`\`

### 2. **Auto-Setup** (`index.php`)
When you access the homepage:
- Checks if database exists
- Checks if tables exist using `DatabaseSchema::schemaExists()`
- Redirects to setup automatically if needed

### 3. **Setup Script** (`database/setup_database.php`)
Beautiful web UI that:
- Creates database
- Runs `DatabaseSchema::createSchema()` to build tables
- Inserts demo data
- Verifies integrity

## Benefits Over .sql Files

✅ **Version Control Friendly** - PHP code is easier to diff/merge
✅ **Cross-Platform** - No SQL syntax differences between MySQL/MariaDB versions
✅ **Programmatic** - Can add logic (conditionals, loops, calculations)
✅ **Self-Contained** - Everything in one project, no external files
✅ **Maintainable** - Change schema in one place, affects all environments
✅ **Testable** - Can unit test schema generation

## How to Add New Tables

Edit `database/schema.php`:

\`\`\`php
public static function getTables() {
    return [
        'existing_table' => "CREATE TABLE...",
        
        // Add your new table here:
        'ratings' => "CREATE TABLE IF NOT EXISTS `ratings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `booking_id` int(11) NOT NULL,
            `rating` int(1) NOT NULL,
            `comment` text,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
}
\`\`\`

Then run setup again - it's that simple!

## Demo Credentials

The system creates demo accounts automatically:

**Passenger Account:**
- Email: `passenger@demo.com`
- Password: `password123`

**Driver Account:**
- Email: `driver@demo.com`
- Password: `password123`

## Migration Path

If you want to keep the old .sql file as backup:
1. Keep `tric_db.sql` in database folder (for reference)
2. System now uses `schema.php` instead
3. Both work independently

## Files Involved

- `database/schema.php` - Core schema definition
- `database/setup_database.php` - Web-based installer
- `index.php` - Auto-detection and redirect
- ~~`database/tric_db.sql`~~ - No longer needed (can be deleted)
