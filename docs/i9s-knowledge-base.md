# i9s Development Project - Knowledge Base

**Last Updated:** February 9, 2026  
**Current Plugin Version:** 1.3.1  
**Project Phase:** Task B Complete, Moving to Task C

---

## 🎯 Project Overview

**What is i9s?**
Historical baseball project modeling Negro League players' projected MLB careers.

**Tech Stack:**
- WordPress + Pods Framework 3.3.4
- Custom plugin: i9s Database Tools
- Code Snippets Pro for display logic
- Database: MySQL (dmlco_wp471, prefix: wpsk_)

**Player Statistics:**
- 171 players currently
- Long-term goal: 2,000-3,000 players
- Batting + Pitching projections for each player-year

---

## 📁 Key Documents

**Always reference these first:**
- `techref.md` - Complete database schema, field reference, best practices
- `roadmap.md` - Task list and priorities
- Plugin files in `/home/claude/i9s-tools/`

---

## 🏗️ Established Patterns

### **Pattern 1: Calculated Statistics Display**

**Problem:** Stats like BA, OBP, ERA stored in DB, error-prone, hard to update formulas

**Solution:** Calculate on display using this pattern:

**Step 1:** Calculation functions in plugin
```php
// File: /includes/class-i9s-tools-batting-calculations.php
class I9S_Tools_Batting_Calculations {
    public static function calculate_ba($h, $ab) {
        // Treat NULL as 0
        $h = $h ?? 0;
        $ab = $ab ?? 0;
        
        if ($ab == 0) return '---';
        
        $result = $h / $ab;
        
        // Smart formatting for values >= 1 (e.g., 1.022)
        if ($result >= 1) {
            return number_format($result, 3);
        } else {
            // Remove leading zero (e.g., .347 not 0.347)
            return '.' . substr(number_format($result, 3), 2);
        }
    }
    
    // Similar methods for calculate_obp(), calculate_slg(), calculate_ops()
}


// Global wrapper for Code Snippets
function i9s_calculate_ba($h, $ab) {
    return I9S_Tools_Batting_Calculations::calculate_ba($h, $ab);
}
```

**Step 2:** Display via Code Snippet + WordPress Shortcode
```php
// Code Snippet: "i9s Batting Table Display"
function i9s_display_batting_table($atts) {
    global $wpdb;
    $pid = $atts['pid'] ?? '';
    
    // Query database
    $records = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pods_playeryear_b WHERE pid = %s ORDER BY yr ASC",
        $pid
    ));
    
    // Build HTML table
    $output = '<table>...';
    foreach ($records as $rec) {
        $ba = i9s_calculate_ba($rec->h, $rec->ab);
        $obp = i9s_calculate_obp($rec->h, $rec->bb, $rec->ab);
        $slg = i9s_calculate_slg($rec->h, $rec->{'2b'}, $rec->{'3b'}, $rec->hr, $rec->ab);
        $ops = i9s_calculate_ops($obp, $slg);
        
        $output .= "<td>$ba</td><td>$obp</td><td>$slg</td><td>$ops</td>";
    }
    $output .= '</table>';
    return $output;
}

add_shortcode('i9s_batting_table', 'i9s_display_batting_table');
```

**Step 3:** Use in Pods Template
```
[i9s_batting_table pid="{@pid}"]
```

**Why This Works:**
- Pods templates can't handle multi-param function calls
- WordPress shortcodes are allowed in Pods templates
- Code Snippets registers the shortcode
- Plugin provides calculation functions
- Shortcode queries DB and builds complete table

**Implemented for:**
- ✅ Batting stats (BA, OBP, SLG, OPS)
- ✅ Pitching stats (ERA, WHIP, H/9, BB/9, K/9)

---

### **Pattern 2: Data Validation Tools**

**Structure:**
```php
// File: /includes/class-i9s-tools-validation.php
class I9S_Tools_Validation {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_menu_page'));
    }
    
    private static function check_something() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT ...");
        // Display results in table with edit links
    }
}
```

**Key Points:**
- Use numeric `id` field for edit links, not `pid_yr`
- Edit URL: `admin.php?page=pods-manage-[pod_name]&action=edit&id=[numeric_id]`

---

### **Pattern 3: Admin Page Structure**

**Layout:**
- Main dashboard: i9s Tools
- Submenu pages: Master List, Data Validation, etc.
- Cards for each tool
- Consistent styling via `/assets/css/admin.css`

---

## 🔧 Development Guidelines

### **Code Display Protocol**
**When implementing code changes, describe your plan first and wait for approval before writing code. Only show complete code blocks when: (1) User explicitly asks to see it, (2) we're ready to implement/test, or (3) there's a specific decision point that requires seeing the code. Otherwise, just describe what you'll do and confirm the approach.**

