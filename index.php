<?php
// Security headers - set via PHP instead of .htaccess
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Health check endpoint
if ($_SERVER['REQUEST_URI'] === '/health') {
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'healthy', 
        'timestamp' => date('c'),
        'service' => 'University Telegram Bot'
    ]);
    exit;
}

header('Content-Type: application/json');

// Error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// For health checks and preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['status' => 'active', 'service' => 'University Telegram Bot']);
    exit;
}

if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

// Bot configuration
$botToken = getenv('BOT_TOKEN');
if (!$botToken) {
    error_log("BOT_TOKEN environment variable not set");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Bot configuration error']);
    exit;
}

// Initialize bot
$bot = new UniversityTelegramBot($botToken);
$bot->handleUpdate($data);

class UniversityTelegramBot {
    private $token;
    private $usersFile;
    
    public function __construct($token) {
        $this->token = $token;
        $this->usersFile = __DIR__ . '/users.json';
        $this->initializeUsersFile();
    }
    
    private function initializeUsersFile() {
        if (!file_exists($this->usersFile)) {
            file_put_contents($this->usersFile, json_encode([]));
            chmod($this->usersFile, 0666);
        }
    }
    
    public function handleUpdate($update) {
        if (!isset($update['message'])) {
            return;
        }
        
        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $userId = $message['from']['id'];
        $firstName = $message['from']['first_name'] ?? 'User';
        
        $this->saveUser($userId, $firstName, $chatId);
        
        $response = $this->processMessage($text, $chatId, $firstName);
        $this->sendMessage($chatId, $response);
    }
    
    private function processMessage($text, $chatId, $firstName) {
        $text = strtolower(trim($text));
        
        // Welcome message for /start command
        if ($text === '/start') {
            return $this->getWelcomeMessage($firstName);
        }
        
        // Payment related queries
        if (strpos($text, 'payment') !== false || 
            strpos($text, 'fee') !== false || 
            strpos($text, 'tuition') !== false ||
            strpos($text, 'pay') !== false) {
            return $this->getPaymentInfo();
        }
        
        // Enrollment related queries
        if (strpos($text, 'enroll') !== false || 
            strpos($text, 'admission') !== false || 
            strpos($text, 'register') !== false ||
            strpos($text, 'application') !== false) {
            return $this->getEnrollmentInfo();
        }
        
        // Deadline queries
        if (strpos($text, 'deadline') !== false || 
            strpos($text, 'due date') !== false || 
            strpos($text, 'when') !== false) {
            return $this->getDeadlineInfo();
        }
        
        // Contact information
        if (strpos($text, 'contact') !== false || 
            strpos($text, 'email') !== false || 
            strpos($text, 'phone') !== false ||
            strpos($text, 'help') !== false) {
            return $this->getContactInfo();
        }
        
        // Scholarship queries
        if (strpos($text, 'scholarship') !== false || 
            strpos($text, 'financial aid') !== false || 
            strpos($text, 'grant') !== false) {
            return $this->getScholarshipInfo();
        }
        
        // Default response for unknown queries
        return $this->getDefaultResponse();
    }
    
    private function getWelcomeMessage($firstName) {
        return "👋 Welcome, $firstName! \n\nI'm your University Assistant Bot. I can help you with:\n\n" .
               "💳 *Payment Questions*\n" .
               "📝 *Enrollment Procedures*\n" .
               "📅 *Deadlines and Dates*\n" .
               "🎓 *Scholarship Information*\n" .
               "📞 *Contact Information*\n\n" .
               "Please type your question or choose from these common topics:\n" .
               "• 'payment options' - Learn about payment methods\n" .
               "• 'enrollment process' - Steps to enroll\n" .
               "• 'deadlines' - Important dates\n" .
               "• 'scholarship' - Financial aid information\n" .
               "• 'contact' - Get help from human staff";
    }
    
    private function getPaymentInfo() {
        return "💳 *Payment Information*\n\n" .
               "We offer several payment options:\n\n" .
               "✅ *Online Payment*\n" .
               "• Credit/Debit Cards (Visa, MasterCard)\n" .
               "• Bank Transfer\n" .
               "• Mobile Payment Apps\n\n" .
               "✅ *Installment Plans*\n" .
               "• 3-month installment plan available\n" .
               "• 0% interest for early payment\n\n" .
               "✅ *Payment Deadlines*\n" .
               "• Semester 1: September 15, 2024\n" .
               "• Semester 2: January 20, 2025\n\n" .
               "💡 *Need help?* Contact our Bursar's Office at bursar@university.edu or call (555) 123-4567";
    }
    
