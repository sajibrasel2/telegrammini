<?php
require_once 'config.php';
require_once 'database.php';
require_once 'security_helper.php';
require_once 'admin_auth.php';

$db = new Database();

if (!admin_is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $success = false;
    $message = "";

    switch ($action) {
        case 'add_task':
            $title = Security::sanitizeInput($_POST['title']);
            $reward = (float)$_POST['reward'];
            $link = Security::sanitizeInput($_POST['link']);
            $type = Security::sanitizeInput($_POST['type']);
            $icon = 'fas fa-tasks'; // Default icon
            
            if ($db->addTask($title, $reward, $link, $icon, $type)) {
                $success = true;
                $message = "Task added successfully!";
            } else {
                $message = "Failed to add task.";
            }
            break;

        case 'reset_social_tasks':
            require_once 'tasks_config.php';

            $db->deleteTasksByType('social');
            $inserted = 0;

            if (isset($tasks) && is_array($tasks)) {
                foreach ($tasks as $t) {
                    $title = $t['title'] ?? ($t['name'] ?? 'Task');
                    $reward = (float)($t['reward'] ?? 0);
                    $link = $t['link'] ?? ($t['url'] ?? '#');
                    $icon = $t['icon'] ?? 'fab fa-telegram-plane';
                    $type = 'social';

                    if ($db->addTask($title, $reward, $link, $icon, $type)) {
                        $inserted++;
                    }
                }
            }

            $success = true;
            $message = "Social tasks reset complete. Inserted: {$inserted}";
            break;

        case 'delete_task':
            $taskId = (int)$_POST['task_id'];
            if ($db->deleteTask($taskId)) {
                $success = true;
                $message = "Task deleted successfully!";
            } else {
                $message = "Failed to delete task.";
            }
            break;

        case 'update_task':
            $taskId = (int)$_POST['task_id'];
            $title = Security::sanitizeInput($_POST['title']);
            $reward = (float)$_POST['reward'];
            $link = Security::sanitizeInput($_POST['link']);
            $type = Security::sanitizeInput($_POST['type']);
            $icon = isset($_POST['icon']) ? Security::sanitizeInput($_POST['icon']) : 'fas fa-tasks';

            if ($taskId <= 0) {
                $message = 'Invalid task id.';
                break;
            }

            if ($db->updateTask($taskId, $title, $reward, $link, $icon, $type)) {
                $success = true;
                $message = 'Task updated successfully!';
            } else {
                $message = 'Nothing changed or update failed.';
            }
            break;

        case 'update_ads':
            $code1 = $_POST['ad_code_1']; // Don't sanitize HTML/JS codes
            $code2 = $_POST['ad_code_2'];
            
            $db->updateAdSlot('slot_1', $code1, 'active');
            $db->updateAdSlot('slot_2', $code2, 'active');
            
            $success = true;
            $message = "Ad settings updated successfully!";
            break;
            
        default:
            $message = "Invalid action.";
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

die(json_encode(['success' => false, 'message' => 'Invalid request.']));
?>
