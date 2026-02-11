# Strokin' - Golf Stroke Allocation App

A portable golf stroke allocation application that stores player and course data in a JSON file.

## Setup & Deployment

### Apache Server (Recommended)
1. Upload all files to your Apache web server
2. Ensure PHP is enabled
3. Make sure `strokin-data.json` is writable by the web server
4. Access via your web URL

### Local Testing (PHP)
```bash
php -S localhost:8000
```

### Files Needed for Apache
- `index.html` - Main application
- `save-data.php` - Handles saving data to JSON file
- `strokin-data.json` - All player and course data
- Note: `server.js` and `package.json` are not needed for Apache deployment

## Data Storage

All player, course, and team data is stored in `strokin-data.json`. You can:
- Edit this file directly with any text editor
- Back it up for different events
- Share it between computers
- Version control it with git

## Usage

- Add players with their handicaps and team assignments
- Select singles or four-ball matches
- Calculate stroke allocation per hole based on USGA guidelines
- Print scorecards directly from the browser