    private function getEnrollmentInfo() {
        return "📝 *Enrollment Process*\n\n" .
               "Follow these steps to enroll:\n\n" .
               "1. *Submit Application* - Complete online form\n" .
               "2. *Upload Documents* - Transcripts, ID, photo\n" .
               "3. *Pay Application Fee* - \$50 (non-refundable)\n" .
               "4. *Receive Acceptance* - Within 2-3 weeks\n" .
               "5. *Confirm Enrollment* - Pay deposit fee\n\n" .
               "📋 *Required Documents:*\n" .
               "• High school transcript\n" .
               "• National ID/Passport\n" .
               "• Passport-sized photo\n" .
               "• Recommendation letters (if applicable)\n\n" .
               "🌐 *Online Portal:* https://portal.university.edu/enroll\n" .
               "📞 *Admissions Office:* (555) 123-4568";
    }
    
    private function getDeadlineInfo() {
        return "📅 *Important Deadlines - Academic Year 2024/2025*\n\n" .
               "🎓 *Undergraduate Programs*\n" .
               "• Early Application: March 31, 2024\n" .
               "• Regular Decision: June 30, 2024\n" .
               "• Late Application: July 31, 2024 (with fee)\n\n" .
               "💳 *Payment Deadlines*\n" .
               "• Fall Semester: September 15, 2024\n" .
               "• Spring Semester: January 20, 2025\n" .
               "• Summer Session: May 15, 2025\n\n" .
               "📚 *Registration Periods*\n" .
               "• Fall: August 1-30, 2024\n" .
               "• Spring: December 1-31, 2024\n\n" .
               "⚠️ *Late payments incur 5% penalty fee*";
    }
    
    private function getContactInfo() {
        return "📞 *Contact Information*\n\n" .
               "We're here to help you!\n\n" .
               "💳 *Bursar's Office (Payments)*\n" .
               "• Email: bursar@university.edu\n" .
               "• Phone: (555) 123-4567\n" .
               "• Hours: Mon-Fri, 9AM-5PM\n\n" .
               "📝 *Admissions Office (Enrollment)*\n" .
               "• Email: admissions@university.edu\n" .
               "• Phone: (555) 123-4568\n" .
               "• Hours: Mon-Fri, 8AM-6PM\n\n" .
               "🎓 *Student Services*\n" .
               "• Email: studentservices@university.edu\n" .
               "• Phone: (555) 123-4569\n" .
               "• Hours: Mon-Sat, 8AM-8PM\n\n" .
               "🌐 *Website:* https://www.university.edu\n" .
               "📍 *Address:* 123 University Ave, Education City";
    }
    
    private function getScholarshipInfo() {
        return "🎓 *Scholarship & Financial Aid*\n\n" .
               "We offer various scholarship opportunities:\n\n" .
               "🏆 *Merit Scholarships*\n" .
               "• Presidential Scholarship: 100% tuition\n" .
               "• Dean's Scholarship: 50% tuition\n" .
               "• Achievement Award: 25% tuition\n\n" .
               "💼 *Need-Based Aid*\n" .
               "• Family income below \$50,000\n" .
               "• Complete FAFSA application\n" .
               "• Submit tax documents\n\n" .
               "⚡ *Application Deadlines*\n" .
               "• Priority Deadline: February 1, 2024\n" .
               "• Final Deadline: April 15, 2024\n\n" .
               "📋 *Requirements:*\n" .
               "• Minimum 3.5 GPA\n" .
               "• Personal statement\n" .
               "• Recommendation letters\n" .
               "• Interview (for some scholarships)\n\n" .
               "💻 Apply at: https://financialaid.university.edu";
    }
    
    private function getDefaultResponse() {
        return "🤖 I'm not sure I understand your question. \n\n" .
               "I can help you with:\n\n" .
               "• Tuition fees and payment methods\n" .
               "• Enrollment procedures and requirements\n" .
               "• Application deadlines\n" .
               "• Scholarship information\n" .
               "• Contact details for departments\n\n" .
               "Please try rephrasing your question or use these keywords:\n" .
               "'payment', 'enrollment', 'deadline', 'scholarship', 'contact'";
    }
    
    private function saveUser($userId, $firstName, $chatId) {
        $users = $this->loadUsers();
        
        if (!isset($users[$userId])) {
            $users[$userId] = [
                'first_name' => $firstName,
                'chat_id' => $chatId,
                'first_seen' => date('Y-m-d H:i:s'),
                'last_active' => date('Y-m-d H:i:s')
            ];
        } else {
            $users[$userId]['last_active'] = date('Y-m-d H:i:s');
        }
        
        file_put_contents($this->usersFile, json_encode($users, JSON_PRETTY_PRINT));
    }
    
    private function loadUsers() {
        if (!file_exists($this->usersFile)) {
            return [];
        }
        
        $data = file_get_contents($this->usersFile);
        return json_decode($data, true) ?: [];
    }
    
    private function sendMessage($chatId, $text) {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";
        
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown'
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        @file_get_contents($url, false, $context);
    }
}

// Respond to Telegram webhook
echo json_encode(['status' => 'success']);
?>