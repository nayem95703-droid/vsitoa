<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Auth;
use Core\Database;
use Core\Validator;
use Core\Mailer;
use Core\Logger;

class SupportController
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Show support tickets list
     */
    public function showTickets(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $user = Auth::user();
        $status = $request->get('status', 'all');
        
        $whereClause = "WHERE st.user_id = ?";
        $params = [$user['id']];
        
        if ($status !== 'all') {
            $whereClause .= " AND st.status = ?";
            $params[] = $status;
        }
        
        $stmt = $this->db->prepare("
            SELECT st.*, 
                   (SELECT COUNT(*) FROM support_responses sr WHERE sr.ticket_id = st.id) as response_count,
                   u.username as assigned_username
            FROM support_tickets st
            LEFT JOIN users u ON st.assigned_to = u.id
            $whereClause
            ORDER BY st.created_at DESC
        ");
        
        $stmt->execute($params);
        $tickets = $stmt->fetchAll();
        
        include ROOT_PATH . '/views/user/support_tickets.php';
    }
    
    /**
     * Show create ticket form
     */
    public function showCreateTicket(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $user = Auth::user();
        
        // Check if user has enhanced support (verified users)
        $hasEnhancedSupport = VerifiedController::hasFeature($user['id'], 'priority_support');
        
        include ROOT_PATH . '/views/user/create_ticket.php';
    }
    
    /**
     * Create new support ticket
     */
    public function createTicket(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $user = Auth::user();
        $data = $request->all();
        
        $validator = Validator::make($data, [
            'subject' => 'required|min:5|max:255',
            'message' => 'required|min:20|max:5000',
            'category' => 'required|in:general,technical,billing,security,verification'
        ]);
        
        if (!$validator->validate()) {
            $_SESSION['flash_error'] = 'Please fill all required fields correctly.';
            $_SESSION['old_input'] = $data;
            $response->redirect('/support/create');
            return;
        }
        
        try {
            // Set priority based on user verification status and category
            $priority = 'medium';
            $hasEnhancedSupport = VerifiedController::hasFeature($user['id'], 'priority_support');
            
            if ($hasEnhancedSupport) {
                $priority = 'high';
            }
            
            if ($data['category'] === 'security' || $data['category'] === 'billing') {
                $priority = 'high';
            }
            
            if ($hasEnhancedSupport && ($data['category'] === 'security' || $data['category'] === 'billing')) {
                $priority = 'urgent';
            }
            
            // Create ticket
            $stmt = $this->db->prepare("
                INSERT INTO support_tickets (
                    user_id, subject, message, category, priority, status, created_at
                ) VALUES (?, ?, ?, ?, ?, 'open', NOW())
            ");
            
            $stmt->execute([
                $user['id'],
                $data['subject'],
                $data['message'],
                $data['category'],
                $priority
            ]);
            
            $ticketId = $this->db->lastInsertId();
            
            // Auto-assign ticket if verified user
            if ($hasEnhancedSupport) {
                $this->autoAssignTicket($ticketId, $priority);
            }
            
            // Send confirmation email
            $this->sendTicketConfirmationEmail($user, $ticketId, $data);
            
            // Log ticket creation
            Logger::info('Support ticket created', [
                'ticket_id' => $ticketId,
                'user_id' => $user['id'],
                'priority' => $priority,
                'category' => $data['category']
            ]);
            
            $_SESSION['flash_success'] = 'Support ticket created successfully. We will respond within ' . 
                ($priority === 'urgent' ? '2 hours' : ($priority === 'high' ? '6 hours' : '24 hours')) . '.';
            
            $response->redirect('/support/tickets');
            
        } catch (\Exception $e) {
            Logger::error('Failed to create support ticket', [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['flash_error'] = 'An error occurred while creating your ticket. Please try again.';
            $_SESSION['old_input'] = $data;
            $response->redirect('/support/create');
        }
    }
    
    /**
     * Show ticket details
     */
    public function showTicket(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $user = Auth::user();
        $ticketId = $request->get('id');
        
        // Get ticket details
        $stmt = $this->db->prepare("
            SELECT st.*, u.username as assigned_username
            FROM support_tickets st
            LEFT JOIN users u ON st.assigned_to = u.id
            WHERE st.id = ? AND st.user_id = ?
        ");
        
        $stmt->execute([$ticketId, $user['id']]);
        $ticket = $stmt->fetch();
        
        if (!$ticket) {
            $_SESSION['flash_error'] = 'Ticket not found.';
            $response->redirect('/support/tickets');
            return;
        }
        
        // Get ticket responses
        $stmt = $this->db->prepare("
            SELECT sr.*, u.username
            FROM support_responses sr
            LEFT JOIN users u ON sr.user_id = u.id
            WHERE sr.ticket_id = ?
            ORDER BY sr.created_at ASC
        ");
        
        $stmt->execute([$ticketId]);
        $responses = $stmt->fetchAll();
        
        // Mark ticket as read if user is viewing
        if ($ticket['status'] === 'in_progress' && $ticket['last_response_at']) {
            $stmt = $this->db->prepare("
                UPDATE support_tickets 
                SET status = 'open' 
                WHERE id = ? AND user_id = ? AND status = 'in_progress'
            ");
            $stmt->execute([$ticketId, $user['id']]);
        }
        
        include ROOT_PATH . '/views/user/ticket_details.php';
    }
    
    /**
     * Add response to ticket
     */
    public function addResponse(Request $request, Response $response): void
    {
        Auth::requireAuth();
        
        $user = Auth::user();
        $data = $request->all();
        
        $validator = Validator::make($data, [
            'ticket_id' => 'required|integer',
            'message' => 'required|min:10|max:2000'
        ]);
        
        if (!$validator->validate()) {
            $_SESSION['flash_error'] = 'Please enter a valid message.';
            $response->redirect('/support/ticket?id=' . $data['ticket_id']);
            return;
        }
        
        try {
            // Verify ticket ownership
            $stmt = $this->db->prepare("
                SELECT id, status FROM support_tickets 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$data['ticket_id'], $user['id']]);
            $ticket = $stmt->fetch();
            
            if (!$ticket) {
                $_SESSION['flash_error'] = 'Ticket not found.';
                $response->redirect('/support/tickets');
                return;
            }
            
            // Add response
            $stmt = $this->db->prepare("
                INSERT INTO support_responses (ticket_id, user_id, message, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([$data['ticket_id'], $user['id'], $data['message']]);
            
            // Update ticket
            $stmt = $this->db->prepare("
                UPDATE support_tickets 
                SET response_count = response_count + 1, 
                    status = 'in_progress',
                    last_response_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$data['ticket_id']]);
            
            // Notify support team
            $this->notifySupportTeam($data['ticket_id'], $user, $data['message']);
            
            $_SESSION['flash_success'] = 'Response added successfully.';
            
        } catch (\Exception $e) {
            Logger::error('Failed to add ticket response', [
                'ticket_id' => $data['ticket_id'],
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['flash_error'] = 'Failed to add response. Please try again.';
        }
        
        $response->redirect('/support/ticket?id=' . $data['ticket_id']);
    }
    
    /**
     * Auto-assign ticket to support staff
     */
    private function autoAssignTicket(int $ticketId, string $priority): void
    {
        // Get available support staff
        $stmt = $this->db->prepare("
            SELECT id FROM users 
            WHERE user_type = 'admin' 
            AND is_active = 1
            ORDER BY RAND()
            LIMIT 1
        ");
        
        $stmt->execute();
        $staff = $stmt->fetch();
        
        if ($staff) {
            $stmt = $this->db->prepare("
                UPDATE support_tickets 
                SET assigned_to = ?, status = 'in_progress'
                WHERE id = ?
            ");
            $stmt->execute([$staff['id'], $ticketId]);
            
            Logger::info('Ticket auto-assigned', [
                'ticket_id' => $ticketId,
                'assigned_to' => $staff['id'],
                'priority' => $priority
            ]);
        }
    }
    
    /**
     * Send ticket confirmation email
     */
    private function sendTicketConfirmationEmail($user, int $ticketId, array $data): void
    {
        $subject = "Support Ticket #{$ticketId} Created - VSITOA";
        
        $priorityText = [
            'urgent' => 'Urgent (2-hour response)',
            'high' => 'High (6-hour response)',
            'medium' => 'Normal (24-hour response)',
            'low' => 'Low (48-hour response)'
        ];
        
        $message = "
        <h2>Support Ticket Created</h2>
        <p>Dear {$user['username']},</p>
        <p>Your support ticket has been created successfully. Here are the details:</p>
        
        <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>
            <h3>Ticket #{$ticketId}</h3>
            <p><strong>Subject:</strong> {$data['subject']}</p>
            <p><strong>Category:</strong> " . ucfirst($data['category']) . "</p>
            <p><strong>Priority:</strong> {$priorityText[$data['priority'] ?? 'medium']}</p>
            <p><strong>Status:</strong> Open</p>
        </div>
        
        <h3>What's Next?</h3>
        <ol>
            <li>Our support team will review your ticket</li>
            <li>You'll receive a response within the estimated time</li>
            <li>You can track your ticket status and add responses</li>
        </ol>
        
        <p><a href='/support/ticket?id={$ticketId}' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Your Ticket</a></p>
        
        <p>If you have any questions, please don't hesitate to contact us.</p>
        
        <p>Best regards,<br>VSITOA Support Team</p>
        ";
        
        try {
            Mailer::send($user['email'], $subject, $message);
        } catch (\Exception $e) {
            Logger::error('Failed to send ticket confirmation email', [
                'ticket_id' => $ticketId,
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Notify support team of new response
     */
    private function notifySupportTeam(int $ticketId, $user, string $message): void
    {
        // Get support staff emails
        $stmt = $this->db->prepare("
            SELECT email FROM users 
            WHERE user_type = 'admin' AND is_active = 1
        ");
        $stmt->execute();
        $staff = $stmt->fetchAll();
        
        if (empty($staff)) return;
        
        $subject = "New Response on Ticket #{$ticketId} - VSITOA";
        
        $emailMessage = "
        <h2>New Ticket Response</h2>
        <p>A new response has been added to ticket #{$ticketId}:</p>
        
        <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>
            <p><strong>User:</strong> {$user['username']} ({$user['email']})</p>
            <p><strong>Response:</strong></p>
            <div style='background: white; padding: 10px; border-radius: 5px; border-left: 4px solid #007bff;'>
                " . nl2br(htmlspecialchars($message)) . "
            </div>
        </div>
        
        <p><a href='/admin/support/ticket?id={$ticketId}' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Ticket</a></p>
        
        <p>VSITOA Support System</p>
        ";
        
        foreach ($staff as $member) {
            try {
                Mailer::send($member['email'], $subject, $emailMessage);
            } catch (\Exception $e) {
                Logger::error('Failed to notify support staff', [
                    'ticket_id' => $ticketId,
                    'staff_email' => $member['email'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Admin: Show all support tickets
     */
    public function showAllTickets(Request $request, Response $response): void
    {
        Auth::requireAdmin();
        
        $status = $request->get('status', 'all');
        $priority = $request->get('priority', 'all');
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if ($status !== 'all') {
            $whereClause .= " AND st.status = ?";
            $params[] = $status;
        }
        
        if ($priority !== 'all') {
            $whereClause .= " AND st.priority = ?";
            $params[] = $priority;
        }
        
        $stmt = $this->db->prepare("
            SELECT st.*, u.username, u.is_verified,
                   (SELECT COUNT(*) FROM support_responses sr WHERE sr.ticket_id = st.id) as response_count,
                   a.username as assigned_username
            FROM support_tickets st
            JOIN users u ON st.user_id = u.id
            LEFT JOIN admins a ON st.assigned_to = a.admin_id
            $whereClause
            ORDER BY st.priority DESC, st.created_at ASC
        ");
        
        $stmt->execute($params);
        $tickets = $stmt->fetchAll();
        
        include ROOT_PATH . '/views/admin/support_tickets.php';
    }
    
    /**
     * Admin: Update ticket status
     */
    public function updateTicketStatus(Request $request, Response $response): void
    {
        Auth::requireAdmin();
        
        $data = $request->all();
        
        $validator = Validator::make($data, [
            'ticket_id' => 'required|integer',
            'status' => 'required|in:open,in_progress,resolved,closed'
        ]);
        
        if (!$validator->validate()) {
            $_SESSION['flash_error'] = 'Invalid request parameters.';
            $response->redirect('/admin?page=support-tickets');
            return;
        }
        
        try {
            $stmt = $this->db->prepare("
                UPDATE support_tickets 
                SET status = ?, assigned_to = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['status'],
                Auth::adminId(),
                $data['ticket_id']
            ]);
            
            $_SESSION['flash_success'] = 'Ticket status updated successfully.';
            
        } catch (\Exception $e) {
            Logger::error('Failed to update ticket status', [
                'ticket_id' => $data['ticket_id'],
                'error' => $e->getMessage()
            ]);
            
            $_SESSION['flash_error'] = 'Failed to update ticket status.';
        }
        
        $response->redirect('/admin?page=support-tickets');
    }
}
