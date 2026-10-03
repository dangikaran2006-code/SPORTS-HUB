<?php
/**
 * SportsHub - Database Connection & Abstraction Layer
 * Features PDO Prepared Statements, SQL Helper Methods, and Demo Fallback
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo = null;
    public $isDemoMode = false;
    public $connectionStatusMessage = '';

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 2,
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->isDemoMode = false;
            $this->connectionStatusMessage = "[DEV SUCCESS] Connected to MySQL Database '" . DB_NAME . "' successfully.";
        } catch (PDOException $e) {
            $this->pdo = null;
            $this->isDemoMode = true;
            $this->connectionStatusMessage = "[DEV NOTICE] MySQL not active or pending setup. Running in Demo Mode.";
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    // -------------------------------------------------------------------------
    // REUSABLE QUERY HELPERS
    // -------------------------------------------------------------------------

    /**
     * Execute any SQL query with prepared statement parameters
     */
    public function executeQuery($sql, $params = []) {
        if (!$this->pdo || $this->isDemoMode) return false;
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database Query Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch all records for a query
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Fetch a single record
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt ? $stmt->fetch() : null;
    }

    /**
     * Generic Insert Helper
     */
    public function insert($table, $data) {
        if (!$this->pdo || $this->isDemoMode) return false;
        $fields = array_keys($data);
        $placeholders = array_map(function($field) { return ":$field"; }, $fields);

        $sql = "INSERT INTO `$table` (`" . implode("`, `", $fields) . "`) VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $this->pdo->prepare($sql);
        
        $params = [];
        foreach ($data as $key => $val) {
            $params[":$key"] = $val;
        }

        if ($stmt->execute($params)) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Generic Update Helper
     */
    public function update($table, $data, $whereClause, $whereParams = []) {
        if (!$this->pdo || $this->isDemoMode) return false;
        $setParts = [];
        $params = [];

        foreach ($data as $key => $val) {
            $setParts[] = "`$key` = :set_$key";
            $params[":set_$key"] = $val;
        }

        $sql = "UPDATE `$table` SET " . implode(", ", $setParts) . " WHERE $whereClause";
        
        foreach ($whereParams as $k => $v) {
            $params[$k] = $v;
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Generic Delete Helper
     */
    public function delete($table, $whereClause, $whereParams = []) {
        if (!$this->pdo || $this->isDemoMode) return false;
        $sql = "DELETE FROM `$table` WHERE $whereClause";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($whereParams);
    }

    // -------------------------------------------------------------------------
    // APPLICATION DOMAIN METHODS WITH DEMO FALLBACK
    // -------------------------------------------------------------------------

    public function getDashboardStats() {
        if ($this->pdo && !$this->isDemoMode) {
            try {
                $tournaments = $this->fetchOne("SELECT COUNT(*) as cnt FROM tournaments")['cnt'] ?? 4;
                $activeTournaments = $this->fetchOne("SELECT COUNT(*) as cnt FROM tournaments WHERE status='active'")['cnt'] ?? 2;
                $teams = $this->fetchOne("SELECT COUNT(*) as cnt FROM teams")['cnt'] ?? 8;
                $players = $this->fetchOne("SELECT COUNT(*) as cnt FROM players")['cnt'] ?? 8;
                $liveMatches = $this->fetchOne("SELECT COUNT(*) as cnt FROM matches WHERE status='live'")['cnt'] ?? 2;
                $todayMatches = $this->fetchOne("SELECT COUNT(*) as cnt FROM matches WHERE scheduled_date = CURRENT_DATE()")['cnt'] ?? 2;

                return [
                    'total_tournaments'  => $tournaments,
                    'active_tournaments' => $activeTournaments,
                    'total_teams'        => $teams,
                    'total_players'      => $players,
                    'live_matches'       => $liveMatches,
                    'today_matches'      => $todayMatches,
                ];
            } catch (Exception $e) {}
        }

        return [
            'total_tournaments'  => 4,
            'active_tournaments' => 2,
            'total_teams'        => 8,
            'total_players'      => 8,
            'live_matches'       => 2,
            'today_matches'      => 2,
        ];
    }

    public function getTournaments($limit = 50, $statusFilter = 'all') {
        if ($this->pdo && !$this->isDemoMode) {
            try {
                $sql = "
                    SELECT t.*, s.name as sport_name, s.slug as sport_code, u.name as organizer_name, v.name as venue_name,
                           (SELECT COUNT(*) FROM tournament_teams tt WHERE tt.tournament_id = t.id) as teams_count
                    FROM tournaments t
                    JOIN sports s ON t.sport_id = s.id
                    JOIN users u ON t.created_by = u.id
                    LEFT JOIN venues v ON t.venue_id = v.id
                ";
                $params = [];
                if ($statusFilter !== 'all') {
                    $sql .= " WHERE t.status = :status";
                    $params[':status'] = $statusFilter;
                }
                $sql .= " ORDER BY t.created_at DESC LIMIT " . (int)$limit;

                $result = $this->fetchAll($sql, $params);
                if (!empty($result)) return $result;
            } catch (Exception $e) {}
        }

        $demoTournaments = [
            [
                'id' => 1,
                'name' => 'Champions Premier League 2026',
                'sport_name' => 'Cricket',
                'sport_code' => 'cricket',
                'format' => 'group_knockout',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-25',
                'status' => 'active',
                'teams_count' => 8,
                'organizer_name' => 'Sarah Jenkins',
                'venue_name' => 'Apex Sports Complex, Mumbai',
                'description' => 'Premier T20 Cricket Championship featuring top clubs.'
            ],
            [
                'id' => 2,
                'name' => 'Super Football Cup 2026',
                'sport_name' => 'Football',
                'sport_code' => 'football',
                'format' => 'league',
                'start_date' => '2026-10-05',
                'end_date' => '2026-11-10',
                'status' => 'active',
                'teams_count' => 10,
                'organizer_name' => 'Sarah Jenkins',
                'venue_name' => 'Grand National Arena, Bengaluru',
                'description' => 'National level 11-a-side Football League Tournament.'
            ],
            [
                'id' => 3,
                'name' => 'Inter College Kabaddi Championship',
                'sport_name' => 'Kabaddi',
                'sport_code' => 'kabaddi',
                'format' => 'round_robin',
                'start_date' => '2026-10-10',
                'end_date' => '2026-10-30',
                'status' => 'upcoming',
                'teams_count' => 6,
                'organizer_name' => 'Rajesh Kumar',
                'venue_name' => 'Metro Indoor Stadium, New Delhi',
                'description' => 'Inter-collegiate high-intensity indoor kabaddi championship.'
            ],
            [
                'id' => 4,
                'name' => 'SSIT Sports Fest 2026',
                'sport_name' => 'Basketball',
                'sport_code' => 'basketball',
                'format' => 'knockout',
                'start_date' => '2026-10-15',
                'end_date' => '2026-10-28',
                'status' => 'upcoming',
                'teams_count' => 12,
                'organizer_name' => 'Alex Mercer',
                'venue_name' => 'SSIT Sports Ground, Tumakuru',
                'description' => 'Annual Inter-departmental Multi-Sport Tournament.'
            ]
        ];

        if ($statusFilter !== 'all') {
            return array_values(array_filter($demoTournaments, function($t) use ($statusFilter) {
                return strtolower($t['status']) === strtolower($statusFilter);
            }));
        }
        return $demoTournaments;
    }

    public function createTournament($name, $sportId, $format, $startDate, $endDate, $venueId, $description) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        
        if ($this->pdo && !$this->isDemoMode) {
            return $this->insert('tournaments', [
                'name'        => $name,
                'slug'        => $slug . '-' . time(),
                'sport_id'    => $sportId,
                'description' => $description,
                'start_date'  => $startDate,
                'end_date'    => $endDate,
                'venue_id'    => $venueId,
                'format'      => $format,
                'status'      => 'upcoming',
                'created_by'  => 1
            ]);
        }
        return true;
    }

    public function getTeams() {
        if ($this->pdo && !$this->isDemoMode) {
            try {
                $sql = "
                    SELECT tm.*, s.name as sport_name, p.name as captain_name
                    FROM teams tm
                    JOIN sports s ON tm.sport_id = s.id
                    LEFT JOIN players p ON tm.captain_id = p.id
                    ORDER BY tm.id DESC
                ";
                $result = $this->fetchAll($sql);
                if (!empty($result)) return $result;
            } catch (Exception $e) {}
        }

        return [
            ['id' => 1, 'name' => 'Royal Strikers', 'short_name' => 'RST', 'sport_name' => 'Cricket', 'city' => 'Mumbai', 'captain_name' => 'Rohit Sharma', 'manager_name' => 'Vikram Rathore', 'status' => 'Approved'],
            ['id' => 2, 'name' => 'Thunder Warriors', 'short_name' => 'TWR', 'sport_name' => 'Cricket', 'city' => 'Bengaluru', 'captain_name' => 'Virat Kohli', 'manager_name' => 'Anil Kumble', 'status' => 'Approved'],
            ['id' => 5, 'name' => 'Apex Football Club', 'short_name' => 'AFC', 'sport_name' => 'Football', 'city' => 'Mumbai', 'captain_name' => 'Sunil Chhetri', 'manager_name' => 'Sergio Lobera', 'status' => 'Approved'],
            ['id' => 6, 'name' => 'City Titans FC', 'short_name' => 'CTF', 'sport_name' => 'Football', 'city' => 'Bengaluru', 'captain_name' => 'Roy Krishna', 'manager_name' => 'Carles Cuadrat', 'status' => 'Approved'],
            ['id' => 7, 'name' => 'SSIT Bulls Kabaddi', 'short_name' => 'SBK', 'sport_name' => 'Kabaddi', 'city' => 'Tumakuru', 'captain_name' => 'Pawan Sehrawat', 'manager_name' => 'Dr. Ramesh', 'status' => 'Approved']
        ];
    }

    public function getPlayers() {
        if ($this->pdo && !$this->isDemoMode) {
            try {
                $sql = "
                    SELECT p.*, tm.name as team_name, s.name as sport_name
                    FROM players p
                    JOIN sports s ON p.sport_id = s.id
                    LEFT JOIN teams tm ON p.team_id = tm.id
                    ORDER BY p.id DESC
                ";
                $result = $this->fetchAll($sql);
                if (!empty($result)) return $result;
            } catch (Exception $e) {}
        }

        return [
            ['id' => 1, 'name' => 'Rohit Sharma', 'jersey_number' => 45, 'sport_name' => 'Cricket', 'team_name' => 'Royal Strikers', 'primary_role' => 'Batsman', 'date_of_birth' => '1987-04-30', 'status' => 'Active'],
            ['id' => 2, 'name' => 'Jasprit Bumrah', 'jersey_number' => 93, 'sport_name' => 'Cricket', 'team_name' => 'Royal Strikers', 'primary_role' => 'Bowler', 'date_of_birth' => '1993-12-06', 'status' => 'Active'],
            ['id' => 3, 'name' => 'Virat Kohli', 'jersey_number' => 18, 'sport_name' => 'Cricket', 'team_name' => 'Thunder Warriors', 'primary_role' => 'Batsman', 'date_of_birth' => '1988-11-05', 'status' => 'Active'],
            ['id' => 5, 'name' => 'Sunil Chhetri', 'jersey_number' => 11, 'sport_name' => 'Football', 'team_name' => 'Apex FC', 'primary_role' => 'Forward', 'date_of_birth' => '1984-08-03', 'status' => 'Active'],
            ['id' => 7, 'name' => 'Pawan Sehrawat', 'jersey_number' => 7, 'sport_name' => 'Kabaddi', 'team_name' => 'SSIT Bulls', 'primary_role' => 'Raider', 'date_of_birth' => '1996-07-09', 'status' => 'Active']
        ];
    }

    public function getMatches($filter = 'all') {
        if ($this->pdo && !$this->isDemoMode) {
            try {
                $sql = "
                    SELECT m.*, m.scheduled_date as match_date, m.scheduled_time as start_time,
                           t.name as tournament_name, s.name as sport_name,
                           ta.name as team_a_name, ta.short_name as team_a_short,
                           tb.name as team_b_name, tb.short_name as team_b_short,
                           v.name as venue_name, o.name as official_name
                    FROM matches m
                    JOIN tournaments t ON m.tournament_id = t.id
                    JOIN sports s ON m.sport_id = s.id
                    JOIN teams ta ON m.team_a_id = ta.id
                    JOIN teams tb ON m.team_b_id = tb.id
                    LEFT JOIN venues v ON m.venue_id = v.id
                    LEFT JOIN officials o ON m.official_id = o.id
                ";
                $params = [];
                if ($filter === 'live') {
                    $sql .= " WHERE m.status = 'live'";
                } elseif ($filter === 'scheduled' || $filter === 'upcoming') {
                    $sql .= " WHERE m.status = 'scheduled'";
                } elseif ($filter === 'completed') {
                    $sql .= " WHERE m.status = 'completed'";
                }
                $sql .= " ORDER BY m.scheduled_date DESC";

                $result = $this->fetchAll($sql, $params);
                if (!empty($result)) return $result;
            } catch (Exception $e) {}
        }

        $allMatches = [
            [
                'id' => 1,
                'tournament_name' => 'Champions Premier League 2026',
                'sport_name' => 'Cricket',
                'team_a_name' => 'Royal Strikers',
                'team_a_short' => 'RST',
                'team_b_name' => 'Thunder Warriors',
                'team_b_short' => 'TWR',
                'score_a' => '174/4',
                'score_b' => '185/6',
                'current_period' => '18.4 Overs',
                'match_date' => date('Y-m-d'),
                'start_time' => '19:30',
                'venue_name' => 'Apex Sports Complex',
                'official_name' => 'Nitin Menon',
                'status' => 'live',
                'result_summary' => 'Royal Strikers need 12 runs in 8 balls'
            ],
            [
                'id' => 3,
                'tournament_name' => 'Super Football Cup 2026',
                'sport_name' => 'Football',
                'team_a_name' => 'Apex Football Club',
                'team_a_short' => 'AFC',
                'team_b_name' => 'City Titans FC',
                'team_b_short' => 'CTF',
                'score_a' => '2',
                'score_b' => '1',
                'current_period' => '78th Min',
                'match_date' => date('Y-m-d'),
                'start_time' => '20:00',
                'venue_name' => 'Grand National Arena',
                'official_name' => 'Pranjal Banerjee',
                'status' => 'live',
                'result_summary' => 'Apex FC leads 2 - 1'
            ],
            [
                'id' => 4,
                'tournament_name' => 'SSIT Sports Fest 2026',
                'sport_name' => 'Basketball',
                'team_a_name' => 'SSIT Bulls',
                'team_a_short' => 'SBK',
                'team_b_name' => 'Delhi Raiders',
                'team_b_short' => 'DLR',
                'score_a' => '-',
                'score_b' => '-',
                'current_period' => 'Not Started',
                'match_date' => date('Y-m-d', strtotime('+2 days')),
                'start_time' => '18:00',
                'venue_name' => 'SSIT Sports Ground',
                'official_name' => 'Tejas Nagvenkar',
                'status' => 'scheduled',
                'result_summary' => 'Scheduled for 6:00 PM'
            ],
            [
                'id' => 2,
                'tournament_name' => 'Champions Premier League 2026',
                'sport_name' => 'Cricket',
                'team_a_name' => 'Rising Panthers',
                'team_a_short' => 'RPA',
                'team_b_name' => 'Coastal Kings',
                'team_b_short' => 'CKG',
                'score_a' => '162/6',
                'score_b' => '158/8',
                'current_period' => 'Finished',
                'match_date' => date('Y-m-d', strtotime('-1 day')),
                'start_time' => '16:00',
                'venue_name' => 'Apex Sports Complex',
                'official_name' => 'Kumar Dharmasena',
                'status' => 'completed',
                'result_summary' => 'Rising Panthers won by 4 wickets'
            ]
        ];

        if ($filter !== 'all') {
            return array_values(array_filter($allMatches, function($m) use ($filter) {
                return strtolower($m['status']) === strtolower($filter);
            }));
        }
        return $allMatches;
    }
}

// Global Database Access Instance Helper
function getDB() {
    return Database::getInstance();
}

// Global Query Helper Shortcuts
function getDBConnection() {
    return getDB()->getConnection();
}

function executeQuery($sql, $params = []) {
    return getDB()->executeQuery($sql, $params);
}

function fetchAll($sql, $params = []) {
    return getDB()->fetchAll($sql, $params);
}

function fetchOne($sql, $params = []) {
    return getDB()->fetchOne($sql, $params);
}

function insert($table, $data) {
    return getDB()->insert($table, $data);
}

function update($table, $data, $whereClause, $whereParams = []) {
    return getDB()->update($table, $data, $whereClause, $whereParams);
}

function delete($table, $whereClause, $whereParams = []) {
    return getDB()->delete($table, $whereClause, $whereParams);
}
