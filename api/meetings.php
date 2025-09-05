<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch($method) {
    case 'GET':
        if(isset($_GET['action'])) {
            switch($_GET['action']) {
                case 'users':
                    getUsers($db);
                    break;
                case 'meetings':
                    getMeetings($db);
                    break;
                case 'meeting':
                    getMeeting($db, $_GET['id']);
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
            }
        } else {
            getMeetings($db);
        }
        break;
    
    case 'POST':
        createMeeting($db, $input);
        break;
    
    case 'PUT':
        updateMeeting($db, $input);
        break;
    
    case 'DELETE':
        deleteMeeting($db, $_GET['id']);
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function getUsers($db) {
    try {
        $query = "SELECT id, name FROM users ORDER BY name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getMeetings($db) {
    try {
        $whereClause = "";
        $params = [];
        
        if(isset($_GET['month']) && isset($_GET['year'])) {
            $whereClause = "WHERE MONTH(data_reuniao) = ? AND YEAR(data_reuniao) = ?";
            $params = [$_GET['month'], $_GET['year']];
        }
        
        if(isset($_GET['status']) && $_GET['status'] != '') {
            $whereClause .= ($whereClause ? " AND " : "WHERE ") . "status = ?";
            $params[] = $_GET['status'];
        }
        
        if(isset($_GET['date']) && $_GET['date'] != '') {
            $whereClause .= ($whereClause ? " AND " : "WHERE ") . "data_reuniao = ?";
            $params[] = $_GET['date'];
        }
        
        if(isset($_GET['participant']) && $_GET['participant'] != '') {
            $whereClause .= ($whereClause ? " AND " : "WHERE ") . "r.id IN (SELECT reuniao_id FROM reuniao_participantes WHERE id_usuario = ?)";
            $params[] = $_GET['participant'];
        }
        
        // Auto-update meeting status based on current time
        $now = new DateTime();
        $currentDate = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');
        
        // Update to "em_andamento" (lowercase with underscore)
        $updateInProgress = "UPDATE reunioes SET status = 'em_andamento' 
                           WHERE data_reuniao = ? AND hora_inicio <= ? AND hora_fim > ? AND status = 'agendada'";
        $stmtUpdate1 = $db->prepare($updateInProgress);
        $stmtUpdate1->execute([$currentDate, $currentTime, $currentTime]);
        
        // Update to "concluida" (lowercase) - 1 minute after end time
        $updateFinished = "UPDATE reunioes SET status = 'concluida' 
                         WHERE ((data_reuniao = ? AND ADDTIME(hora_fim, '00:01:00') <= ?) OR data_reuniao < ?) 
                         AND status IN ('agendada', 'em_andamento')";
        $stmtUpdate2 = $db->prepare($updateFinished);
        $stmtUpdate2->execute([$currentDate, $currentTime, $currentDate]);
        
        // Fix any meetings with empty status or inconsistent status values
        $fixEmptyStatus = "UPDATE reunioes SET status = 
                          CASE 
                            WHEN data_reuniao < ? THEN 'concluida'
                            WHEN data_reuniao = ? AND ADDTIME(hora_fim, '00:01:00') <= ? THEN 'concluida'
                            WHEN data_reuniao = ? AND hora_inicio <= ? AND hora_fim > ? THEN 'em_andamento'
                            ELSE 'agendada'
                          END
                          WHERE status = '' OR status IS NULL OR status = 'finalizada'";
        $stmtFix = $db->prepare($fixEmptyStatus);
        $stmtFix->execute([$currentDate, $currentDate, $currentTime, $currentDate, $currentTime, $currentTime]);

        $query = "SELECT r.*, 
                         GROUP_CONCAT(u.name SEPARATOR ', ') as participantes,
                         DATE_FORMAT(r.data_reuniao, '%Y-%m-%d') as data_reuniao_formatted
                  FROM reunioes r 
                  LEFT JOIN reuniao_participantes rp ON r.id = rp.reuniao_id 
                  LEFT JOIN users u ON rp.id_usuario = u.id 
                  $whereClause 
                  GROUP BY r.id 
                  ORDER BY r.data_reuniao, r.hora_inicio";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($meetings);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getMeeting($db, $id) {
    try {
        $query = "SELECT r.*, 
                         GROUP_CONCAT(rp.id_usuario) as participant_ids 
                  FROM reunioes r 
                  LEFT JOIN reuniao_participantes rp ON r.id = rp.reuniao_id 
                  WHERE r.id = ? 
                  GROUP BY r.id";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $meeting = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($meeting) {
            $meeting['participants'] = $meeting['participant_ids'] ? explode(',', $meeting['participant_ids']) : [];
            echo json_encode($meeting);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Meeting not found']);
        }
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function createMeeting($db, $data) {
    try {
        // Check for conflicts
        if(checkTimeConflict($db, $data['data_reuniao'], $data['hora_inicio'], $data['hora_fim'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Conflito de horário com outra reunião']);
            return;
        }
        
        $db->beginTransaction();
        
        $query = "INSERT INTO reunioes (assunto, descricao, data_reuniao, hora_inicio, hora_fim, status) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $data['assunto'],
            $data['descricao'],
            $data['data_reuniao'],
            $data['hora_inicio'],
            $data['hora_fim'],
            $data['status']
        ]);
        
        $meetingId = $db->lastInsertId();
        
        // Insert participants
        if(isset($data['participants']) && is_array($data['participants'])) {
            $participantQuery = "INSERT INTO reuniao_participantes (reuniao_id, id_usuario, status_participacao, data_criacao) VALUES (?, ?, 'confirmado', NOW())";
            $participantStmt = $db->prepare($participantQuery);
            
            foreach($data['participants'] as $userId) {
                $participantStmt->execute([$meetingId, $userId]);
            }
        }
        
        $db->commit();
        echo json_encode(['success' => true, 'id' => $meetingId]);
    } catch(Exception $e) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function updateMeeting($db, $data) {
    try {
        // Check for conflicts (excluding current meeting)
        if(checkTimeConflict($db, $data['data_reuniao'], $data['hora_inicio'], $data['hora_fim'], $data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Conflito de horário com outra reunião']);
            return;
        }
        
        $db->beginTransaction();
        
        $query = "UPDATE reunioes SET assunto = ?, descricao = ?, data_reuniao = ?, 
                  hora_inicio = ?, hora_fim = ?, status = ? WHERE id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $data['assunto'],
            $data['descricao'],
            $data['data_reuniao'],
            $data['hora_inicio'],
            $data['hora_fim'],
            $data['status'],
            $data['id']
        ]);
        
        // Delete existing participants
        $deleteParticipants = "DELETE FROM reuniao_participantes WHERE reuniao_id = ?";
        $deleteStmt = $db->prepare($deleteParticipants);
        $deleteStmt->execute([$data['id']]);
        
        // Insert new participants
        if(isset($data['participants']) && is_array($data['participants'])) {
            $participantQuery = "INSERT INTO reuniao_participantes (reuniao_id, id_usuario, status_participacao, data_criacao) VALUES (?, ?, 'confirmado', NOW())";
            $participantStmt = $db->prepare($participantQuery);
            
            foreach($data['participants'] as $userId) {
                $participantStmt->execute([$data['id'], $userId]);
            }
        }
        
        $db->commit();
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function deleteMeeting($db, $id) {
    try {
        $db->beginTransaction();
        
        // Delete participants first
        $deleteParticipants = "DELETE FROM reuniao_participantes WHERE reuniao_id = ?";
        $deleteStmt = $db->prepare($deleteParticipants);
        $deleteStmt->execute([$id]);
        
        // Delete meeting
        $deleteMeeting = "DELETE FROM reunioes WHERE id = ?";
        $meetingStmt = $db->prepare($deleteMeeting);
        $meetingStmt->execute([$id]);
        
        $db->commit();
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        $db->rollback();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function checkTimeConflict($db, $date, $startTime, $endTime, $excludeId = null) {
    try {
        $query = "SELECT COUNT(*) as count FROM reunioes 
                  WHERE data_reuniao = ? 
                  AND ((hora_inicio < ? AND hora_fim > ?) OR 
                       (hora_inicio < ? AND hora_fim > ?) OR 
                       (hora_inicio >= ? AND hora_fim <= ?))";
        
        $params = [$date, $endTime, $startTime, $startTime, $startTime, $startTime, $endTime];
        
        if($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    } catch(Exception $e) {
        return false;
    }
}
?>
