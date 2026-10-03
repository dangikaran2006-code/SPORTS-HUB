# SportsHub - Multi-Sport Tournament Management Platform 🏆

![SportsHub Platform](https://img.shields.io/badge/Platform-SportsHub-00e676?style=for-the-badge)
![Tech Stack](https://img.shields.io/badge/Stack-PHP%20%7C%20MySQL%20%7C%20HTML5%20%7C%20CSS3%20%7C%20JS-3b82f6?style=for-the-badge)
![Version](https://img.shields.io/badge/Version-1.0.0-f59e0b?style=for-the-badge)

**SportsHub** is a next-generation multi-sport tournament management web application designed for tournament organizers, team managers, referees, players, and sports fans. It provides comprehensive tools for creating and managing tournaments, teams, player rosters, match schedules, live scoring, points tables, and performance statistics across 8 major sports.

---

## ⚽ Supported Sports

SportsHub is architected dynamically to support sport-specific scoring rules without being hard-coded to a single sport:

1. 🏏 **Cricket** (*Runs, Wickets, Overs, Net Run Rate calculation*)
2. ⚽ **Football** (*Goals, Halves, Clean Sheets, Goal Difference*)
3. 🤼 **Kabaddi** (*Raid Points, Tackle Points, Super Raids*)
4. 🏀 **Basketball** (*Quarters, Points, Field Goals*)
5. 🏐 **Volleyball** (*Sets, Set Points*)
6. 🏸 **Badminton** (*Sets, Game Points*)
7. 🎾 **Tennis** (*Sets, Games, Tie-breakers*)
8. 🏓 **Table Tennis** (*Sets, Points*)

---

## 🎨 Design System & UI Principles

- **Foundation**: Dark Navy sports-tech layout (`#070b14`, `#0e1526`, `#141e33`).
- **Accents**: Emerald Green (`#00e676`) for active/win states and Glowing Pulsing Red (`#ef4444`) for `LIVE` match indicators.
- **Typography**: Google Fonts `Outfit` (Headings) and `Inter` (UI & Tables).
- **Components**: Glassmorphic rounded cards (`12px` to `16px` radius), responsive data tables with sport badges, status pills, toast notifications, and interactive modals.

---

## 📁 Directory Structure

```
/project 1
├── index.php                 # Main entrance routing to dashboard
├── README.md                 # Complete project documentation & setup guide
├── BRAINME.md                # System architecture & developer documentation
│
├── database/
│   ├── schema.sql            # Normalized MySQL Database Schema
│   └── seed.sql              # Realistic seed data for 8 supported sports
│
├── includes/
│   ├── config.php            # App constants, session init, & badge helpers
│   ├── database.php          # PDO DB Connection & Mock Data Provider
│   ├── header.php            # Top navbar, global search, LIVE status indicator
│   ├── sidebar.php           # Collapsible sidebar with active route highlighting
│   └── footer.php            # Footer metadata & script inclusions
│
├── assets/
│   ├── css/
│   │   ├── style.css         # Core Design System, Variables, Layout, Controls
│   │   ├── dashboard.css     # Metric cards, live score ticker, quick actions
│   │   ├── tournament.css    # Tournament grid, sport filter chips, modals
│   │   └── auth.css          # Glassmorphic login & register form styles
│   └── js/
│       ├── main.js           # Sidebar toggle, search filtering, toast alerts
│       ├── dashboard.js      # Real-time score ticker simulation
│       └── tournament.js     # Sport filter pill logic & modal controls
│
├── admin/
│   ├── dashboard.php         # Admin & Organizer Dashboard
│   ├── tournaments.php       # Tournament Registry & Creation Wizard
│   ├── teams.php             # Team Roster Registry
│   ├── players.php           # Athlete Directory
│   ├── matches.php           # Match Schedule & Fixture Engine
│   ├── venues.php            # Stadiums & Arenas Directory
│   ├── statistics.php        # Multi-Sport Leaderboards & MVP Metrics
│   └── settings.php          # Platform & Scoring Rules Configuration
│
├── public/
│   ├── tournament.php        # Public Tournament Detail View
│   ├── live-score.php        # Live Scoring Center
│   └── points-table.php      # Sport-aware Points Table & NRR Calculation
│
└── auth/
    └── login.php             # Sports Console Login (Admin / Organizer / Fan)
```

---

## 🛢️ Database Setup (MySQL)

1. Open **phpMyAdmin** (`http://localhost/phpmyadmin`) or your MySQL client.
2. Execute [database/schema.sql](file:///e:/BCA%20SEM%203/Web%20Development%20Project/project%201/database/schema.sql) to create the database structure:
   ```sql
   CREATE DATABASE IF NOT EXISTS `sportshub_db`;
   ```
3. Execute [database/seed.sql](file:///e:/BCA%20SEM%203/Web%20Development%20Project/project%201/database/seed.sql) to populate initial demo data for all 8 sports.

### Database Tables:
- `users`, `sports`, `venues`, `tournaments`, `teams`, `tournament_teams`, `players`, `officials`, `matches`, `scores`, `match_events`, `points_tables`, `player_statistics`, `team_statistics`, `notifications`.

---

## 🖥️ Local Execution Guide

### Option 1: XAMPP / WAMP / Laragon
1. Move/Copy this folder into your web root directory (e.g. `C:\xampp\htdocs\sportshub`).
2. Start **Apache** and **MySQL** in XAMPP / WAMP Control Panel.
3. Open browser at: **`http://localhost/sportshub/admin/dashboard.php`**

### Option 2: PHP Built-in Server
1. Open terminal in the project directory:
   ```bash
   php -S localhost:8000
   ```
2. Open browser at: **`http://localhost:8000/admin/dashboard.php`**

---

## 🔐 Credentials for Demo Login

| Role | Email | Password |
| :--- | :--- | :--- |
| **Administrator** | `admin@sportshub.com` | `Password123` |
| **Organizer** | `organizer@sportshub.com` | `Password123` |
| **Fan / Player** | `fan@sportshub.com` | `Password123` |

---

## 🛠️ Features Implemented (Phase 1)

- [x] Full-stack architecture (PHP + MySQL + Vanilla JS + CSS3).
- [x] Responsive layout with collapsible sidebar and top navigation.
- [x] Multi-sport badging and sport-aware scoring system.
- [x] Dynamic Dashboard with metric cards, live score ticker, and quick action cards.
- [x] Tournaments directory with live sport filtering and creation wizard modal.
- [x] Live Scoring center showing current match periods and live score updates.
- [x] Points table engine supporting Goal Difference & Net Run Rate (NRR).
- [x] Glassmorphic authentication portal with role switching.
