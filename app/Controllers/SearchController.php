<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Auth;
use Core\Database;
use Core\Logger;

class SearchController
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Enhanced search with verified user optimization
     */
    public function search(Request $request, Response $response): void
    {
        $query = trim($request->get('q', ''));
        $type = $request->get('type', 'users'); // users, tasks, all
        $page = max(1, (int) $request->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        if (empty($query)) {
            $this->showSearchPage($request, $response, [], 0, $page, $limit);
            return;
        }
        
        try {
            $results = [];
            $total = 0;
            
            switch ($type) {
                case 'users':
                    $results = $this->searchUsers($query, $limit, $offset);
                    $total = $this->countUserSearchResults($query);
                    break;
                case 'tasks':
                    $results = $this->searchTasks($query, $limit, $offset);
                    $total = $this->countTaskSearchResults($query);
                    break;
                case 'all':
                default:
                    $results = $this->searchAll($query, $limit, $offset);
                    $total = $this->countAllSearchResults($query);
                    break;
            }
            
            $this->showSearchPage($request, $response, $results, $total, $page, $limit, $query, $type);
            
        } catch (\Exception $e) {
            Logger::error('Search error', [
                'query' => $query,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['flash_error'] = 'Search failed. Please try again.';
            $this->showSearchPage($request, $response, [], 0, $page, $limit);
        }
    }
    
    /**
     * Search users with verified user priority
     */
    private function searchUsers(string $query, int $limit, int $offset): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                u.id, u.username, u.first_name, u.last_name, u.company_name,
                u.user_type, u.rating, u.is_verified, u.profile_image,
                us.search_score, us.profile_views,
                MATCH(us.search_keywords) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score,
                GROUP_CONCAT(DISTINCT usl.url) as profile_links
            FROM users u
            JOIN user_search_data us ON u.id = us.user_id
            LEFT JOIN user_profile_links usl ON u.id = usl.user_id AND usl.is_public = 1
            WHERE u.is_active = 1
            AND MATCH(us.search_keywords) AGAINST(? IN NATURAL LANGUAGE MODE)
            GROUP BY u.id
            ORDER BY 
                u.is_verified DESC,
                us.search_score DESC,
                relevance_score DESC,
                u.username ASC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$query, $query, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Search tasks
     */
    private function searchTasks(string $query, int $limit, int $offset): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                t.id, t.title, t.description, t.payment_per_execution,
                t.status, t.created_at,
                u.username as advertiser_username,
                u.is_verified as advertiser_verified,
                MATCH(t.title, t.description) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score
            FROM tasks t
            JOIN users u ON t.advertiser_id = u.id
            WHERE t.status = 'active'
            AND MATCH(t.title, t.description) AGAINST(? IN NATURAL LANGUAGE MODE)
            ORDER BY 
                u.is_verified DESC,
                relevance_score DESC,
                t.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$query, $query, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Search all content
     */
    private function searchAll(string $query, int $limit, int $offset): array
    {
        $results = [];
        
        // Search users (60% of results)
        $userLimit = (int) ($limit * 0.6);
        $userResults = $this->searchUsers($query, $userLimit, 0);
        
        foreach ($userResults as $user) {
            $results[] = [
                'type' => 'user',
                'data' => $user,
                'relevance' => $user['relevance_score'] + ($user['is_verified'] ? 50 : 0)
            ];
        }
        
        // Search tasks (40% of results)
        $taskLimit = $limit - count($userResults);
        if ($taskLimit > 0) {
            $taskResults = $this->searchTasks($query, $taskLimit, 0);
            
            foreach ($taskResults as $task) {
                $results[] = [
                    'type' => 'task',
                    'data' => $task,
                    'relevance' => $task['relevance_score'] + ($task['advertiser_verified'] ? 30 : 0)
                ];
            }
        }
        
        // Sort by relevance
        usort($results, function($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });
        
        return array_slice($results, $offset, $limit);
    }
    
    /**
     * Count user search results
     */
    private function countUserSearchResults(string $query): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT u.id) as total
            FROM users u
            JOIN user_search_data us ON u.id = us.user_id
            WHERE u.is_active = 1
            AND MATCH(us.search_keywords) AGAINST(? IN NATURAL LANGUAGE MODE)
        ");
        
        $stmt->execute([$query]);
        return (int) $stmt->fetch()['total'];
    }
    
    /**
     * Count task search results
     */
    private function countTaskSearchResults(string $query): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total
            FROM tasks t
            JOIN users u ON t.advertiser_id = u.id
            WHERE t.status = 'active'
            AND MATCH(t.title, t.description) AGAINST(? IN NATURAL LANGUAGE MODE)
        ");
        
        $stmt->execute([$query]);
        return (int) $stmt->fetch()['total'];
    }
    
    /**
     * Count all search results
     */
    private function countAllSearchResults(string $query): int
    {
        return $this->countUserSearchResults($query) + $this->countTaskSearchResults($query);
    }
    
    /**
     * Show search results page
     */
    private function showSearchPage(Request $request, Response $response, array $results, int $total, int $page, int $limit, string $query = '', string $type = 'users'): void
    {
        $totalPages = ceil($total / $limit);
        $hasNextPage = $page < $totalPages;
        $hasPrevPage = $page > 1;
        
        include ROOT_PATH . '/views/search/results.php';
    }
    
    /**
     * AJAX search for autocomplete
     */
    public function autocomplete(Request $request, Response $response): void
    {
        $query = trim($request->get('q', ''));
        $limit = min(10, max(1, (int) $request->get('limit', 5)));
        
        if (strlen($query) < 2) {
            $response->json(['success' => true, 'results' => []]);
            return;
        }
        
        try {
            $results = [];
            
            // Search users with verified priority
            $stmt = $this->db->prepare("
                SELECT 
                    u.id, u.username, u.first_name, u.last_name, u.profile_image, u.is_verified,
                    MATCH(us.search_keywords) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score
                FROM users u
                JOIN user_search_data us ON u.id = us.user_id
                WHERE u.is_active = 1
                AND MATCH(us.search_keywords) AGAINST(? IN NATURAL LANGUAGE MODE)
                ORDER BY 
                    u.is_verified DESC,
                    relevance_score DESC,
                    u.username ASC
                LIMIT ?
            ");
            
            $stmt->execute([$query, $query, $limit]);
            $users = $stmt->fetchAll();
            
            foreach ($users as $user) {
                $displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                if (empty($displayName)) {
                    $displayName = $user['username'];
                }
                
                $results[] = [
                    'type' => 'user',
                    'id' => $user['id'],
                    'title' => $user['username'],
                    'subtitle' => $displayName,
                    'image' => $user['profile_image'] ?? '/assets/images/default-avatar.png',
                    'verified' => (bool) $user['is_verified'],
                    'url' => '/profile?username=' . urlencode($user['username'])
                ];
            }
            
            $response->json(['success' => true, 'results' => $results]);
            
        } catch (\Exception $e) {
            Logger::error('Autocomplete search error', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            $response->json(['success' => false, 'results' => []]);
        }
    }
    
    /**
     * Update search data for all users (cron job)
     */
    public function updateSearchIndex(): void
    {
        try {
            // Get all active users
            $stmt = $this->db->prepare("
                SELECT id, username, email, first_name, last_name, 
                       company_name, user_type, is_verified, rating
                FROM users 
                WHERE is_active = 1
            ");
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            foreach ($users as $user) {
                $this->updateUserSearchData($user);
            }
            
            Logger::info('Search index updated successfully', [
                'users_updated' => count($users)
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to update search index', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update search data for a specific user
     */
    private function updateUserSearchData(array $user): void
    {
        // Get additional user data
        $stmt = $this->db->prepare("
            SELECT GROUP_CONCAT(DISTINCT url) as profile_links
            FROM user_profile_links 
            WHERE user_id = ? AND is_public = 1
        ");
        $stmt->execute([$user['id']]);
        $links = $stmt->fetch();
        
        // Build search keywords
        $keywords = [
            'username' => $user['username'],
            'email' => $user['email'],
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? '',
            'company_name' => $user['company_name'] ?? '',
            'user_type' => $user['user_type'],
            'profile_links' => $links['profile_links'] ?? ''
        ];
        
        // Calculate search score
        $searchScore = 50.0; // Base score
        
        if ($user['is_verified']) {
            $searchScore += 30.0; // Verified bonus
        }
        
        if ($user['rating'] >= 4.5) {
            $searchScore += 15.0; // High rating bonus
        } elseif ($user['rating'] >= 3.5) {
            $searchScore += 10.0;
        }
        
        if (!empty($user['company_name'])) {
            $searchScore += 5.0; // Company bonus
        }
        
        // Update search data
        $stmt = $this->db->prepare("
            INSERT INTO user_search_data (user_id, search_keywords, search_score, last_search_update)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                search_keywords = VALUES(search_keywords),
                search_score = VALUES(search_score),
                last_search_update = NOW()
        ");
        
        $stmt->execute([$user['id'], json_encode($keywords), $searchScore]);
    }
    
    /**
     * Get trending searches (based on recent activity)
     */
    public function getTrendingSearches(Request $request, Response $response): void
    {
        $limit = min(20, max(5, (int) $request->get('limit', 10)));
        
        try {
            // Get trending based on profile views and recent activity
            $stmt = $this->db->prepare("
                SELECT 
                    u.username,
                    u.first_name,
                    u.last_name,
                    u.is_verified,
                    us.profile_views,
                    us.search_score
                FROM users u
                JOIN user_search_data us ON u.id = us.user_id
                WHERE u.is_active = 1
                AND us.profile_views > 0
                ORDER BY 
                    us.profile_views DESC,
                    us.search_score DESC,
                    u.username ASC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            $trending = $stmt->fetchAll();
            
            $response->json(['success' => true, 'trending' => $trending]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to get trending searches', [
                'error' => $e->getMessage()
            ]);
            
            $response->json(['success' => false, 'trending' => []]);
        }
    }
    
    /**
     * Get search suggestions
     */
    public function getSearchSuggestions(Request $request, Response $response): void
    {
        $query = trim($request->get('q', ''));
        $limit = min(10, max(1, (int) $request->get('limit', 5)));
        
        if (strlen($query) < 2) {
            $response->json(['success' => true, 'suggestions' => []]);
            return;
        }
        
        try {
            $suggestions = [];
            
            // Get username suggestions
            $stmt = $this->db->prepare("
                SELECT DISTINCT username
                FROM users u
                JOIN user_search_data us ON u.id = us.user_id
                WHERE u.is_active = 1
                AND u.username LIKE ?
                ORDER BY 
                    u.is_verified DESC,
                    us.search_score DESC,
                    u.username ASC
                LIMIT ?
            ");
            
            $stmt->execute([$query . '%', $limit]);
            $usernames = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            foreach ($usernames as $username) {
                $suggestions[] = [
                    'text' => $username,
                    'type' => 'user',
                    'description' => 'User profile'
                ];
            }
            
            // Get company name suggestions
            $stmt = $this->db->prepare("
                SELECT DISTINCT company_name
                FROM users u
                JOIN user_search_data us ON u.id = us.user_id
                WHERE u.is_active = 1
                AND u.company_name IS NOT NULL
                AND u.company_name LIKE ?
                ORDER BY 
                    u.is_verified DESC,
                    us.search_score DESC,
                    u.company_name ASC
                LIMIT ?
            ");
            
            $stmt->execute(['%' . $query . '%', $limit]);
            $companies = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            foreach ($companies as $company) {
                $suggestions[] = [
                    'text' => $company,
                    'type' => 'company',
                    'description' => 'Company name'
                ];
            }
            
            $response->json(['success' => true, 'suggestions' => array_slice($suggestions, 0, $limit)]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to get search suggestions', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            $response->json(['success' => false, 'suggestions' => []]);
        }
    }
}
