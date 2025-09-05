<?php
// Database initialization script
require_once '../config/database.php';

echo "🔍 Testing database connection...\n";

try {
    // Test database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
    
    echo "✅ Database connection successful!\n";
    
    // Test basic query
    $testQuery = $db->query("SELECT 1 as test")->fetch();
    if ($testQuery['test'] == 1) {
        echo "✅ Database queries working!\n";
    }
    
    // Check if tables exist and have data
    try {
        $checkUsers = $db->query("SELECT COUNT(*) as count FROM users")->fetch();
        $checkReuniones = $db->query("SELECT COUNT(*) as count FROM reunioes")->fetch();
        $checkParticipantes = $db->query("SELECT COUNT(*) as count FROM reuniao_participantes")->fetch();
        
        echo "\n📊 Tables status:\n";
        echo "   - users: {$checkUsers['count']} records\n";
        echo "   - reunioes: {$checkReuniones['count']} records\n";
        echo "   - reuniao_participantes: {$checkParticipantes['count']} records\n";
        
    } catch (Exception $e) {
        echo "⚠️  Error checking tables: " . $e->getMessage() . "\n";
        echo "Tables may not exist or have different structure.\n";
        
        // Try to show available tables
        try {
            $tablesQuery = $db->query("SHOW TABLES");
            $tables = $tablesQuery->fetchAll();
            echo "Available tables:\n";
            foreach($tables as $table) {
                echo "   - " . array_values($table)[0] . "\n";
            }
        } catch (Exception $e2) {
            echo "Could not list tables: " . $e2->getMessage() . "\n";
        }
    }
    
    // Show sample meetings for current month
    try {
        $currentMonth = date('n');
        $currentYear = date('Y');
        
        $sampleQuery = "SELECT r.assunto, r.data_reuniao, r.hora_inicio, r.status 
                        FROM reunioes r 
                        WHERE MONTH(r.data_reuniao) = ? AND YEAR(r.data_reuniao) = ?
                        ORDER BY r.data_reuniao, r.hora_inicio 
                        LIMIT 5";
        
        $stmt = $db->prepare($sampleQuery);
        $stmt->execute([$currentMonth, $currentYear]);
        $sampleMeetings = $stmt->fetchAll();
        
        if (count($sampleMeetings) > 0) {
            echo "\n📅 Sample meetings for current month ({$currentMonth}/{$currentYear}):\n";
            foreach($sampleMeetings as $meeting) {
                echo "   - {$meeting['assunto']} ({$meeting['data_reuniao']} {$meeting['hora_inicio']}) - {$meeting['status']}\n";
            }
        } else {
            echo "\n📅 No meetings found for current month ({$currentMonth}/{$currentYear})\n";
            
            // Show any existing meetings
            $allMeetingsQuery = "SELECT r.assunto, r.data_reuniao, r.hora_inicio, r.status 
                                FROM reunioes r 
                                ORDER BY r.data_reuniao DESC 
                                LIMIT 5";
            
            $stmt = $db->prepare($allMeetingsQuery);
            $stmt->execute();
            $allMeetings = $stmt->fetchAll();
            
            if (count($allMeetings) > 0) {
                echo "📅 Recent meetings found:\n";
                foreach($allMeetings as $meeting) {
                    echo "   - {$meeting['assunto']} ({$meeting['data_reuniao']} {$meeting['hora_inicio']}) - {$meeting['status']}\n";
                }
            } else {
                echo "📅 No meetings found in database\n";
            }
        }
    } catch (Exception $e) {
        echo "⚠️  Error loading meetings: " . $e->getMessage() . "\n";
    }
    
    echo "\n🚀 System ready!\n";
    echo "📍 Access the system at: http://localhost/agendamento/\n";
    echo "🔧 Open browser console to see API responses\n";
    
} catch (Exception $e) {
    echo "❌ Connection Error: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Check if XAMPP MySQL service is running\n";
    echo "2. Verify database 'cadastro_empresas' exists\n";
    echo "3. Try accessing phpMyAdmin at http://localhost/phpmyadmin/\n";
    echo "4. Check MySQL port configuration (usually 3306)\n";
    echo "5. Verify MySQL credentials (username: root, password: empty)\n";
}
?>