### **WordPress/Pods Best Practices**
- Always use `$wpdb->prepare()` for SQL queries
- Use transactions for multi-table updates
- Check `current_user_can('manage_options')` for admin pages
- Escape output with `esc_html()`, `esc_url()`, etc.

### **Pods Quirks**
- CPT (Players) stored in postmeta - query via Pods API or wp_postmeta
- ACT (PlayerYears) stored in tables - query via direct SQL
- Pods templates support HTML comments: `<!-- comment -->`
- Magic tags: `{@field}` outputs field value
- Shortcodes work in templates: `[shortcode_name]`

### **Plugin Versioning**
- Bump version in two places: header comment + `I9S_TOOLS_VERSION` constant
- Use semantic versioning: major.minor.patch
- Package as zip for delivery

---

## 📊 Database Schema Quick Reference

**Players (CPT):**
- Table: `wpsk_posts` + `wpsk_postmeta`
- Key fields: `pid` (meta), `first_name`, `last_name`, `yrdebut`, `yrfinal`, `pos`
- URL: `https://i9s.org/wp/?player={slug}`

**Batting Stats (ACT):**
- Table: `wpsk_pods_playeryear_b`
- Key fields: `id` (PK), `pid`, `yr`, `ab`, `h`, `2b`, `3b`, `hr`, `bb`, `so`, `sb`, `cs`
- Calculated on display: `ba`, `obp`, `slg`, `ops`

**Pitching Stats (ACT):**
- Table: `wpsk_pods_playeryear_p`
- Key fields: `id` (PK), `pid`, `yr`, `g`, `gs`, `ip`, `ha`, `w`, `k`, `hra`, `er`
- Calculated on display: `era`, `whip`, `h_9`, `bb_9`, `k_9`

**Field 'w' in pitching = Walks** (not Wins)

---

## ✅ Current Status

### **Completed Tasks:**

**Task D: Schema Review & Optimization** ✅
- Created `pyr_auto` field (auto-generated via hook)
- Format: `{pid} ({yr})` → `oms-000ale (1925)`
- Added compound unique indexes on `pid + yr`
- Plugin: v1.0.9

**Task A: Master Player List** ✅
- Admin page with all 171 players
- Dual search (name + content)
- Sortable columns
- Shows batting/pitching year counts
- Plugin: v1.1.0

**Task B: Convert Stats to Formulas** ✅
- Batting calculations implemented (BA, OBP, SLG, OPS)
- Pitching calculations implemented (ERA, WHIP, H/9, BB/9, K/9)
- Smart formatting (1.022 vs .347 for batting, 3.45 for pitching)
- Code Snippet + Shortcode pattern established
- Global wrapper functions for Code Snippets compatibility
- Plugin: v1.3.1


**Bonus: Data Validation** ✅
- Pitching Years Without ER check
- Pitching Years with 0 IP check
- Working edit links using numeric `id` field
- Plugin: v1.3.1

---

### **Active Code Snippets:**

1. **i9s Batting Table Display** (PHP Function)
   - Shortcode: `[i9s_batting_table pid="{@pid}"]`
   - Used in: `fullPlayerDisplay` template
   - Function: Builds complete batting stats table with calculated stats
   - Calls: `i9s_calculate_ba()`, `i9s_calculate_obp()`, `i9s_calculate_slg()`, `i9s_calculate_ops()`

2. **i9s Pitching Table Display** (PHP Function)
   - Shortcode: `[i9s_pitching_table pid="{@pid}"]`
   - Used in: `fullPlayerDisplay` template
   - Function: Builds complete pitching stats table with calculated stats
   - Calls: `i9s_calculate_era()`, `i9s_calculate_whip()`, `i9s_calculate_h9()`, `i9s_calculate_bb9()`, `i9s_calculate_k9()`

3. **fPlayersByDebut** (PHP Function)
   - Used for chronological player listing page
   - Pre-existing, not part of recent work

4. **fDateDisplay_mmddyyyy** (PHP Function)
   - Date formatting helper
   - Pre-existing, not part of recent work

---

### **Plugin Files:**

**Core:**
- `i9s-tools.php` - Main plugin file
- `includes/class-i9s-tools-admin.php` - Admin dashboard
- `includes/class-i9s-tools-master-list.php` - Master player list
- `includes/class-i9s-tools-validation.php` - Data validation tools

**Data Operations:**
- `includes/class-i9s-tools-playerid-fix.php` - Fix multiple dashes in pIDs
- `includes/class-i9s-tools-pyr-autogen.php` - Auto-generate pyr_auto field
- `includes/class-i9s-tools-pyr-autogenerate.php` - (duplicate/variant)

**Calculations:**
- `includes/class-i9s-tools-batting-calculations.php` - BA, OBP, SLG, OPS
- `includes/class-i9s-tools-pitching-calculations.php` - ERA, WHIP, H/9, BB/9, K/9

