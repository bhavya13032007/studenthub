<?php
/**
 * Dynamic Menu Generator based on User Role
 * Practical 14: Role-Based Dynamic Navigation
 * Student ID: 25DCE075
 */
function render_dynamic_menu($active_page = 'dashboard') {
    $role = $_SESSION['role'] ?? 'guest';
    
    // Define navigation structure based on role
    $menu_items = [];

    if ($role === 'admin') {
        $menu_items = [
            ['title' => '📊 Dashboard & Analytics', 'link' => '../practical_14_admin_dashboard/admin_dashboard.php', 'key' => 'dashboard'],
            ['title' => '👥 User Management', 'link' => '#users_section', 'key' => 'users'],
            ['title' => '👨‍🎓 Student Records (CRUD)', 'link' => '../practical_11_student_crud/students.php', 'key' => 'students'],
            ['title' => '📅 Event Management', 'link' => '../practical_12_event_management/events.php', 'key' => 'events'],
            ['title' => '⚡ Fetch API CRUD', 'link' => '../practical_13_json_ajax_crud/index.html', 'key' => 'api'],
            ['title' => '📜 Audit Logs & Trail', 'link' => '#audit_section', 'key' => 'audit']
        ];
    } elseif ($role === 'student') {
        $menu_items = [
            ['title' => '🎓 Student Home', 'link' => '../practical_10_auth_sessions/student_dashboard.php', 'key' => 'student_home'],
            ['title' => '📅 Upcoming Events', 'link' => '../practical_12_event_management/events.php', 'key' => 'events'],
            ['title' => '📝 My Profile', 'link' => '#', 'key' => 'profile']
        ];
    } else {
        $menu_items = [
            ['title' => '🔑 Sign In', 'link' => '../practical_10_auth_sessions/login.php', 'key' => 'login'],
            ['title' => '📝 Register', 'link' => '../practical_09_registration/register.php', 'key' => 'register']
        ];
    }

    echo '<ul class="dynamic-nav">';
    foreach ($menu_items as $item) {
        $active_class = ($item['key'] === $active_page) ? 'active' : '';
        echo "<li><a href='{$item['link']}' class='{$active_class}'>{$item['title']}</a></li>";
    }
    echo '</ul>';
}
?>
