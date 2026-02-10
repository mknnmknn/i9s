# i9s Database Tools Plugin

A WordPress plugin for managing the i9s player database and Pods data.

## Installation

1. **Download the plugin folder** (`i9s-tools/`)

2. **Upload to WordPress:**
   - Upload the entire `i9s-tools` folder to `/wp-content/plugins/`
   - Or compress it as a ZIP and upload via WordPress admin (Plugins → Add New → Upload)

3. **Activate the plugin:**
   - Go to WordPress admin → Plugins
   - Find "i9s Database Tools"
   - Click "Activate"

4. **Access the tools:**
   - A new menu item "i9s Tools" will appear in your WordPress admin sidebar
   - Click it to access the dashboard

## Current Features

### Fix PlayerID Dashes

Identifies and fixes PlayerIDs with multiple consecutive dashes that cause Pods embed errors.

**What it does:**
- Scans all Player records for PlayerIDs with multiple dashes (e.g., `oms---000ale`, `ball--000wal`)
- Shows you exactly what will be changed before making any updates
- Updates three locations when you click "Fix":
  1. Player pod (`pid` field)
  2. PlayerYears_b records (all matching batting records)
  3. PlayerYears_p records (all matching pitching records)

**How to use:**
1. Go to i9s Tools dashboard
2. Click "Scan for Issues" to see what needs fixing
3. Review the results
4. Click "Fix PlayerIDs" to apply the changes
5. Confirm when prompted

**Safety features:**
- Uses database transactions (rolls back on errors)
- Requires confirmation before fixing
- Shows detailed results
- Non-destructive (only changes dashes to single dash format)

## File Structure

```
i9s-tools/
├── i9s-tools.php                          # Main plugin file
├── includes/
│   ├── class-i9s-tools-admin.php          # Admin menu and dashboard
│   └── class-i9s-tools-playerid-fix.php   # PlayerID fix utility
├── assets/
│   ├── css/
│   │   └── admin.css                      # Admin styles
│   └── js/
│       └── admin.js                       # Admin JavaScript
└── README.md                              # This file
```

## Requirements

- WordPress 5.0+
- Pods Framework plugin
- PHP 7.0+
- MySQL 5.6+

## Future Tools (Planned)

- **Convert to Relationships:** Convert text-based PlayerID links to proper Pods relationships
- **Data Validation:** Check for orphaned records, missing data, and integrity issues
- **Bulk Import/Export:** Tools for managing player data in bulk
- **Backup Assistant:** Quick database backup before major operations

## Database Tables Used

This plugin works with the following database tables:
- `wp_posts` (Player CPT - via post meta)
- `wp_postmeta` (Player custom fields)
- `wp_pods_playeryear_b` (Batting statistics)
- `wp_pods_playeryear_p` (Pitching statistics)

*Note: Table prefix may vary based on your WordPress installation*

## Support

For issues or questions, contact the i9s development team.

## Changelog

### Version 1.0.0
- Initial release
- PlayerID dash fix utility
- Admin dashboard framework