**Assets:**
- `assets/css/admin.css` - Admin styling
- `assets/css/master-list.css` - Master list specific styles
- `assets/js/admin.js` - PlayerID fix AJAX
- `assets/js/master-list.js` - DataTables integration

---

### **Known Issues & Quirks:**

**PlayerID Format:**
- Standard: `lastname-XXXfirstname` (single dash)
- Some legacy IDs have spaces (e.g., `ball m049can`) - this is VALID
- Multiple dashes (`--` or `---`) are INVALID and should be fixed

**CSV Imports:**
- phpMyAdmin CSV import can create duplicates if PID is wrong
- Always verify PID format before importing
- Use UPDATE mode, not INSERT mode

**Master Data Location:**
- Primary source: Excel file (not Google Sheets)
- Google Sheets version is 2+ years old, DO NOT USE
- Always export from Excel for imports

---

## 🎯 Next Tasks (Roadmap)

**Immediate Next:**
- **Task C:** Career Totals (TOTALS row + career summary)

**Quick Wins:**
- **Task E:** Search Function Links (30 min)
- **Task F:** By Position Page (1-2 hours)

**Larger Projects:**
- **Task G:** Data Input Tools (4-5 hours)
- **Task B.1:** Validation Tool Expansion (pending user requirements)

---

## 💡 Useful Shortcuts

**Check plugin version:**
```bash
grep "Version:" /home/claude/i9s-tools/i9s-tools.php
```

**Quick SQL to find issues:**
```sql
-- Multiple dash PIDs
SELECT DISTINCT pid FROM wpsk_pods_playeryear_p WHERE pid LIKE '%---%' OR pid LIKE '%--%';

-- Records without ER
SELECT COUNT(*) FROM wpsk_pods_playeryear_p WHERE er IS NULL;
```

**Pods template locations:**
- WordPress Admin → Pods Admin → Templates

**Code Snippet locations:**
- WordPress Admin → Snippets

---

## 🚫 Common Pitfalls to Avoid

1. **Don't paste large code blocks prematurely** - describe plan first, get approval
2. **Don't use Pods templates for complex calculations** - use Code Snippets + shortcodes
3. **Don't forget to bump plugin version** - update both header and constant
4. **Don't assume field names** - check techref.md first
5. **Don't import without verifying PID format** - check for multiple dashes
6. **Don't forget to add `id` to SELECT when building edit links** - PIDs aren't unique keys

---

## 🔄 Filesystem Reset Protocol

**CRITICAL: Container filesystem resets between sessions. Files in `/home/claude/` are LOST.**

### Before Starting Any Plugin Work

**Step 1: Check if plugin directory exists**
```bash
ls /home/claude/i9s-tools/
```

**Step 2: If directory is empty or missing:**

❌ **DO NOT** automatically rebuild from documentation
❌ **DO NOT** recreate files from scratch
âœ… **DO STOP and ASK:**

> "The plugin directory is empty (filesystem reset). Options:
> A) I can wait for you to upload the current version (v[X.X.X])
> B) I can rebuild from documentation (may introduce errors)
> 
> Which would you prefer?"

**Step 3: User uploads current plugin .zip**
- User uploads `i9s-tools-vX.X.X.zip` from local backup
- Extract to `/home/claude/`
- Verify version matches expected
- Proceed with planned work

### Why This Matters

**Token Waste:**
- Rebuilding from scratch = 5,000+ tokens
- Extracting uploaded zip = 50 tokens

**Error Risk:**
- Recreated code may not match production exactly
- Missing subtle fixes from previous sessions
- Could introduce regressions

**User Maintains:**
- Latest plugin .zip file locally (always download after successful deployment)
- Current version number in knowledge base
- Ready to upload at session start if needed

### Persistent Storage Reference

**What persists between sessions:**
- `/mnt/project/` - Read-only project files (knowledge base, roadmap, techref)
- `/mnt/user-data/outputs/` - Files presented to user (survives between sessions)
- `/mnt/user-data/uploads/` - User-uploaded files

**What does NOT persist:**
- `/home/claude/` - Working directory (RESETS EVERY SESSION)
- Any files not moved to `/mnt/user-data/outputs/`

**Best Practice:**
- Always move final deliverables to `/mnt/user-data/outputs/`
- User downloads and keeps local backup
- Re-upload at start of next session if needed

---

## 📝 Session Workflow

**Efficient Session Pattern:**
1. Reference techref.md for schema/field questions
2. Describe implementation plan, get approval
3. Implement (showing code only when needed)
4. Test
5. Package plugin if changes made
6. Update documentation
7. **Don't rebuild plugin automatically after filesystem reset** - stop and ask for upload first

**Token Savers:**
- Don't re-upload docs already in project
- Don't show code blocks until implementation phase
- Batch questions together
- Use shorthand confirmations ("Approach B, go")

---

**End of Knowledge Base**

*Use this as quick reference. Always check techref.md for detailed schema info.*
