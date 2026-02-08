# i9s Project Roadmap

**Last Updated:** February 8, 2026  
**Status:** Planning Phase

This document outlines all planned enhancements and improvements. Tasks are organized by phase based on dependencies and logical workflow.

---

## Overview

### Phase Summary

| Phase | Focus | Tasks | Est. Time |
|-------|-------|-------|-----------|
| Phase 1 | Foundation |~~D~~, A | 4-6 hours |
| Phase 2 | Data Structure | B, C | 5-7 hours |
| Phase 3 | User Experience | E, F | 1.5-3 hours |
| Phase 4 | Workflow | G | 4-5 hours |
| **Total** | | **7 tasks** | **14.5-21 hours** |

---

## Phase 1: Foundation (Build Clean Base)

### Task D: Schema Review & Optimization

**Priority:** High  
**Estimated Time:** 2-3 hours  
**Dependencies:** None

Complete 260207

---

### Task A: Master Player List Page

**Priority:** High  
**Estimated Time:** 2-3 hours  
**Dependencies:** Task D (benefits from clean schema)

**Purpose:**  
Create an admin-only page listing all players with key information and quick access links for both human review and AI reference.

**Requirements:**

**Location:**
- Add to i9s Database Tools plugin as new tab/section
- Admin-only access

**Display:**
- All players (no pagination)
- Client-side search/filter box for instant filtering
- Sortable columns (JavaScript, no page reload)

**Columns:**
- Name (links to public player page: `https://i9s.org/wp/?player={slug}`)
- PID
- Position
- Debut Year
- Years (displayed as "B / P" format)
  - Example: "20 / 0" = 20 batting years, 0 pitching years
  - Example: "15 / 8" = 15 batting years, 8 pitching years
- Edit (links to WordPress admin edit screen)

**Sortable By:**
- Name (alphabetical)
- Debut Year (chronological)
- Position (grouped)
- PID (alphabetical)

**Features:**
- Search box filters all columns instantly (as you type)
- Click column headers to sort ascending/descending
- Responsive table design
- Fast loading (all data fetched once via AJAX)

**Technical Approach:**
- Add new admin page to i9s Database Tools plugin
- AJAX endpoint to fetch player data with year counts:
  ```sql
  SELECT p.post_title, pm.meta_value as pid, ...
         (SELECT COUNT(*) FROM wpsk_pods_playeryear_b WHERE pid = pm.meta_value) as batting_years,
         (SELECT COUNT(*) FROM wpsk_pods_playeryear_p WHERE pid = pm.meta_value) as pitching_years
  FROM wpsk_posts p
  JOIN wpsk_postmeta pm ON p.ID = pm.post_id
  WHERE pm.meta_key = 'pid'
  ```
- JavaScript DataTables library or custom sorting/filtering
- Cache results for performance (WordPress transients)

**Use Cases:**
- Quick reference for player URLs and PIDs
- Validate data completeness (which players have stats)
- Easy access to edit players
- AI assistant can view all players at once for context
- Identify players missing stats
- Find players by position or era quickly

---

## Phase 2: Data Structure (How Stats Work)

### Task B: Convert Statistical Numbers to Formulas

**Priority:** High  
**Estimated Time:** 3-4 hours  
**Dependencies:** Task D (clean schema makes this easier)

**Purpose:**  
Stop storing calculated statistics in the database. Calculate them on display from raw counting stats to improve data integrity and simplify data entry.

**Current State:**
- Calculated stats stored in DB: BA, OBP, SLG, OPS, ERA, WHIP, H/9, BB/9, K/9
- Requires manual calculation before entry (error-prone)
- Inconsistent if formulas change
- Duplicates data (H/AB stored separately AND as BA)

**Requirements:**

**Remove from Storage (stop using these fields):**
- Batting: BA, OBP, SLG, OPS
- Pitching: ERA, WHIP, H/9, BB/9, K/9

**Keep Raw Stats:**
- Batting: AB, H, 2B, 3B, HR, BB, SO, SB, CS
- Pitching: G, GS, IP, HA, W, K, HRA, eD, eT, eHBP, WP, eBK

