# Bookish - Online Library Management System

## New Features

1. **Google Sign-In Integration**
   - Easy login with Google account
   - Automatic account creation for new users
   - Secure authentication flow

2. **Profile Management**
   - Upload and manage profile pictures
   - Update personal information
   - Change password functionality

3. **Password Recovery**
   - Forgot password feature
   - Reset using email and mobile verification
   - Secure password update process

4. **Reading History**
   - Track books viewed and read
   - Show reading progress
   - Maintain reading status (reading, completed, abandoned)

# System Overview

## File Structure

```
bookishphp/
├── admin/                  # Admin panel files
│   ├── assets/            # Admin-specific assets
│   ├── bookimg/           # Book cover images
│   ├── epubfiles/         # EPUB book files
│   └── includes/          # Admin includes
├── includes/              # Core includes
│   ├── api/              # API endpoints
│   │   └── save-progress.php
│   ├── auth/             # Authentication
│   ├── reader/           # Reader components
│   │   └── Reader.php
│   └── config.php        # Database configuration
├── public/               # Public assets
│   ├── assets/
│   │   ├── css/         # Stylesheets
│   │   ├── js/          # JavaScript files
│   │   └── img/         # Images
│   └── uploads/         # User uploads
├── sql/                 # SQL files
│   └── reading_progress.sql
└── index.php           # Main entry point
```

## Components

1. **Reader System**
   - `includes/reader/Reader.php`: Core reader functionality
   - `includes/api/save-progress.php`: API endpoint for saving reading progress

2. **Admin Panel**
   - Manage books, authors, and categories
   - View reading statistics
   - Handle user management

3. **User Features**
   - Read EPUB books
   - Track reading progress
   - Bookmark functionality

## Database Tables

1. **tblreadingprogress**
   - Tracks user reading progress
   - Stores current location and progress percentage
   - Links books with users

## Setup

1. Import SQL files from the `sql` directory
2. Configure database in `includes/config.php`
3. Ensure proper permissions on upload directories
4. Install dependencies:
   ```bash
   composer install
   ```

5. Configure Google Sign-In:
   - Create a project in Google Cloud Console
   - Enable Google+ API
   - Create OAuth 2.0 credentials
   - Add authorized redirect URI: `http://your-domain/bookishphp/google-callback.php`
   - Update `includes/google-config.php` with your credentials:
     ```php
     $clientID = 'YOUR_GOOGLE_CLIENT_ID';
     $clientSecret = 'YOUR_GOOGLE_CLIENT_SECRET';
     ```

6. Set up profile images:
   ```bash
   mkdir profile_images
   chmod 755 profile_images
   ```

## Security Notes

1. Always use HTTPS in production
2. Keep Google API credentials secure
3. Implement proper email service for password reset in production
4. Regular security updates for dependencies
