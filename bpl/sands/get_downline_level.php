<?php

function getDownlineLevel($pdo, $sponsor_id, $id, $max_levels = 10)
{
    // Queue to store nodes for BFS traversal
    $queue = new SplQueue();

    // Start with the sponsor_id and level 0
    $queue->enqueue(['user_id' => $sponsor_id, 'level' => 0]);

    // Perform BFS
    while (!$queue->isEmpty()) {
        $current = $queue->dequeue();
        $current_user_id = $current['user_id'];
        $current_level = $current['level'];

        // If the current user_id matches the target id, return the level
        if ($current_user_id == $id) {
            return $current_level;
        }

        // If the current level exceeds max_levels, skip fetching downlines
        if ($current_level >= $max_levels) {
            continue;
        }

        // Fetch all direct downlines of the current user
        $stmt = $pdo->prepare("SELECT id FROM network_users WHERE sponsor_id = :sponsor_id");
        $stmt->execute(['sponsor_id' => $current_user_id]);
        $downlines = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Add all downlines to the queue with incremented level
        foreach ($downlines as $downline_id) {
            $queue->enqueue(['user_id' => $downline_id, 'level' => $current_level + 1]);
        }
    }

    // If the id is not found in the downline within max_levels, return 0
    return 0;
}

// Example usage:
$pdo = new PDO('mysql:host=localhost;dbname=b2p2_db', 'root', '');
$sponsor_id = 1; // The sponsor's user_id
$id = 5; // The user_id of the downline member you want to find
$max_levels = 10; // Maximum levels to search

$level = getDownlineLevel($pdo, $sponsor_id, $id, $max_levels);

if ($level > 0) {
    echo "The user with id $id is $level levels deep in the downline of sponsor with id $sponsor_id.";
} else {
    echo "The user with id $id is not in the downline of sponsor with id $sponsor_id within $max_levels levels.";
}