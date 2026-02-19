<?php
require_once 'config.php';
require_once 'database.php';

$db = new Database();

// Initial tasks from tasks_config.php or original logic
$initialTasks = [
    [
        'title' => 'Join Official Channel',
        'reward' => 10,
        'link' => 'https://t.me/PCN_OfficialChannel',
        'icon' => 'fab fa-telegram',
        'type' => 'social'
    ],
    [
        'title' => 'Join Community Group',
        'reward' => 5,
        'link' => 'https://t.me/PCN_Community',
        'icon' => 'fab fa-telegram',
        'type' => 'social'
    ],
    [
        'title' => 'Follow on Twitter',
        'reward' => 5,
        'link' => 'https://twitter.com/PCN_Coin',
        'icon' => 'fab fa-twitter',
        'type' => 'social'
    ],
    [
        'title' => 'Visit PCN Website',
        'reward' => 2,
        'link' => 'https://techandclick.site',
        'icon' => 'fas fa-globe',
        'type' => 'daily'
    ]
];

$successCount = 0;
foreach ($initialTasks as $task) {
    if ($db->addTask($task['title'], $task['reward'], $task['link'], $task['icon'], $task['type'])) {
        $successCount++;
    }
}

echo "Successfully seeded $successCount tasks into the database.";
?>
