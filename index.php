<?php
/**
 * Landing Page - TrycKaSaken
 * Now using template-based architecture with separated UI/UX
 */

require_once __DIR__ . '/database/DatabaseSchema.php';
require_once __DIR__ . '/includes/database-setup.php';

// Setup database silently
setupDatabase();

// Configure page variables
$pageTitle = 'TrycKaSaken - Tricycle Booking System';
$cssFiles = ['public/css/style.css', 'public/css/landing.css'];
$contentFile = __DIR__ . '/templates/pages/landing.php';

// Load main layout
include __DIR__ . '/templates/layouts/main.php';