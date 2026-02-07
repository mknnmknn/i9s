i9s Technical Reference

Version Control and history information removed and moved to GitHub.

## 🎯 Quick Reference Cards

### Database Connection
```
Database: dmlco_wp471
User:     dmlco_wp471
Host:     localhost
Charset:  utf8mb4
Prefix:   wpsk_
```

### Core Tables
```
Players (CPT):     wpsk_posts + wpsk_postmeta
Batting Stats:     wpsk_pods_playeryear_b
Pitching Stats:    wpsk_pods_playeryear_p
Taxonomy:          wpsk_terms + wpsk_term_relationships
```

### Pod Names (for code)
```php
pods('player')         // Players CPT
pods('playeryear_b')   // Batting stats (ACT)
pods('playeryear_p')   // Pitching stats (ACT)
pods('i9s_level')      // Status taxonomy
```

### Key Field Names
```
Player:   pid, first_name, last_name, yrdebut, yrfinal, pos
Batting:  pid, yr, ab, h, 2b, 3b, hr, bb, so, sb, cs, ba, obp, slg, ops
Pitching: pid, yr, g, gs, ip, ha, w, k, hra, era, whip
```

---

## 📑 Table of Contents

1. [Project Overview](#project-overview)
2. [WordPress Configuration](#wordpress-configuration)
3. [Database Schema](#database-schema)
   - [Storage Architecture](#storage-architecture)
   - [Players (Custom Post Type)](#players-custom-post-type)
   - [PlayerYears_b (Batting Statistics)](#playeryears_b-batting-statistics)
   - [PlayerYears_p (Pitching Statistics)](#playeryears_p-pitching-statistics)
   - [i9s_level (Taxonomy)](#i9s_level-taxonomy)
4. [Site Pages & Features](#site-pages--features)
5. [Code Snippets](#code-snippets)
6. [Pods Templates](#pods-templates)
7. [Common Operations](#common-operations)
8. [Known Issues & Fixes](#known-issues--fixes)
9. [Development Guidelines](#development-guidelines)
10. [Plugin Versions](#plugin-versions)

---

## Project Overview

**Site URL:** https://www.i9s.org/  
**Purpose:** Historical baseball project modeling Negro League players' projected MLB careers  
**CMS:** WordPress with Pods Framework

### Player Statistics
- **Total Players:** 171
- **Published:** 139
- **Unpublished:** 32 (drafts, pending review, etc.)

### Technology Stack
- **WordPress Version:** [TBD - check in wp-admin]
- **Pods Framework:** 3.3.4
- **Code Snippets Pro:** 3.9.5
- **Hosting:** HostGator (cPanel)

---

## WordPress Configuration

**Last Verified:** February 7, 2026

### Database Settings (wp-config.php)
```php
define('DB_NAME', 'dmlco_wp471');
define('DB_USER', 'dmlco_wp471');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');
$table_prefix = 'wpsk_';
```

### Critical Pods Settings
```php
define('PODS_SHORTCODE_ALLOW_EVALUATE_TAGS', 'true');
```
**Purpose:** Allows Pods shortcodes to evaluate tags dynamically (required for WHERE clauses in templates)

### Pods Components Enabled
- Advanced Content Types
- Advanced Relationships
- Migrate: Packages
- Table Storage
- Templates

---

## Database Schema

### Storage Architecture

**Last Verified:** February 7, 2026

WordPress + Pods uses two different storage strategies:

#### Custom Post Types (CPT) - Meta Storage
**Used for:** Players  
**Storage Location:** WordPress core tables

```
wpsk_posts (core data)
├── post_title = "Alejandro Oms"
├── post_type = 'player'
├── post_name = 'oms-000ale' (slug for URLs)
├── post_status = 'publish' | 'draft' | etc.
└── post_content = editor content

wpsk_postmeta (custom fields)
├── pid = 'oms-000ale'
├── first_name = 'Alejandro'
├── last_name = 'Oms'
├── yrdebut = 1917
└── [all other Pods fields]

wpsk_term_relationships + wpsk_terms (taxonomies)
└── i9s_level assignments
```

**Benefits:**
- Full WordPress functionality (revisions, authors, publishing workflow)
- Native WordPress queries work
- Compatible with all WP plugins

#### Advanced Content Types (ACT) - Table Storage
**Used for:** PlayerYears_b, PlayerYears_p  
**Storage Location:** Dedicated tables

```
wpsk_pods_playeryear_b (batting stats)
wpsk_pods_playeryear_p (pitching stats)
```

**Benefits:**
- Better performance for large datasets
- Simpler queries (direct SQL)
- Cleaner data structure
- No meta_key/meta_value overhead

---

### Players (Custom Post Type)

**Last Verified:** February 7, 2026

**Pod Configuration:**
- **Internal Name:** `player`
- **Label:** Players
- **Type:** Custom Post Type (CPT)
- **Storage:** Meta (wpsk_postmeta)
- **Pod ID:** 13
- **Public:** Yes
- **REST API:** Enabled

#### Player Fields Reference

| Field Name | Label | Type | Max | Required | Storage | Description |
|------------|-------|------|-----|----------|---------|-------------|
| `pid` | Player ID | text | 16 | ✅ Yes | postmeta | **Primary identifier** (e.g., oms-000ale) |
| `first_name` | First Name | text | 100 | No | postmeta | Player's first name |
| `last_name` | Last Name | text | 255 | No | postmeta | Player's last name |
| `nickname` | Nickname | text | 255 | No | postmeta | Player's nickname |
| `playerintro` | playerIntro | paragraph | ∞ | No | postmeta | Intro text about player |
| `pos` | Position | text | 4 | No | postmeta | Position (OF, P, 1B, etc.) |
| `yob` | yob | number | 4 | No | postmeta | Year of birth |
| `dob` | dob | date | - | No | postmeta | Date of birth (m/d/y) |
| `yrdebut` | Debut Year | number | 4 | No | postmeta | Career start year |
| `yrfinal` | Final Year | number | 4 | No | postmeta | Career end year |
| `birth_nation` | Birth Nation | text | 255 | No | postmeta | Country of birth |
| `birth_city` | Birth City | text | 255 | No | postmeta | City of birth |
| `birth_state` | Birth State | text | 255 | No | postmeta | State of birth |
| `height` | Height | text | 5 | No | postmeta | Height (e.g., 5'10") |
| `weight` | Weight | number | 3 | No | postmeta | Weight in pounds |
| `b` | B | text | 1 | No | postmeta | Bats (L/R/S) |
| `t` | T | text | 1 | No | postmeta | Throws (L/R) |
| `bpaste` | bPaste | paragraph | ∞ | No | postmeta | Batting import field |
| `ppaste` | pPaste | paragraph | ∞ | No | postmeta | Pitching import field |
| `notes` | Notes | wysiwyg | ∞ | No | postmeta | Editorial notes |

#### WordPress Core Fields (wpsk_posts)
| Field Name | Description |
|------------|-------------|
| `post_title` | Player's full name (display) |
| `post_name` | URL slug (usually matches pid) |
| `post_content` | Editor content (rarely used, template overrides) |
| `post_status` | publish, draft, pending, private |
| `post_modified` | Last modified date |

#### Taxonomies
- **status** - Built-in WordPress taxonomy
- **i9s_level** - Custom taxonomy for publication workflow

#### URL Structure
```
Single Player: https://i9s.org/wp/?player={slug}
Example:       https://i9s.org/wp/?player=oms-000ale
```

#### Templates Applied
- **fullPlayerDisplay** - Auto-appended to post content
- **pfat_filter_single:** the_content
- **pfat_append_single:** append

---

### PlayerYears_b (Batting Statistics)

**Last Verified:** February 7, 2026

**Pod Configuration:**
- **Internal Name:** `playeryear_b`
- **Label:** PlayerYears_b
- **Type:** Pod (Advanced Content Type)
- **Storage:** Table (wpsk_pods_playeryear_b)
- **Pod ID:** 70
- **Index Field:** pyr

#### Database Table Structure

**Table:** `wpsk_pods_playeryear_b`

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| `id` | bigint | No | PRI | Auto-increment primary key |
| `pyr` | varchar | Yes | | PlayerYear composite key (e.g., oms-000ale1925) |
| `pid` | varchar | Yes | | **Foreign key to Player.pid** |
| `yr` | decimal | Yes | | Season year |
| `ab` | decimal | Yes | | At Bats |
| `h` | decimal | Yes | | Hits |
| `2b` | decimal | Yes | | Doubles |
| `3b` | decimal | Yes | | Triples |
| `hr` | decimal | Yes | | Home Runs |
| `bb` | decimal | Yes | | Walks |
| `so` | decimal | Yes | | Strikeouts |
| `sb` | decimal | Yes | | Stolen Bases |
| `cs` | decimal | Yes | | Caught Stealing |
| `ba` | decimal(6,3) | Yes | | Batting Average |
| `obp` | decimal(6,3) | Yes | | On-Base Percentage |
| `slg` | decimal(6,3) | Yes | | Slugging Percentage |
| `ops` | decimal(6,3) | Yes | | On-Base + Slugging |
| `created` | datetime | Yes | | Record creation timestamp |
| `modified` | datetime | Yes | | Last modified timestamp |

**Indexes:**
- PRIMARY KEY: `id`
- Index recommended on: `pid`, `yr` (for performance)

**Relationships:**
- Text-based join to `Player.pid` (not a Pods relationship object)
- One player can have many batting records (one per year)

---

### PlayerYears_p (Pitching Statistics)

**Last Verified:** February 7, 2026

**Pod Configuration:**
- **Internal Name:** `playeryear_p`
- **Label:** PlayerYears_p
- **Type:** Pod (Advanced Content Type)
- **Storage:** Table (wpsk_pods_playeryear_p)
- **Pod ID:** 2063
- **Index Field:** pyr

#### Database Table Structure

**Table:** `wpsk_pods_playeryear_p`

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| `id` | bigint | No | PRI | Auto-increment primary key |
| `pyr` | varchar | Yes | | PlayerYear composite key |
| `pid` | varchar | Yes | | **Foreign key to Player.pid** |
| `yr` | decimal | Yes | | Season year |
| `g` | decimal | Yes | | Games Pitched |
| `gs` | decimal | Yes | | Games Started |
| `ip` | decimal(7,1) | Yes | | Innings Pitched |
| `ha` | decimal | Yes | | Hits Allowed |
| `w` | decimal | Yes | | Walks Allowed |
| `k` | decimal | Yes | | Strikeouts |
| `hra` | decimal | Yes | | Home Runs Allowed |
| `ed` | decimal | Yes | | Doubles Allowed (est) |
| `et` | decimal | Yes | | Triples Allowed (est) |
| `ehbp` | decimal | Yes | | HBP Allowed (est) |
| `wp` | decimal | Yes | | Wild Pitches Allowed (est) |
| `bk` | decimal | Yes | | Balks (est) |
| `h_9` | decimal(6,1) | Yes | | Hits per 9 IP |
| `bb_9` | decimal(6,1) | Yes | | Walks per 9 IP |
| `k_9` | decimal(6,1) | Yes | | Strikeouts per 9 IP |
| `whip` | decimal(6,2) | Yes | | Walks + Hits per IP |
| `era` | decimal(6,2) | Yes | | Earned Run Average |
| `created` | datetime | Yes | | Record creation timestamp |
| `modified` | datetime | Yes | | Last modified timestamp |

**Indexes:**
- PRIMARY KEY: `id`
- Index recommended on: `pid`, `yr` (for performance)

**Relationships:**
- Text-based join to `Player.pid` (not a Pods relationship object)
- One player can have many pitching records (one per year)

---

### i9s_level (Taxonomy)

**Last Verified:** February 7, 2026

**Pod Configuration:**
- **Internal Name:** `i9s_level`
- **Label:** i9s Levels
- **Type:** Custom Taxonomy
- **Storage:** Meta
- **Pod ID:** 164
- **Hierarchical:** No
- **Applied to:** player post type

**Purpose:** Publication workflow / quality control status

**Storage Tables:**
- `wpsk_terms` - Term names
- `wpsk_term_taxonomy` - Taxonomy assignment
- `wpsk_term_relationships` - Player-to-term relationships

---

## Site Pages & Features

**Last Verified:** February 7, 2026

### Available Players (Alphabetical)

**URL:** https://i9s.org/wp/available-players-alphabetical/

**Purpose:** Lists currently available players sorted A-Z with career spans

**Implementation:**
```
[pods name="player" orderby="last_name" pagination="true" pagination_type="true_simple" limit="30"]
{@post_title} [{@yrdebut} - {@yrfinal}]
[/pods]
```

**Features:**
- Ordered by last name
- Pagination enabled (30 per page)
- Simple prev/next pagination style
- Shows player name and career span

**Output Format:**
```
Alejandro Oms [1917 - 1935]
Walter Ball [1893 - 1923]
Pete Hill [1899 - 1925]
...
```

---

### Available Players (Chronological)

**URL:** https://i9s.org/wp/available-players-chronological/

**Purpose:** Lists players sorted by debut year, grouped by year with headers

**Implementation:**
```
[code_snippet id=12 php=true]
```

**Uses Code Snippet:** fPlayersByDebut (see Code Snippets section)

**Features:**
- Ordered by debut year, then last name
- Year headers for grouping
- Shows all players (no pagination)
- Clickable player names linking to profiles

**Output Format:**
```
1893
Walter Ball (1893 - 1923)

1899
Pete Hill (1899 - 1925)

1917
Alejandro Oms (1917 - 1935)
...
```

**Why a Code Snippet?**
- Pods shortcodes can't easily do dynamic grouping/headers
- Requires state tracking between rows ($curYr variable)
- Needs conditional logic for header insertion
- Custom HTML output for each group

---

### Most Recent Player Updates

**URL:** [TBD - add when found]

**Purpose:** Shows recently modified players with modification dates

**Implementation:**
```
[pods name="player" orderby="post_modified DESC" limit="30"]
{@post_title} ({@post_modified, fDateDisplay_mmddyyyy})
[/pods]
```

**Features:**
- Ordered by modification date (newest first)
- Shows 30 most recent updates
- Calls fDateDisplay_mmddyyyy snippet for date formatting
- Useful for tracking editorial progress

**Output Format:**
```
Alejandro Oms (02/07/2026)
Walter Ball (02/06/2026)
Pete Hill (02/05/2026)
...
```

---

### Individual Player Pages

**URL Pattern:** https://i9s.org/wp/?player={slug}

**Template:** fullPlayerDisplay (auto-appended to content)

**Structure:**
1. Player intro text (playerIntro field)
2. Player information table (tblPlayerInfo template)
3. Batting projections heading + table (tblPlayerYears_B template)
4. Pitching projections heading + table (tblPlayerYears_P template)

**See Pods Templates section for template code.**

---

## Code Snippets

**Last Verified:** February 7, 2026  
**Plugin:** Code Snippets Pro 3.9.5  
**Documentation:** https://codesnippets.pro/docs/

### Why Code Snippets?

Code Snippets allows running PHP code without editing theme's functions.php. Used for:
- Complex sorting and grouping logic
- Custom display formatting
- Utility functions (date formatting)
- Features that Pods templates can't easily handle

---

### Snippet #1: snpPlayersByDebut (HTML)

**Type:** HTML snippet  
**Purpose:** Shortcode wrapper for chronological player display

**Content:**
```html
[code_snippet id=12 php=true]
```

**Usage:** Embedded in "Available Players (Chronological)" page

**Function:** Calls the fPlayersByDebut PHP function

---

### Snippet #2: fPlayersByDebut (PHP, ID=12)

**Type:** PHP function  
**Purpose:** Generate chronological player listing grouped by debut year

**Code:**
```php
function fPlayersByDebut() {
    $aParms = array (
        'orderby' => 'yrdebut.meta_value ASC, last_name.meta_value ASC',
        'limit' => -1
    );
    $aPlayers = pods('player', $aParms, true);
    
    if ( $aPlayers == false ) {
        echo 'podcall is false';        
    } else {
        $curYr = 0;
        $curRowYr = 0;
        while ($aPlayers->fetch () ) {
            $curRowYr = $aPlayers->field('yrdebut');
            $nameLink = '<a href="i9s.org/wp/?player='.$aPlayers->display('slug').'">'.$aPlayers->display('first_name').' '.$aPlayers->display('last_name').'</a> ('.$aPlayers->display('yrdebut').' - '.$aPlayers->display('yrfinal').')<br/>';
            if ($curRowYr != $curYr) {
                echo '<br/><h1>'.$aPlayers->display('yrdebut').'</h1>';
                echo $nameLink;
                $curYr = $curRowYr;
            } else {
                echo $nameLink;
            }
        }
    }
}
```

**How It Works:**
1. Queries all players ordered by debut year, then last name
2. Loops through results tracking current year
3. When year changes, outputs new H1 header
4. Outputs player link with career span for each player
5. Groups consecutive players under same year

**Why Not a Pods Template?**
- Requires state tracking between rows ($curYr)
- Conditional header insertion based on field changes
- Complex control flow (if/else logic)
- Pods templates excel at formatting individual items, not cross-record logic

---

### Snippet #3: fDateDisplay_mmddyyyy (PHP)

**Type:** PHP function  
**Purpose:** Format date helper for consistent date display

**Code:**
```php
function fDateDisplay_mmddyyyy ($dt) {
    return date("m/d/Y", strtotime($dt));
}
```

**Usage:** Called from Pods shortcodes for date formatting

**Example:**
```
{@post_modified, fDateDisplay_mmddyyyy}
```
Outputs: `02/07/2026`

**Input:** Any date string PHP can parse  
**Output:** MM/DD/YYYY format

---

## Pods Templates

**Last Verified:** February 7, 2026

### Template: fullPlayerDisplay

**Used On:** Player single post pages (auto-appended to content)  
**Purpose:** Complete player profile display

**Code:**
```
[pods name="player" slug="{@pid}"]{@playerintro}[/pods]

[pods name="player" template="tblPlayerInfo" slug="{@pid}"]
<p>&nbsp;</p>
<h3>Batting Projections</h3>
[pods name="playeryear_b" template="tblPlayerYears_B" where="pid = '{@pid}'" limit="-1"]
<p>&nbsp;</p>
<h3>Pitching Projections</h3>
[pods name="playeryear_p" template="tblPlayerYears_P" where="pid = '{@pid}'" limit="-1"]
```

**Structure:**
1. Player intro text
2. Player info table
3. Batting stats section (heading + table)
4. Pitching stats section (heading + table)

**⚠️ Security Note:** WHERE clause uses {@pid} which may trigger Pods security if pid contains SQL syntax like `--` (see Known Issues)

---

### Template: tblPlayerInfo

**Pod Reference:** player  
**Purpose:** Display biographical information table

**Code:**
```html
[before]<table>[/before]
<tr><td class="hdg">Name</td><td class="hdg">{@first_name} {@last_name}[if nickname] ({@nickname})[/if]</td></tr>
<tr><td class="hdg">ID / Status</td><td>{@pid} / <a href="https://i9s.org/wp/?page_id=213">{@i9s_level}</a></td></tr>
<tr><td class="hdg">Pos</td><td>{@pos}</td></tr>
<tr><td class="hdg">DOB / i9s Career</td><td>[if dob]{@dob}[else]{@yob}[/if] / {@yrdebut} - {@yrfinal}</td></tr>
<tr><td class="hdg">Birthplace</td><td>[if birth_city]{@birth_city}, [/if][if birth_state]{@birth_state} [/if]({@birth_nation})</td></tr>
<tr><td class="hdg">Height / Weight</td><td>{@height} / {@weight}</td></tr>
<tr><td class="hdg">B / T</td><td>{@b} / {@t}</td></tr>
[after]</table>[/after]
```

**Features:**
- Conditional display ([if] tags)
- Linked i9s_level to status page
- Shows DOB if available, otherwise YOB
- Combines multiple fields (name, birthplace)

---

### Template: tblPlayerYears_B

**Pod Reference:** playeryear (batting)  
**Purpose:** Year-by-year batting statistics table

**Code:**
```html
[before]<table><tr><td class="hdg">Year</td><td class="hdg">AB</td><td class="hdg">H</td><td class="hdg">2B</td><td class="hdg">3B</td><td class="hdg">HR</td><td class="hdg">BB</td><td class="hdg">SO</td><td class="hdg">SB</td><td class="hdg">CS</td><td class="hdg">BA</td><td class="hdg">OBP</td><td class="hdg">SLG</td><td class="hdg">OPS</td></tr>[/before]
[if ab]
<tr><td class="hdg">{@yr}</td><td>{@ab}</td><td>{@h}</td><td>{@2b}</td><td>{@3b}</td><td>{@hr}</td><td>{@bb}</td><td>{@so}</td><td>{@sb}</td><td>{@cs}</td><td>{@ba}</td><td>{@obp}</td><td>{@slg}</td><td>{@ops}</td></tr>
[/if]
[after]</table>[/after]
```

**Filter:** Only displays years where `ab` (at bats) exists  
**Called From:** fullPlayerDisplay template with WHERE clause on pid

---

### Template: tblPlayerYears_P

**Pod Reference:** playeryear_p (pitching)  
**Purpose:** Year-by-year pitching statistics table

**Code:**
```html
[before]<table><tr><td class="hdg">Year</td><td class="hdg">G</td><td class="hdg">GS</td><td class="hdg">IP</td><td class="hdg">HA</td><td class="hdg">W</td><td class="hdg">K</td><td class="hdg">HRA</td><td class="hdg">eD</td><td class="hdg">eT</td><td class="hdg">eHBP</td><td class="hdg">BB/9</td><td class="hdg">H/9</td><td class="hdg">K/9</td><td class="hdg">ERA</td><td class="hdg">WHIP</td></tr>[/before]
[if g]
<tr><td class="hdg">{@yr}</td><td>{@g}</td><td>{@gs}</td><td>{@ip}</td><td>{@ha}</td><td>{@w}</td><td>{@k}</td><td>{@hra}</td><td>{@ed}</td><td>{@et}</td><td>{@ehbp}</td><td>{@bb_9}</td><td>{@h_9}</td><td>{@k_9}</td><td>{@era}</td><td>{@whip}</td></tr>
[/if]
[after]</table>[/after]
```

**Filter:** Only displays years where `g` (games pitched) exists  
**Called From:** fullPlayerDisplay template with WHERE clause on pid

---

## Common Operations

**Last Verified:** February 7, 2026

### Querying Players (PHP)

#### Get All Published Players
```php
$players = pods('player');
$players->find(array(
    'limit' => -1,              // Unlimited results (default is 15!)
    'orderby' => 'last_name.meta_value ASC'
));

while ($players->fetch()) {
    $player_id = $players->field('pid');
    $name = $players->field('post_title');
    $debut = $players->field('yrdebut');
    // ...
}
```

#### Get All Players (Including Drafts)
```php
$players = pods('player');
$players->find(array(
    'limit' => -1,
    'status' => 'any'           // Include all post statuses
));
```

**⚠️ CRITICAL:** Always specify `'limit' => -1` or Pods defaults to 15 results!

#### Get Specific Player by PID
```php
$player = pods('player');
$player->find(array(
    'where' => "pid.meta_value = 'oms-000ale'",
    'limit' => 1
));

if ($player->total() > 0) {
    $player->fetch();
    $name = $player->field('post_title');
}
```

---

### Querying Player Statistics (SQL)

#### Get Batting Stats for a Player
```php
global $wpdb;
$batting = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}pods_playeryear_b 
     WHERE pid = %s 
     ORDER BY yr ASC",
    'oms-000ale'
));

foreach ($batting as $year) {
    echo $year->yr . ': ' . $year->ba . ' BA<br>';
}
```

#### Get Pitching Stats for a Player
```php
global $wpdb;
$pitching = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}pods_playeryear_p 
     WHERE pid = %s 
     ORDER BY yr ASC",
    'oms-000ale'
));
```

#### Count Total Records
```php
global $wpdb;
$batting_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}pods_playeryear_b 
     WHERE pid = %s",
    'oms-000ale'
));
```

---

### Using Pods Shortcodes

#### Simple Player List
```
[pods name="player" orderby="last_name" limit="30"]
{@post_title} ({@yrdebut} - {@yrfinal})
[/pods]
```

#### With Pagination
```
[pods name="player" orderby="last_name" pagination="true" pagination_type="true_simple" limit="30"]
{@post_title}
[/pods]
```

#### Filtered by Taxonomy
```
[pods name="player" where="i9s_level.slug = 'published'" limit="-1"]
{@post_title}
[/pods]
```

#### Calling Snippet Functions
```
[pods name="player" orderby="post_modified DESC" limit="30"]
{@post_title} ({@post_modified, fDateDisplay_mmddyyyy})
[/pods]
```

---

### Updating Records

#### Update Player Field
```php
$player = pods('player', $post_id);
$player->save('pid', 'new-player-id');
```

#### Update Batting Record (Direct SQL)
```php
global $wpdb;
$wpdb->update(
    $wpdb->prefix . 'pods_playeryear_b',
    array('pid' => 'new-id'),      // Data to update
    array('pid' => 'old-id'),      // Where clause
    array('%s'),                   // Data format
    array('%s')                    // Where format
);
```

#### Transaction for Multi-Table Updates
```php
global $wpdb;
$wpdb->query('START TRANSACTION');

try {
    // Update player
    update_post_meta($post_id, 'pid', $new_id);
    
    // Update batting
    $wpdb->update(
        $wpdb->prefix . 'pods_playeryear_b',
        array('pid' => $new_id),
        array('pid' => $old_id),
        array('%s'),
        array('%s')
    );
    
    // Update pitching
    $wpdb->update(
        $wpdb->prefix . 'pods_playeryear_p',
        array('pid' => $new_id),
        array('pid' => $old_id),
        array('%s'),
        array('%s')
    );
    
    $wpdb->query('COMMIT');
} catch (Exception $e) {
    $wpdb->query('ROLLBACK');
    error_log('Update failed: ' . $e->getMessage());
}
```

## Known Issues & Fixes

**Last Verified:** February 7, 2026

### Issue #1: PlayerIDs with Multiple Consecutive Dashes

**Status:** ⚠️ ACTIVE - 3 players affected

**Problem:**  
PlayerIDs containing multiple consecutive dashes cause Pods template errors:
```
Pods Embed Error: WHERE contains SQL that is not allowed.
```

**Root Cause:**  
Pods security interprets `--` as SQL comment syntax and blocks WHERE clauses containing it.

**Players Affected:**
- Alejandro Oms: `oms---000ale` (triple dash) - 19 batting, 0 pitching records
- Pete Hill: `hill--001pet` (double dash) - 20 batting, 0 pitching records
- Walter Ball: `ball--000wal` (double dash) - 0 batting, 12 pitching records

**Impact:**
- Player pages display template error instead of year-by-year stats
- Stats exist in database but can't be displayed via templates

**Solution:**  
Fix PlayerIDs to use single dashes:
- `oms---000ale` → `oms-000ale`
- `hill--001pet` → `hill-001pet`
- `ball--000wal` → `ball-000wal`

**Update Required in 3 Locations:**
1. Player CPT: `wpsk_postmeta` WHERE `meta_key = 'pid'`
2. Batting records: `wpsk_pods_playeryear_b.pid`
3. Pitching records: `wpsk_pods_playeryear_p.pid`

**Tool:** i9s Database Tools plugin v1.0.8+

**Fix Process:**
1. Install i9s Database Tools plugin
2. Run "Scan for Issues" to verify affected players
3. Review results carefully
4. Click "Fix PlayerIDs" to update all 3 locations atomically
5. Verify player pages display correctly
6. Clear any site caching

---

### Issue #2: Pods Query Default Limit

**Status:** ⚠️ CAUTION - Common mistake

**Problem:**  
`$pods->find()` without parameters defaults to 15 results, not all records.

**Impact:**
- Queries return only first 15 players alphabetically
- Loops appear to work but silently skip most data
- Not obvious without checking totals

**Solution:**  
Always specify limit explicitly:
```php
// WRONG - only gets 15 players
$players = pods('player');
$players->find();

// RIGHT - gets all players
$players = pods('player');
$players->find(array('limit' => -1));
```

**Best Practice:**  
Always use `array('limit' => -1)` for unlimited results.

---

### Issue #3: find() Must Come Before total()

**Status:** ⚠️ CAUTION - Common mistake

**Problem:**  
Calling `$pods->total()` before `$pods->find()` returns NULL.

**Example:**
```php
// WRONG - total() returns NULL
$players = pods('player');
if ($players->total() == 0) {  // This is NULL!
    echo 'No players';
}

// RIGHT - call find() first
$players = pods('player');
$players->find(array('limit' => -1));
$total = $players->total();  // Now returns actual count
```

---

### Issue #4: Published vs All Players

**Status:** 📋 DOCUMENTED - Expected behavior

**Current Behavior:**  
Pods queries default to `post_status = 'publish'`, returning only 139 of 171 players.

**Unpublished Players:** 32 (drafts, pending, private, etc.)

**When This Matters:**
- Scanning for data issues across all players
- Import/export operations
- Batch processing tools

**Solution:**  
Add `'status' => 'any'` to include all post statuses:
```php
$players = pods('player');
$players->find(array(
    'limit' => -1,
    'status' => 'any'
));
```

**Future Enhancement:**  
i9s Database Tools plugin should offer option to scan all players regardless of status.

---

## Development Guidelines

**Last Verified:** February 7, 2026

### Pods Best Practices

#### Always Specify Limits
```php
// DEFAULT (BAD): Gets only 15 results
$players = pods('player');
$players->find();

// CORRECT: Gets all results
$players = pods('player');
$players->find(array('limit' => -1));
```

#### Call find() Before total()
```php
// WRONG: total() returns NULL
$players = pods('player');
$count = $players->total();

// RIGHT: Call find() first
$players = pods('player');
$players->find(array('limit' => -1));
$count = $players->total();
```

#### Always Use Prepared Statements for SQL
```php
// WRONG: SQL injection vulnerability
$wpdb->query("SELECT * FROM {$wpdb->prefix}pods_playeryear_b WHERE pid = '$pid'");

// RIGHT: Use prepare()
$wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}pods_playeryear_b WHERE pid = %s",
    $pid
));
```

#### Use Transactions for Multi-Table Updates
```php
global $wpdb;
$wpdb->query('START TRANSACTION');
try {
    // Multiple updates here
    $wpdb->query('COMMIT');
} catch (Exception $e) {
    $wpdb->query('ROLLBACK');
}
```

---

### PlayerID Format Standards

**Standard Format:** `lastname-XXXfirstname`

**Components:**
- `lastname`: First 3-6 chars of last name (lowercase)
- `-`: **Single dash separator** (CRITICAL)
- `XXX`: Sequential number (001, 002, 003...) for uniqueness
- `firstname`: First 3 chars of first name (lowercase)

**Examples:**
- Alejandro Oms → `oms-000ale`
- Walter Ball → `ball-000wal`
- Pete Hill → `hill-001pet`

**Rules:**
- All lowercase
- **Only ONE dash** between components
- Pad number with zeros (001 not 1)
- Must be unique across all players
- Max 16 characters

**⚠️ CRITICAL:** Never use multiple consecutive dashes (`--`, `---`, etc.)

---

### Data Relationships

**Current Implementation:** Text-based joins

```php
// How it works now
Player.pid = "oms-000ale" (stored in wpsk_postmeta)
PlayerYears_b.pid = "oms-000ale" (stored in wpsk_pods_playeryear_b)
PlayerYears_p.pid = "oms-000ale" (stored in wpsk_pods_playeryear_p)

// Joined via WHERE clause
[pods name="playeryear_b" where="pid = '{@pid}'"]
```

**Pros:**
- Simple to understand
- Works with any text value
- Easy to query directly via SQL

**Cons:**
- No referential integrity
- Orphaned records possible
- Bulk updates require touching all 3 locations
- No cascade delete

**Future Consideration:**  
Convert to native Pods relationships for:
- Automatic referential integrity
- Cascade operations
- Better UI for managing relationships
- Native Pods traversal syntax

---

### Performance Considerations

#### Indexing
Current tables should have indexes on:
- `wpsk_pods_playeryear_b.pid`
- `wpsk_pods_playeryear_p.pid`
- Both tables: `yr` field

**Verify indexes:**
```sql
SHOW INDEX FROM wpsk_pods_playeryear_b;
SHOW INDEX FROM wpsk_pods_playeryear_p;
```

#### Query Optimization
```php
// SLOW: Queries in loop
while ($players->fetch()) {
    $batting = $wpdb->get_results("SELECT * FROM ... WHERE pid = '" . $players->field('pid') . "'");
}

// FAST: Single query with JOIN or IN clause
$pids = array();
while ($players->fetch()) {
    $pids[] = $players->field('pid');
}
$batting = $wpdb->get_results("SELECT * FROM ... WHERE pid IN ('" . implode("','", $pids) . "')");
```

#### Caching
Consider caching for:
- Player lists (alphabetical, chronological)
- Aggregate statistics
- Most recent updates

Use WordPress transients:
```php
$players = get_transient('i9s_player_list');
if ($players === false) {
    // Generate list
    set_transient('i9s_player_list', $players, HOUR_IN_SECONDS);
}
```

---

### When to Use Snippets vs Templates

**Use Code Snippets When:**
- Need complex PHP logic (loops, conditions, state tracking)
- Grouping/aggregating across multiple records
- Custom sorting with multiple fields
- Dynamic header insertion
- Calling external APIs or libraries

**Use Pods Templates When:**
- Formatting individual records
- Simple conditionals ([if] tags)
- Displaying relationships
- Standard list/table layouts
- No cross-record logic needed

**Example Decision:**
- **Alphabetical list:** Pods shortcode ✓
- **Chronological with year headers:** Code snippet ✓
- **Player info table:** Pods template ✓
- **Stats table:** Pods template ✓

---

## Plugin Versions

**Last Verified:** February 7, 2026

### Pods Framework
**Version:** 3.3.4  
**Purpose:** Custom content types and fields  
**Components Enabled:**
- Advanced Content Types
- Advanced Relationships
- Migrate: Packages
- Table Storage
- Templates

### Code Snippets Pro
**Version:** 3.9.5  
**Purpose:** Run PHP/HTML snippets without editing theme  
**Documentation:** https://codesnippets.pro/docs/  
**Active Snippets:** 3

### i9s Database Tools (Custom Plugin)
**Current Version:** 1.0.8  
**Purpose:** Database maintenance and PlayerID fixes

**Changelog:**
- **v1.0.8** - Fixed Pods query limit (added `limit => -1`), fixed field name consistency (`total_players`)
- **v1.0.7** - Added debug display to issues view
- **v1.0.6** - Removed sample limit
- **v1.0.5** - Increased sample size
- **v1.0.4** - Pod name consistency
- **v1.0.3** - Fixed find() before total() order
- **v1.0.2** - Debug improvements
- **v1.0.1** - Pod name plural fix
- **v1.0.0** - Initial release

---

## Maintenance Checklist

### Monthly Tasks
- [ ] Verify plugin versions (Pods, Code Snippets)
- [ ] Check for orphaned PlayerYear records (pid not in Players)
- [ ] Review unpublished players (32 drafts - still needed?)
- [ ] Verify all player pages display correctly (random sampling)

### After Data Imports
- [ ] Verify PlayerID format (no multiple dashes)
- [ ] Check for duplicate pids
- [ ] Confirm batting/pitching records linked correctly
- [ ] Update "Most Recent Player Updates" page timestamp

### Before Major Updates
- [ ] Backup database (`dmlco_wp471`)
- [ ] Export Pods configuration (Migrate: Packages)
- [ ] Document current state (update this file!)
- [ ] Test on staging if available

---

## Update Log

**How to Update This Document:**

1. Change version number at top
2. Add entry to Document Changelog
3. Update "Last Verified" dates in relevant sections
4. Commit changes with descriptive message

**Sections Most Likely to Need Updates:**
- Plugin Versions (after WP/Pods updates)
- Known Issues (as issues are resolved)
- Site Pages & Features (when adding new pages)
- Code Snippets (when adding/modifying snippets)

---

**End of Technical Reference v2.0**

*For questions or updates, consult with development team.*
*This is a living document - keep it current!*
