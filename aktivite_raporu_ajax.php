<?php
session_start();
require_once 'functions.php';

// Yetki kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı!']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'add_note':
        $danisan_id = $_POST['danisan_id'] ?? 0;
        $not = trim($_POST['not'] ?? '');
        
        if (!$danisan_id || !$not) {
            echo json_encode(['success' => false, 'message' => 'Gerekli alanlar eksik!']);
            exit;
        }
        
        $result = addAktiviteRaporuNotu($danisan_id, $not, $user_id);
        echo json_encode($result);
        break;
        
    case 'get_notes':
        $danisan_id = $_GET['danisan_id'] ?? 0;
        
        if (!$danisan_id) {
            echo json_encode(['success' => false, 'message' => 'Danışan ID eksik!']);
            exit;
        }
        
        $danisan_info = getDanisanBasicInfo($danisan_id);
        $notlar = getAktiviteRaporuNotlari($danisan_id, 20);
        
        if ($danisan_info) {
            echo json_encode([
                'success' => true,
                'danisan' => $danisan_info,
                'notlar' => $notlar
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Danışan bulunamadı!']);
        }
        break;
        
    case 'delete_note':
        $not_id = $_POST['not_id'] ?? 0;
        
        if (!$not_id) {
            echo json_encode(['success' => false, 'message' => 'Not ID eksik!']);
            exit;
        }
        
        $result = deleteAktiviteRaporuNotu($not_id, $user_id);
        echo json_encode($result);
        break;
        
    case 'get_note_count':
        $danisan_id = $_GET['danisan_id'] ?? 0;
        
        if (!$danisan_id) {
            echo json_encode(['success' => false, 'count' => 0]);
            exit;
        }
        
        $count = getAktiviteRaporuNotSayisi($danisan_id);
        echo json_encode(['success' => true, 'count' => $count]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem!']);
        break;
}
?>