**Calculate on Display:**
- Create PHP functions for each calculation:
  ```php
  function calculate_batting_average($h, $ab) {
      if ($ab == 0) return '.000';
      return number_format($h / $ab, 3);
  }
  
  function calculate_obp($h, $bb, $ab) {
      $pa = $ab + $bb; // Simplified OBP
      if ($pa == 0) return '.000';
      return number_format(($h + $bb) / $pa, 3);
  }
  
  function calculate_era($er, $ip) {
      if ($ip == 0) return '0.00';
      return number_format(($er * 9) / $ip, 2);
  }
  ```
- Update Pods templates to call functions
- Format output appropriately (.000 for BA, 0.00 for ERA, etc.)

**Migration:**
- Leave old calculated fields in database (don't delete columns yet)
- Ignore them in templates
- Future: Remove columns after validation period (6+ months)

**Add Validation Tool:**
- New section in i9s Database Tools
- Scan all player-years for impossible/suspicious stats:
  - H > AB (impossible)
  - 2B + 3B + HR > H (extra base hits exceed total)
  - Negative values
  - IP with invalid decimals (only .0, .1, .2 allowed)
  - SO > AB (unlikely but possible)
- Display report with:
  - Player name
  - Year
  - Problem description
  - Link to edit record
- Example output:
  ```
  Alejandro Oms - 1925: H (156) > AB (144) ❌
  Pete Hill - 1920: 2B (28) + 3B (8) + HR (12) = 48 > H (38) ❌
  ```

**Benefits:**
- Data entry only requires raw stats (fewer fields to type)
- Formula changes apply retroactively to all players instantly
- Single source of truth (raw counts)
- Easier validation and error checking
- No more typos in calculated stats
- Consistent calculations across all players

**Template Changes:**

Before:
```
<td>{@ba}</td><td>{@obp}</td><td>{@slg}</td>
```

After:
```php
<td><?php echo calculate_batting_average($item['h'], $item['ab']); ?></td>
<td><?php echo calculate_obp($item['h'], $item['bb'], $item['ab']); ?></td>
<td><?php echo calculate_slg($item); ?></td>
```

Or use Pods code callbacks if templates support it.

---

### Task C: Career Totals

**Priority:** Medium  
**Estimated Time:** 2-3 hours  
**Dependencies:** Task B (uses same calculation approach)

**Purpose:**  
Display career total and average statistics for each player across all their seasons.

**Requirements:**

**Display Locations:**

1. **Bottom of year-by-year tables**
   - TOTALS row after last year
   - Bolded or CSS class to distinguish
   - Shows sum of counting stats and career calculations
   
2. **Career summary at top of player page**
   - Condensed highlight stats before detailed tables
   - Different format for position players vs pitchers vs two-way
   
3. **Career leaderboards** (future feature, not this task)
   - Separate page ranking players by career stats
   - Minimum AB/IP qualifications
   - Will require stored/indexed career stats for sorting

**Stats to Calculate:**

**Batting:**
- **Totals:** AB, H, 2B, 3B, HR, BB, SO, SB, CS
- **Career Averages:** BA, OBP, SLG, OPS
  - Career BA = Total H / Total AB (not average of yearly BAs)
  - Career OBP = Total (H+BB) / Total (AB+BB)
  - Career SLG = Total Bases / Total AB
  - Career OPS = Career OBP + Career SLG

**Pitching:**
- **Totals:** G, GS, IP, HA, W, K, HRA, eD, eT, eHBP, WP, eBK
- **Career Averages:** ERA, WHIP, H/9, BB/9, K/9
  - Career ERA = (Total ER * 9) / Total IP
  - Career WHIP = (Total HA + Total W) / Total IP
  - Career H/9 = (Total HA * 9) / Total IP
  - Career BB/9 = (Total W * 9) / Total IP
  - Career K/9 = (Total K * 9) / Total IP

**Two-Way Players:**
- Separate batting career totals AND pitching career totals
- Calculate each independently from respective yearly stats
- Example: Bullet Rogan has both batting and pitching career summaries

**Display Format Examples:**

**Position Player (like Alejandro Oms):**
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
CAREER: .297 BA, .368 OBP, .437 SLG in 3,245 AB (1917-1935)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

[Player info table]

Batting Projections
Year  AB    H   2B  3B  HR  BB  SO  SB  CS   BA   OBP   SLG   OPS
1917  425  138  22   7  11  38  42  15   8  .325  .381  .478  .859
1918  456  141  24   6  13  42  47  12   6  .309  .369  .461  .830
...
1935  287   79  12   3   5  28  31   4   2  .275  .337  .388  .725
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TOTALS: 3,245 AB | 965 H | 178 2B | 52 3B | 89 HR | .297 BA | .368 OBP | .437 SLG | .805 OPS
```

**Pitcher (like Walter Ball):**
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
CAREER: 3.12 ERA, 1.24 WHIP in 1,247.1 IP (1893-1923)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

[Player info table]

Pitching Projections
Year   G  GS    IP     HA   W   K  HRA  ERA  WHIP
1893  19  16  130.0  144  39  46   2  3.51  1.41
1894  20  15  123.0  156  40  30   3  4.29  1.59
...
1923  12   8   67.0   71  22  18   1  2.96  1.39
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TOTALS: 312 G | 289 GS | 1,247.1 IP | 1,156 HA | 3.12 ERA | 1.24 WHIP
```

**Two-Way Player (like Bullet Rogan):**
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
CAREER BATTING: .338 BA, .411 OBP, .527 SLG in 1,892 AB (1920-1938)
CAREER PITCHING: 2.63 ERA, 1.09 WHIP in 1,654.2 IP (1920-1937)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

[Player info table]

Batting Projections
[Batting table with years]
TOTALS: [batting career totals]

Pitching Projections
[Pitching table with years]
TOTALS: [pitching career totals]
```

**Technical Approach:**
- Calculate on display (consistent with Task B approach)
- PHP functions to sum raw stats and calculate career averages:
  ```php
  function get_career_batting_totals($pid) {
      global $wpdb;
      return $wpdb->get_row($wpdb->prepare(
          "SELECT 
              SUM(ab) as total_ab,
              SUM(h) as total_h,
              SUM(2b) as total_2b,
              ...
          FROM {$wpdb->prefix}pods_playeryear_b
          WHERE pid = %s",
          $pid
      ));
  }
  ```
- Add to existing Pods templates
- CSS styling to distinguish TOTALS row (bold, borders, background color)
- Update fullPlayerDisplay template to include career summary

---

## Phase 3: User Experience (Quick Wins)

### Task E: Search Function - Clickable Links

**Priority:** Low (but quick win)  
**Estimated Time:** 30 minutes  
**Dependencies:** None

**Purpose:**  
Make player names in WordPress search results clickable links to their profile pages.

**Current State:**
- Search works and finds players
- Results show player names as plain text
- Example: https://i9s.org/wp/?s=pet shows Pete Hill, but name isn't clickable
- Using WordPress native search (part of blog theme)

**Requirements:**
- Minimal change to existing search results template
- Make player names link to their pages
- Maintain existing search functionality
- No redesign needed, just add links
- Keep current styling/layout

**Technical Approach:**
- Locate theme's search results template
  - Likely `search.php` or `searchform.php` in theme
  - Or template part like `template-parts/content-search.php`
- Find where post title is displayed
- Wrap in link to player page:
  ```php
  // Before
  <?php the_title(); ?>
  
  // After
  <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
  ```
- Test with various search queries
- Verify links work for player post type

**Edge Cases:**
- Ensure it only affects player posts, not regular posts/pages
- Check that search still works for non-player content

---

### Task F: By Position Page

**Priority:** Medium  
**Estimated Time:** 1-2 hours  
**Dependencies:** None (can be done anytime)

**Purpose:**  
Create a page listing players grouped by their primary position with tabbed navigation, similar to existing Chronological and Alphabetical pages.

**Requirements:**

**Display:**
- Players grouped by primary position
- Position tabs/links at top for navigation
- Current position highlighted (CSS class)
- Within each position: alphabetical by name
- Show career span for each player (debut-final years)

**Position Logic:**
- Primary position = first position in `pos` field
- Examples:
  - "P" → Pitcher
  - "OF" → Outfielder
  - "P/OF" → Pitcher (P is primary, listed under Pitchers)
  - "1B/OF" → First Baseman (1B is primary)
- Multiple positions in field separated by slash "/"

**Position Order (Traditional Baseball):**
- C (Catchers)
- 1B (First Basemen)
- 2B (Second Basemen)
- 3B (Third Basemen)
- SS (Shortstops)
- LF (Left Fielders)
- CF (Center Fielders)
- RF (Right Fielders)
- P (Pitchers)

**Navigation:**
- Clickable position tabs at top
- Can be anchor links (#pitchers) or JavaScript tabs
- Current position highlighted with CSS
- Click tab to jump/show that position's section

**Display Format:**
```html
<div class="position-nav">
  <a href="#c" class="pos-tab">C</a>
  <a href="#1b" class="pos-tab">1B</a>
  <a href="#2b" class="pos-tab">2B</a>
  <a href="#3b" class="pos-tab">3B</a>
  <a href="#ss" class="pos-tab">SS</a>
  <a href="#lf" class="pos-tab">LF</a>
  <a href="#cf" class="pos-tab">CF</a>
  <a href="#rf" class="pos-tab">RF</a>
  <a href="#p" class="pos-tab active">P</a>
</div>

<section id="p" class="position-group">
  <h2>Pitchers (P)</h2>
  <div class="player-list">
    <a href="https://i9s.org/wp/?player=ball-000wal">Walter Ball (1893-1923)</a><br>
    <a href="https://i9s.org/wp/?player=rogan-001bul">Bullet Rogan (1920-1937)</a><br>
    <a href="https://i9s.org/wp/?player=willia000str">Andrew Williams (1898-1903)</a><br>
    ...
  </div>
</section>

<section id="of" class="position-group">
  <h2>Outfielders (OF)</h2>
  <div class="player-list">
    <a href="https://i9s.org/wp/?player=hill-001pet">Pete Hill (1899-1925)</a><br>
    <a href="https://i9s.org/wp/?player=oms-000ale">Alejandro Oms (1917-1935)</a><br>
    ...
  </div>
</section>

[More sections for each position]
```

**Implementation:**
- Code snippet (similar to Chronological page approach)
- PHP function to parse primary position and group players
- Example code structure:
  ```php
  function fPlayersByPosition() {
      $players = pods('player');
      $players->find(array('limit' => -1, 'orderby' => 'last_name.meta_value ASC'));
      
      $positions = array();
      while ($players->fetch()) {
          $pos_field = $players->field('pos');
          $primary_pos = extractPrimaryPosition($pos_field); // Get first position
          $positions[$primary_pos][] = array(
              'name' => $players->display('first_name') . ' ' . $players->display('last_name'),
              'slug' => $players->display('slug'),
              'years' => $players->display('yrdebut') . '-' . $players->display('yrfinal')
          );
      }
      
      // Output navigation
      // Output each position section
  }
  
  function extractPrimaryPosition($pos_string) {
      $parts = explode('/', $pos_string);
      return trim($parts[0]); // First position is primary
  }
  ```

**Styling:**
- CSS for tabs (active state, hover effects)
- Player list formatting
- Responsive design

**Future Enhancement:**
- Show player count in each tab: "P (45)"
- Filter by era within position
- Show additional stats (BA for batters, ERA for pitchers)

---

## Phase 4: Workflow Optimization (Payoff)

### Task G: Data Input Tools

**Priority:** High (biggest time-saver long-term)  
**Estimated Time:** 4-5 hours  
**Dependencies:** Task B (only import raw stats), Task D (schema decisions affect pyr handling)

**Purpose:**  
Create tools for fast data entry from Google Sheet exports via copy/paste interface, eliminating manual typing of individual fields and dramatically speeding up data entry workflow.

**Current Workflow Pain:**
- Export data from Google Sheet
- Open WordPress admin
- Navigate to player
- Add new PlayerYear record
- Manually type each stat field
- Calculate and type pyr field manually
- Repeat for each year
- Extremely time-consuming for 10-20 years per player
- Error-prone (typos in numbers, wrong pyr format)

**Requirements:**

**Interface Location:**
- Add new tab to i9s Database Tools plugin
- "Import Player Stats" section

**Workflow:**

1. **Select Player**
   ```html
   <select name="player_id">
     <option value="">-- Select Player --</option>
     <option value="123">Alejandro Oms (oms-000ale)</option>
     <option value="456">Walter Ball (ball-000wal)</option>
     ...
   </select>
   ```

2. **Select Data Type**
   ```html
   <input type="radio" name="data_type" value="batting" checked> Batting
   <input type="radio" name="data_type" value="pitching"> Pitching
   ```

3. **Paste Data Area**
   ```html
   <label>Paste tab-separated data (one row per year):</label>
   <textarea name="paste_data" rows="10" cols="80" placeholder="Paste exported data from Google Sheet..."></textarea>
   ```

4. **Parse Button**
   ```html
   <button type="button" id="parse-data">Parse and Validate</button>
   ```

5. **Preview Table**
   - Shows parsed data in table format
   - Validation warnings for each row
   - User can review before importing
   ```html
   <table class="preview-table">
     <thead>
       <tr><th>Year</th><th>AB</th><th>H</th><th>2B</th>...<th>Status</th><th>Action</th></tr>
     </thead>
     <tbody>
       <tr class="valid">
         <td>1920</td><td>450</td><td>156</td><td>28</td>...
         <td>✓ Valid</td><td>Will Import</td>
       </tr>
       <tr class="warning">
         <td>1921</td><td>478</td><td>189</td><td>31</td>...
         <td>⚠ H (189) slightly high for AB (478)</td><td>Will Import</td>
       </tr>
       <tr class="error">
         <td>1922</td><td>412</td><td>450</td><td>24</td>...
         <td>❌ H (450) > AB (412) - INVALID</td><td>Will Skip</td>
       </tr>
     </tbody>
   </table>
   ```

6. **Import Buttons**
   ```html
   <button id="cancel-import">Cancel</button>
   <button id="confirm-import" class="button-primary">Import 3 Valid Years</button>
   ```

**Data Source Formats:**

**Batting Export (tab-separated):**
```
ID              Name              Year  AB   H   2B  3B  HR  BB  SO  SB  CS  BA    OBP   SLG   OPS    Status
allen-000tod    Herbert Allen     1911  63   11  1   1   3   7   2   1   0   .175  .212  .222  .434   Draft
allen-000tod    Herbert Allen     1913  107  26  3   1   1   5   3   5   2   .243  .277  .318  .595   Draft
```

**Pitching Export (tab-separated):**
```
ID              Name              Year  G   GS  IP    H    W   K   HR  eD  eT  eHBP  eWP  eBK  H/9  BB/9  K/9  WHIP  ERA   Status
willia000str    Andrew Williams   1898  19  16  130   144  39  46  2   7   3   0     0    0    10.0 2.7  3.2  1.41  3.51  Draft
willia000str    Andrew Williams   1899  20  15  123   156  40  30  3   7   3   0     0    0    11.4 2.9  2.2  1.59  4.29  Draft
```

**Import Behavior:**

- **Columns to Import (Batting):**
  - pid (from selected player)
  - yr (from Year column)
  - ab, h, 2b, 3b, hr, bb, so, sb, cs (from respective columns)
  - IGNORE: ID, Name, BA, OBP, SLG, OPS (calculated fields from Task B)
  
- **Columns to Import (Pitching):**
  - pid (from selected player)
  - yr (from Year column)
  - g, gs, ip, ha (as 'h' column), w, k, hra (as 'hr' column), ed, et, ehbp, ewp (as 'wp'), ebk (as 'bk')
  - IGNORE: ID, Name, H/9, BB/9, K/9, WHIP, ERA (calculated fields)

- **Status Column Handling:**
  - Updates player's i9s_level taxonomy
  - Valid values: "Draft", "Final", "Curated" (case-insensitive)
  - If all rows have same Status, set it once for the player
  - If rows have different Status values, use most common or most complete

- **pyr Field:**
  - Based on Task D decision:
    - If keeping: auto-generate as `{pid}.{yr}.b` or `{pid}.{yr}.p`
    - If removing: don't populate it
  - User never types this manually

- **Year Overwrite Behavior:**
  - If year already exists for player: **OVERWRITE** (replace all stats)
  - Rationale: This is a "here's the complete current projection" workflow
  - Show warning in preview: "⚠ 1920 exists - will overwrite"

**Validation Rules:**

**Batting:**
- AB must be numeric and > 0
- H ≤ AB (can't have more hits than at-bats)
- 2B + 3B + HR ≤ H (extra base hits can't exceed total hits)
- All counting stats ≥ 0 (no negatives)
- Warning if BA would be > .400 (unusual but possible)
- Warning if player has huge jump in AB year-over-year

**Pitching:**
- IP must be numeric with valid decimal (.0, .1, or .2 only)
- G, GS must be numeric and ≥ 0
- GS ≤ G (can't start more games than played)
- All counting stats ≥ 0
- Warning if ERA would be < 1.00 or > 10.00 (unusual)

**Error Handling:**

- **Parse Errors:**
  - Wrong number of columns
  - Non-numeric values where numbers expected
  - Show specific error: "Row 3: Expected number for AB, got 'abc'"
  
- **Validation Errors:**
  - Block import if critical errors (H > AB)
  - Allow import with warnings (high BA, missing data)
  - User can choose to skip error rows or cancel entirely

- **Database Errors:**
  - Use transactions (all years or none)
  - On error: ROLLBACK and show error message
  - Log details for debugging

**Transaction Implementation:**

```php
global $wpdb;
$wpdb->query('START TRANSACTION');

try {
    foreach ($valid_years as $year_data) {
        // Insert or update PlayerYear record
        $result = $wpdb->replace(
            $wpdb->prefix . 'pods_playeryear_b',
            array(
                'pid' => $player_pid,
                'yr' => $year_data['year'],
                'pyr' => $player_pid . '.' . $year_data['year'] . '.b',
                'ab' => $year_data['ab'],
                'h' => $year_data['h'],
                // ... all fields
            ),
            array('%s', '%d', '%s', '%d', '%d', ...) // formats
        );
        
        if ($result === false) {
            throw new Exception("Failed to insert year " . $year_data['year']);
        }
    }
    
    // Update player's i9s_level if Status provided
    if ($status_value) {
        wp_set_object_terms($player_id, $status_value, 'i9s_level');
    }
    
    $wpdb->query('COMMIT');
    
} catch (Exception $e) {
    $wpdb->query('ROLLBACK');
    wp_send_json_error(array('message' => $e->getMessage()));
}
```

**After Successful Import:**

- Success message: "✓ Successfully imported 12 years for Andrew Williams"
- Show summary:
  - Years added: 1898-1909
  - Updated i9s_level to: Draft
  - Total batting records: 12
  - Total pitching records: 0
- Link to view player page: "View Andrew Williams →"
- Clear paste area
- Keep player selected for easy additional imports
- Option: "Import Another Player" (clears selection)

**Technical Approach:**

1. **JavaScript Parsing:**
   ```javascript
   function parseTabSeparatedData(text, dataType) {
       const lines = text.trim().split('\n');
       const rows = lines.map(line => line.split('\t'));
       
       // Validate columns match expected format
       // Parse each row into object
       // Run validation on each row
       // Return array of parsed/validated rows
   }
   ```

2. **AJAX Preview:**
   ```javascript
   $('#parse-data').click(function() {
       const playerId = $('#player-select').val();
       const dataType = $('input[name=data_type]:checked').val();
       const pasteData = $('#paste-area').val();
       
       $.post(ajaxurl, {
           action: 'i9s_preview_import',
           player_id: playerId,
           data_type: dataType,
           paste_data: pasteData
       }, function(response) {
           displayPreviewTable(response.data);
       });
   });
   ```

3. **AJAX Import:**
   ```javascript
   $('#confirm-import').click(function() {
       // Send validated data to server
       // Show progress indicator
       // Display results
   });
   ```

**Benefits:**

- **Speed:** Import 10-20 years in seconds vs 10-20 minutes
- **Accuracy:** No manual typing of numbers (copy/paste from Sheet)
- **Validation:** Catch errors before they're saved
- **Consistency:** Auto-generate pyr, ensure format
- **i9s_level Management:** Automatically update player status
- **Scalability:** Can handle hundreds of players quickly

**Future Enhancements (not in initial version):**

- Bulk import multiple players at once
- Import from CSV file upload (not just paste)
- Preview changes before overwrite (diff view)
- Undo last import
- Import history/log
- Batch update i9s_level for multiple players

---

## Task Sequencing

### Recommended Order

1. **Task D** - Schema Review (foundational decision affects everything)
2. **Task A** - Master Player List (visibility and validation)
3. **Task B** - Formula Calculations (changes data model)
4. **Task C** - Career Totals (builds on Task B)
5. **Task E** - Search Links (quick win for morale)
6. **Task F** - By Position Page (nice-to-have feature)
7. **Task G** - Data Input Tools (benefits from all previous work)

### Alternative: Quick Wins First

If you prefer early visible progress:

1. **Task E** - Search Links (30 min, immediate improvement)
2. **Task A** - Master Player List (see all your data)
3. **Task D** - Schema Review (then fix foundation)
4. **Task B, C, F, G** - Continue in order

---

## Notes for Future Sessions

- This roadmap is a living document
- Update task status as work progresses
- Add actual time spent vs estimates
- Document decisions made during implementation
- Note any scope changes or discoveries
- Move completed tasks to "Completed" section
- Add new tasks as they're identified

---

**End of Roadmap**
