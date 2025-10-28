<?php
// Admin Panel - User & Editor Management + Analytics
require_once 'config.php';

// Check if user is admin
if (!is_admin()) {
    header('Location: index.php');
    exit;
}

// Try to connect to database with error handling
try {
    $conn = db_connect();
} catch (Exception $e) {
    error_log("Admin panel - Database connection failed: " . $e->getMessage());
    die("<h1>Database Connection Failed</h1><p>Unable to connect to database. Please check your configuration.</p>");
}

// Check if role column exists
try {
    $result_check = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
if (isset($result_check) && $result_check->num_rows === 0) {
    echo "<div style='padding: 2rem; max-width: 800px; margin: 0 auto;'>";
    echo "<h1>Database Migration Required</h1>";
    echo "<p>The 'role' column is missing from the users table.</p>";
    echo "<p><a href='migrate_add_role_column.php' style='display: inline-block; padding: 1rem 2rem; background: #a8aa19; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>Run Migration Now</a></p>";
    echo "<p>Or run: <code>php init.php</code> to recreate all tables.</p>";
    echo "</div>";
    exit;
}
} catch (Exception $e) {
    error_log("Admin panel - Role check failed: " . $e->getMessage());
}

// Get statistics
$stats = [
    'total_users' => 0,
    'total_editors' => 0,
    'total_stories' => 0,
    'total_guides' => 0,
    'total_events' => 0,
    'active_members' => 0,
    'new_contacts' => 0
];

// Get statistics with error handling
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) $stats['total_users'] = $result->fetch_assoc()['count'];
} catch (Exception $e) {
    $stats['total_users'] = 0;
}

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN ('editor', 'admin')");
    if ($result) $stats['total_editors'] = $result->fetch_assoc()['count'];
} catch (Exception $e) {
    $stats['total_editors'] = 0;
}

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM stories");
    if ($result) $stats['total_stories'] = $result->fetch_assoc()['count'];
} catch (Exception $e) {
    $stats['total_stories'] = 0;
}

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM guides");
    if ($result) $stats['total_guides'] = $result->fetch_assoc()['count'];
} catch (Exception $e) {
    $stats['total_guides'] = 0;
}

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM events");
    if ($result) $stats['total_events'] = $result->fetch_assoc()['count'];
} catch (Exception $e) {
    $stats['total_events'] = 0;
}

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM members WHERE is_active = 1");
    if ($result) $stats['active_members'] = $result->fetch_assoc()['count'];
} catch (Exception $e) {
    $stats['active_members'] = 0;
}

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'new'");
    if ($result) $stats['new_contacts'] = $result->fetch_assoc()['count'];
} catch (Exception $e) {
    $stats['new_contacts'] = 0;
}

// Get recent activity with error handling
$recent_stories = [];
try {
    $result = $conn->query("SELECT title, created_at, status FROM stories ORDER BY created_at DESC LIMIT 5");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_stories[] = $row;
        }
    }
} catch (Exception $e) {
    $recent_stories = [];
}

$recent_guides = [];
try {
    $result = $conn->query("SELECT title, created_at, status FROM guides ORDER BY created_at DESC LIMIT 5");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_guides[] = $row;
        }
    }
} catch (Exception $e) {
    $recent_guides = [];
}

// Get all users with their roles
$users = [];
try {
    $result = $conn->query("SELECT id, email, role, created_at FROM users ORDER BY created_at DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
} catch (Exception $e) {
    $users = [];
}

try {
    $conn->close();
} catch (Exception $e) {
    // Connection already closed or error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Yucca Club</title>
    <link rel="icon" type="image/png" href="ui/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #F5F1E9;
            color: #1a1a1a;
            line-height: 1.6;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background: #1a1a1a;
            color: white;
            padding: 2rem 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 2rem;
        }

        .sidebar-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #a8aa19;
        }

        .sidebar-header p {
            font-size: 0.875rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }

        .nav-section {
            padding: 0 1rem;
            margin-bottom: 2rem;
        }

        .nav-section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.5;
            padding: 0 0.75rem;
            margin-bottom: 0.75rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            margin: 0.25rem 0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9375rem;
            color: rgba(255,255,255,0.8);
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .nav-item.active {
            background: #a8aa19;
            color: white;
        }

        .nav-item i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            padding: 2rem;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .dashboard-header h2 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1a1a1a;
        }

        .dashboard-header p {
            color: #63666A;
            margin-top: 0.25rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.06);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .stat-card-title {
            font-size: 0.875rem;
            color: #63666A;
            font-weight: 500;
        }

        .stat-card-icon {
            font-size: 1.5rem;
            opacity: 0.7;
        }

        .stat-card-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a1a;
        }

        .stat-card-value.new {
            color: #A81919;
        }

        /* Content Area */
        .content-area {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.06);
            min-height: 400px;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: #F5F1E9;
            border-bottom: 2px solid #ede9df;
        }

        .data-table th {
            padding: 1rem;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            color: #63666A;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #ede9df;
        }

        .data-table tbody tr:hover {
            background: #F5F1E9;
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-user { background: #ede9df; color: #63666A; }
        .badge-editor { background: #E3F2FD; color: #1976D2; }
        .badge-admin { background: #a8aa19; color: white; }
        .badge-published { background: #E8F5E9; color: #388E3C; }
        .badge-draft { background: #FFF3E0; color: #F57C00; }
        .badge-new { background: #FFEBEE; color: #C62828; }

        /* Buttons */
        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            border: none;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: #a8aa19;
            color: white;
        }

        .btn-primary:hover {
            background: #94941c;
        }

        .btn-secondary {
            background: #ede9df;
            color: #63666A;
        }

        .btn-secondary:hover {
            background: #d6d1c4;
        }

        .btn-danger {
            background: #A81919;
            color: white;
        }

        .btn-danger:hover {
            background: #8d1313;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        /* Actions Bar */
        .actions-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #63666A;
        }

        .modal-close:hover {
            color: #1a1a1a;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #1a1a1a;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ede9df;
            border-radius: 8px;
            font-size: 0.9375rem;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #a8aa19;
        }

        /* Rich Builder */
        #rich-builder {
            background: #F5F1E9;
            border: 2px solid #ede9df;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }

        #blocks-container {
            display: grid;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .block-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #ede9df;
            position: relative;
        }

        .block-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .block-actions {
            display: flex;
            gap: 0.5rem;
        }

        .block-actions button {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #63666A;
        }

        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-paw"></i> Yucca Club</h1>
                <p>Admin Dashboard</p>
            </div>

            <nav class="nav-section">
                <div class="nav-section-title">Main</div>
                <div class="nav-item active" onclick="showSection('dashboard')">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </div>
            </nav>

            <nav class="nav-section">
                <div class="nav-section-title">Content</div>
                <div class="nav-item" onclick="showSection('stories')">
                    <i class="fas fa-book"></i>
                    <span>Stories</span>
                </div>
                <div class="nav-item" onclick="showSection('guides')">
                    <i class="fas fa-map"></i>
                    <span>Guides</span>
                </div>
                <div class="nav-item" onclick="showSection('messages')">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                    <?php if ($stats['new_contacts'] > 0): ?>
                        <span class="badge badge-new" style="margin-left: auto;"><?= $stats['new_contacts'] ?></span>
                    <?php endif; ?>
                </div>
            </nav>

            <nav class="nav-section">
                <div class="nav-section-title">Users</div>
                <div class="nav-item" onclick="showSection('users')">
                    <i class="fas fa-users"></i>
                    <span>All Users</span>
                </div>
                <div class="nav-item" onclick="showSection('create-user')">
                    <i class="fas fa-user-plus"></i>
                    <span>Create User</span>
                </div>
            </nav>

            <nav class="nav-section">
                <div class="nav-section-title">Tools</div>
                <div class="nav-item" onclick="showSection('testing')">
                    <i class="fas fa-flask"></i>
                    <span>Testing</span>
                </div>
                <div class="nav-item" onclick="showSection('maintenance')">
                    <i class="fas fa-tools"></i>
                    <span>Maintenance</span>
                </div>
            </nav>

            <div style="padding: 0 1.5rem; margin-top: auto; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1);">
                <a href="index.php" style="display: flex; align-items: center; gap: 0.75rem; color: rgba(255,255,255,0.8); text-decoration: none; padding: 0.75rem; border-radius: 8px; transition: all 0.2s;">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Site</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="content-section active">
                <div class="dashboard-header">
                    <div>
                        <h2>Dashboard</h2>
                        <p>Overview of your content and activity</p>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-title">Total Stories</div>
                            <div class="stat-card-icon"><i class="fas fa-book"></i></div>
                        </div>
                        <div class="stat-card-value"><?= $stats['total_stories'] ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-title">Total Guides</div>
                            <div class="stat-card-icon"><i class="fas fa-map"></i></div>
                        </div>
                        <div class="stat-card-value"><?= $stats['total_guides'] ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-title">Events</div>
                            <div class="stat-card-icon"><i class="fas fa-calendar"></i></div>
                        </div>
                        <div class="stat-card-value"><?= $stats['total_events'] ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-title">Total Users</div>
                            <div class="stat-card-icon"><i class="fas fa-users"></i></div>
                        </div>
                        <div class="stat-card-value"><?= $stats['total_users'] ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-title">Staff</div>
                            <div class="stat-card-icon"><i class="fas fa-user-tie"></i></div>
                        </div>
                        <div class="stat-card-value"><?= $stats['total_editors'] ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-title">New Messages</div>
                            <div class="stat-card-icon"><i class="fas fa-envelope"></i></div>
                        </div>
                        <div class="stat-card-value new"><?= $stats['new_contacts'] ?></div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 2rem;">
                    <div class="content-area">
                        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">Recent Stories</h3>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php if (count($recent_stories) > 0): ?>
                                <?php foreach ($recent_stories as $story): ?>
                                <div style="padding: 1rem; border-bottom: 1px solid #ede9df;">
                                    <div style="font-weight: 600; margin-bottom: 0.5rem;"><?= htmlspecialchars($story['title']) ?></div>
                                    <div style="font-size: 0.875rem; color: #63666A;">
                                        <span class="badge badge-<?= $story['status'] ?>"><?= ucfirst($story['status']) ?></span>
                                        <span style="margin-left: 1rem;"><?= date('M j, Y', strtotime($story['created_at'])) ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-book"></i>
                                    <p>No stories yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="content-area">
                        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">Recent Guides</h3>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php if (count($recent_guides) > 0): ?>
                                <?php foreach ($recent_guides as $guide): ?>
                                <div style="padding: 1rem; border-bottom: 1px solid #ede9df;">
                                    <div style="font-weight: 600; margin-bottom: 0.5rem;"><?= htmlspecialchars($guide['title']) ?></div>
                                    <div style="font-size: 0.875rem; color: #63666A;">
                                        <span class="badge badge-<?= $guide['status'] ?>"><?= ucfirst($guide['status']) ?></span>
                                        <span style="margin-left: 1rem;"><?= date('M j, Y', strtotime($guide['created_at'])) ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-map"></i>
                                    <p>No guides yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stories Section -->
            <div id="stories-section" class="content-section">
                <div class="dashboard-header">
                    <div>
                        <h2><i class="fas fa-book"></i> Stories</h2>
                        <p>Manage all story content</p>
                    </div>
                </div>

                <div class="content-area">
                    <div class="actions-bar">
                        <button class="btn btn-primary" onclick="openEditor('stories', null)">
                            <i class="fas fa-plus"></i>
                            Create New Story
                        </button>
                    </div>
                    <div id="stories-list"></div>
                </div>
            </div>

            <!-- Guides Section -->
            <div id="guides-section" class="content-section">
                <div class="dashboard-header">
                    <div>
                        <h2><i class="fas fa-map"></i> Guides</h2>
                        <p>Manage all guide content</p>
                    </div>
                </div>

                <div class="content-area">
                    <div class="actions-bar">
                        <button class="btn btn-primary" onclick="openEditor('guides', null)">
                            <i class="fas fa-plus"></i>
                            Create New Guide
                        </button>
                    </div>
                    <div id="guides-list"></div>
                </div>
            </div>

            <!-- Messages Section -->
            <div id="messages-section" class="content-section">
                <div class="dashboard-header">
                    <div>
                        <h2><i class="fas fa-envelope"></i> Messages</h2>
                        <p>View and manage contact submissions</p>
                    </div>
                </div>

                <div class="content-area">
                    <div id="contacts-list"></div>
                </div>
            </div>

            <!-- Users Section -->
            <div id="users-section" class="content-section">
                <div class="dashboard-header">
                    <div>
                        <h2><i class="fas fa-users"></i> All Users</h2>
                        <p>Manage user accounts and permissions</p>
                    </div>
                </div>

                <div class="content-area">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $user['role'] ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <?php if ($user['email'] !== $_SESSION['user_email']): ?>
                                        <select onchange="updateUserRole(<?= $user['id'] ?>, this.value)" style="padding: 0.5rem; border: 1px solid #ede9df; border-radius: 8px; font-size: 0.875rem;">
                                            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                            <option value="editor" <?= $user['role'] === 'editor' ? 'selected' : '' ?>>Editor</option>
                                        </select>
                                    <?php else: ?>
                                        <span style="opacity: 0.5; font-size: 0.875rem;">Can't change own role</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Create User Section -->
            <div id="create-user-section" class="content-section">
                <div class="dashboard-header">
                    <div>
                        <h2><i class="fas fa-user-plus"></i> Create User</h2>
                        <p>Add new editor or admin accounts</p>
                    </div>
                </div>

                <div class="content-area">
                    <div class="actions-bar">
                        <button class="btn btn-primary" onclick="openCreateEditorModal()">
                            <i class="fas fa-user-tie"></i>
                            Create Editor
                        </button>
                        <button class="btn btn-secondary" onclick="openCreateAdminModal()">
                            <i class="fas fa-user-shield"></i>
                            Create Admin
                        </button>
                    </div>
                </div>
            </div>

            <!-- Testing Section -->
            <div id="testing-section" class="content-section">
                <div class="dashboard-header">
                    <div>
                        <h2><i class="fas fa-flask"></i> Testing</h2>
                        <p>Test API connections and functionality</p>
                    </div>
                </div>

                <div class="content-area">
                    <div class="actions-bar">
                        <button class="btn btn-primary" onclick="testGitHub()">
                            <i class="fab fa-github"></i>
                            Test GitHub
                        </button>
                        <button class="btn btn-secondary" onclick="testDatabase()">
                            <i class="fas fa-database"></i>
                            Test Database
                        </button>
                        <button class="btn btn-secondary" onclick="testAPIs()">
                            <i class="fas fa-plug"></i>
                            Test APIs
                        </button>
                    </div>
                    <div id="test-results" style="margin-top: 2rem;"></div>
                </div>
            </div>

            <!-- Maintenance Section -->
            <div id="maintenance-section" class="content-section">
                <div class="dashboard-header">
                    <div>
                        <h2><i class="fas fa-tools"></i> Maintenance Mode</h2>
                        <p>Control site accessibility</p>
                    </div>
                </div>

                <div class="content-area">
                    <div id="maintenance-status"></div>
                    <div class="actions-bar" style="margin-top: 2rem;">
                        <button class="btn btn-danger" onclick="toggleMaintenance('enable')">
                            <i class="fas fa-lock"></i>
                            Enable Maintenance
                        </button>
                        <button class="btn btn-primary" onclick="toggleMaintenance('disable')">
                            <i class="fas fa-unlock"></i>
                            Disable Maintenance
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Editor Modal -->
    <div id="editor-modal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeEditor()">&times;</button>
            <h2 id="editor-title">Create Content</h2>
            <form id="editor-form">
                <input type="hidden" id="content-type" name="content_type">
                <input type="hidden" id="content-id" name="content_id">

                <div class="form-group">
                    <label>Title</label>
                    <input type="text" id="content-title" name="title" required>
                </div>

                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" id="content-slug" name="slug" required>
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <input type="text" id="content-category" name="category">
                </div>

                <div class="form-group">
                    <label>Featured Image URL</label>
                    <input type="url" id="content-image" name="featured_image" placeholder="https://...">
                </div>

                <div class="form-group">
                    <label>Excerpt</label>
                    <textarea id="content-excerpt" name="excerpt" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label>Content</label>
                    <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 0.5rem;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleBuilder()">Toggle Rich Builder</button>
                        <span id="builder-status" style="font-size: 0.9rem; opacity: 0.8;">Rich builder: off</span>
                    </div>
                    <textarea id="content-body" name="content" rows="10"></textarea>

                    <div id="rich-builder" style="display:none;">
                        <div style="display:flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="addBlock('heading')">+ Heading</button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="addBlock('subheading')">+ Subheading</button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="addBlock('paragraph')">+ Paragraph</button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="addBlock('image')">+ Image</button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="addBlock('blockquote')">+ Blockquote</button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="addBlock('list')">+ List</button>
                        </div>
                        <div id="blocks-container"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select id="content-status" name="status">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditor()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Editor Modal -->
    <div id="create-editor-modal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeCreateEditorModal()">&times;</button>
            <h2>Create Editor Account</h2>
            <form id="create-editor-form">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">Create Account</button>
                    <button type="button" class="btn btn-secondary" onclick="closeCreateEditorModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Admin Modal -->
    <div id="create-admin-modal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeCreateAdminModal()">&times;</button>
            <h2>Create Admin Account</h2>
            <form id="create-admin-form">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">Create Account</button>
                    <button type="button" class="btn btn-secondary" onclick="closeCreateAdminModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Navigation
        function showSection(sectionName) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active from all nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });

            // Show selected section
            const section = document.getElementById(sectionName + '-section');
            if (section) {
                section.classList.add('active');
            }

            // Add active to clicked nav item
            event.currentTarget.classList.add('active');

            // Load section-specific content
            if (sectionName === 'stories') loadStories();
            if (sectionName === 'guides') loadGuides();
            if (sectionName === 'messages') loadContacts();
        }

        // Load functions (keeping all existing functionality)
        
        async function loadStories() {
            try {
                const response = await fetch('api/content_api.php?type=stories&action=list');
                const data = await response.json();
                
                const container = document.getElementById('stories-list');
                let html = '<table class="data-table"><thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>';
                
                if (data.data && data.data.length > 0) {
                    data.data.forEach(item => {
                        html += `<tr>
                            <td>${item.title}</td>
                            <td>${item.category || '-'}</td>
                            <td><span class="badge badge-${item.status}">${item.status}</span></td>
                            <td>${new Date(item.updated_at).toLocaleDateString()}</td>
                            <td>
                                <button class="btn btn-secondary btn-sm" onclick="openEditor('stories', ${item.id})">Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteContent('stories', ${item.id})">Delete</button>
                            </td>
                        </tr>`;
                    });
                } else {
                    html += '<tr><td colspan="5" style="text-align: center; padding: 2rem;">No stories yet.</td></tr>';
                }
                
                html += '</tbody></table>';
                container.innerHTML = html;
            } catch (error) {
                console.error('Error loading stories:', error);
            }
        }

        async function loadGuides() {
            try {
                const response = await fetch('api/content_api.php?type=guides&action=list');
                const data = await response.json();
                
                const container = document.getElementById('guides-list');
                let html = '<table class="data-table"><thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>';
                
                if (data.data && data.data.length > 0) {
                    data.data.forEach(item => {
                        html += `<tr>
                            <td>${item.title}</td>
                            <td>${item.category || '-'}</td>
                            <td><span class="badge badge-${item.status}">${item.status}</span></td>
                            <td>${new Date(item.updated_at).toLocaleDateString()}</td>
                            <td>
                                <button class="btn btn-secondary btn-sm" onclick="openEditor('guides', ${item.id})">Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteContent('guides', ${item.id})">Delete</button>
                            </td>
                        </tr>`;
                    });
                } else {
                    html += '<tr><td colspan="5" style="text-align: center; padding: 2rem;">No guides yet.</td></tr>';
                }
                
                html += '</tbody></table>';
                container.innerHTML = html;
            } catch (error) {
                console.error('Error loading guides:', error);
            }
        }

        async function loadContacts() {
            try {
                const response = await fetch('api/contacts_api.php');
                const data = await response.json();
                
                const container = document.getElementById('contacts-list');
                let html = '<table class="data-table"><thead><tr><th>Name</th><th>Email</th><th>Message</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead><tbody>';
                
                if (data.data && data.data.length > 0) {
                    data.data.forEach(item => {
                        html += `<tr>
                            <td>${item.name}</td>
                            <td>${item.email}</td>
                            <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${item.message}</td>
                            <td><span class="badge badge-${item.status}">${item.status}</span></td>
                            <td>${new Date(item.created_at).toLocaleDateString()}</td>
                            <td>
                                <select onchange="updateContactStatus(${item.id}, this.value)" style="padding: 0.5rem; border: 1px solid #ede9df; border-radius: 8px;">
                                    <option value="new" ${item.status === 'new' ? 'selected' : ''}>New</option>
                                    <option value="read" ${item.status === 'read' ? 'selected' : ''}>Read</option>
                                    <option value="replied" ${item.status === 'replied' ? 'selected' : ''}>Replied</option>
                                    <option value="archived" ${item.status === 'archived' ? 'selected' : ''}>Archived</option>
                                </select>
                            </td>
                        </tr>`;
                    });
                } else {
                    html += '<tr><td colspan="6" style="text-align: center; padding: 2rem;">No messages yet.</td></tr>';
                }
                
                html += '</tbody></table>';
                container.innerHTML = html;
            } catch (error) {
                console.error('Error loading contacts:', error);
            }
        }

        async function openEditor(type, id) {
            try {
                document.getElementById('content-type').value = type;
                document.getElementById('editor-modal').classList.add('active');
                
                if (id) {
                    document.getElementById('editor-title').textContent = 'Edit ' + type;
                    const response = await fetch(`api/content_api.php?type=${type}&action=get&id=${id}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        document.getElementById('content-id').value = data.data.id;
                        document.getElementById('content-title').value = data.data.title || '';
                        document.getElementById('content-slug').value = data.data.slug || '';
                        document.getElementById('content-category').value = data.data.category || '';
                        document.getElementById('content-image').value = data.data.featured_image || '';
                        document.getElementById('content-excerpt').value = data.data.excerpt || '';
                        document.getElementById('content-body').value = data.data.content || '';
                        document.getElementById('content-status').value = data.data.status || 'draft';
                    } else {
                        alert('Error loading content: ' + data.error);
                    }
                } else {
                    document.getElementById('editor-title').textContent = 'New ' + type;
                    document.getElementById('editor-form').reset();
                    document.getElementById('content-id').value = '';
                    builderEnabled = false;
                    blocks = [];
                    document.getElementById('rich-builder').style.display = 'none';
                    document.getElementById('builder-status').textContent = 'Rich builder: off';
                }
            } catch (error) {
                alert('Error opening editor: ' + error.message);
            }
        }

        function closeEditor() {
            document.getElementById('editor-modal').classList.remove('active');
        }

        async function deleteContent(type, id) {
            if (!confirm(`Are you sure you want to delete this ${type}?`)) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                const response = await fetch(`api/content_api.php?type=${type}&action=delete`, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    alert('Successfully deleted!');
                    loadStories();
                    loadGuides();
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete'));
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        async function updateUserRole(userId, newRole) {
            try {
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('role', newRole);
                
                await fetch('api/user_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                alert('User role updated!');
                location.reload();
            } catch (error) {
                alert('Error updating role: ' + error.message);
            }
        }

        async function updateContactStatus(id, status) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', status);
            
            await fetch('api/contacts_api.php', {
                method: 'POST',
                body: formData
            });
        }

        // Form submissions
        document.getElementById('editor-form').onsubmit = async (e) => {
            e.preventDefault();
            
            try {
                const formData = new FormData(e.target);
                const type = document.getElementById('content-type').value;
                const action = document.getElementById('content-id').value ? 'update' : 'create';
                
                formData.append('action', action);
                if (document.getElementById('content-id').value) {
                    formData.append('id', document.getElementById('content-id').value);
                }
                
                const response = await fetch(`api/content_api.php?type=${type}&action=${action}`, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    alert(`${action === 'create' ? 'Created' : 'Updated'} successfully!`);
                    closeEditor();
                    loadStories();
                    loadGuides();
                } else {
                    alert('Error: ' + (data.error || 'Failed to save'));
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        };

        function openCreateEditorModal() {
            document.getElementById('create-editor-modal').classList.add('active');
        }

        function closeCreateEditorModal() {
            document.getElementById('create-editor-modal').classList.remove('active');
        }

        function openCreateAdminModal() {
            document.getElementById('create-admin-modal').classList.add('active');
        }

        function closeCreateAdminModal() {
            document.getElementById('create-admin-modal').classList.remove('active');
        }

        // Create Editor Form
        document.getElementById('create-editor-form').onsubmit = async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('role', 'editor');
            
            const response = await fetch('api/user_api.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            if (data.success) {
                alert('Editor account created!');
                closeCreateEditorModal();
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        };

        // Create Admin Form
        document.getElementById('create-admin-form').onsubmit = async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('role', 'admin');
            
            const response = await fetch('api/user_api.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            if (data.success) {
                alert('Admin account created!');
                closeCreateAdminModal();
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        };

        // Testing functions
        async function testGitHub() {
            const resultsDiv = document.getElementById('test-results');
            resultsDiv.innerHTML = '<p>Testing GitHub connection...</p>';
            
            try {
                const response = await fetch('api/test_github.php');
                const data = await response.json();
                
                let html = '<div style="background: #F5F1E9; padding: 1rem; border-radius: 8px;">';
                html += '<h3 style="margin-bottom: 1rem;">GitHub Test Results</h3>';
                
                if (data.success) {
                    html += '<p style="color: green; margin: 0.5rem 0;">✓ GitHub connection successful</p>';
                } else {
                    html += '<p style="color: red; margin: 0.5rem 0;">✗ GitHub connection failed</p>';
                }
                
                html += '</div>';
                resultsDiv.innerHTML = html;
            } catch (error) {
                resultsDiv.innerHTML = '<p style="color: red;">Error: ' + error.message + '</p>';
            }
        }

        async function testDatabase() {
            window.open('test_connection.php', '_blank');
        }

        async function testAPIs() {
            const resultsDiv = document.getElementById('test-results');
            resultsDiv.innerHTML = '<p>Testing APIs...</p>';
            
            try {
                const tests = [
                    { name: 'Content API', url: 'api/content_api.php?type=stories&action=list' },
                    { name: 'Contacts API', url: 'api/contacts_api.php' }
                ];
                
                let html = '<div style="background: #F5F1E9; padding: 1rem; border-radius: 8px;">';
                html += '<h3 style="margin-bottom: 1rem;">API Test Results</h3>';
                
                for (const test of tests) {
                    try {
                        const response = await fetch(test.url);
                        html += `<p style="color: green; margin: 0.5rem 0;">✓ ${test.name}</p>`;
                    } catch (error) {
                        html += `<p style="color: red; margin: 0.5rem 0;">✗ ${test.name}</p>`;
                    }
                }
                
                html += '</div>';
                resultsDiv.innerHTML = html;
            } catch (error) {
                resultsDiv.innerHTML = '<p style="color: red;">Error: ' + error.message + '</p>';
            }
        }

        async function toggleMaintenance(action) {
            try {
                const response = await fetch('api/maintenance.php', {
                    method: 'POST',
                    body: JSON.stringify({ action }),
                    headers: { 'Content-Type': 'application/json' }
                });
                
                const data = await response.json();
                if (data.success) {
                    alert('Maintenance mode ' + action + 'd successfully!');
                    loadMaintenanceStatus();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        async function loadMaintenanceStatus() {
            try {
                const response = await fetch('.maintenance');
                const isActive = response.ok;
                
                const statusDiv = document.getElementById('maintenance-status');
                statusDiv.innerHTML = `
                    <div style="padding: 1rem; background: ${isActive ? '#ffebee' : '#e8f5e9'}; border-radius: 8px; border: 1px solid ${isActive ? '#c62828' : '#388e3c'};">
                        <p style="font-weight: 600; margin-bottom: 0.5rem;">Status: <span style="color: ${isActive ? '#c62828' : '#388e3c'};">${isActive ? 'ACTIVE' : 'INACTIVE'}</span></p>
                        <p style="font-size: 0.875rem; opacity: 0.8;">${isActive ? 'The site is currently in maintenance mode.' : 'The site is publicly accessible.'}</p>
                    </div>
                `;
            } catch (error) {
                console.error('Error loading maintenance status:', error);
            }
        }

        // Rich Builder functions
        let builderEnabled = false;
        let blocks = [];

        function toggleBuilder() {
            builderEnabled = !builderEnabled;
            document.getElementById('rich-builder').style.display = builderEnabled ? 'block' : 'none';
            document.getElementById('builder-status').textContent = `Rich builder: ${builderEnabled ? 'on' : 'off'}`;
            
            if (builderEnabled && blocks.length === 0) {
                try {
                    const raw = document.getElementById('content-body').value.trim();
                    const parsed = JSON.parse(raw);
                    if (Array.isArray(parsed)) {
                        blocks = parsed;
                        renderBlocks();
                    }
                } catch (e) {
                    // ignore
                }
            }
        }

        function addBlock(type) {
            const newBlock = {
                id: crypto.randomUUID(),
                type,
                data: {}
            };
            if (type === 'heading') newBlock.data = { text: 'A Heading for a New Section', level: 2 };
            if (type === 'subheading') newBlock.data = { text: 'A Sub-heading for Finer Details', level: 3 };
            if (type === 'paragraph') newBlock.data = { text: 'Write your paragraph...' };
            if (type === 'blockquote') newBlock.data = { text: '“Quote goes here."' };
            if (type === 'list') newBlock.data = { items: ['First item', 'Second item'] };
            if (type === 'image') newBlock.data = { url: '', alt: 'Descriptive alt text' };
            
            blocks.push(newBlock);
            renderBlocks();
        }

        function removeBlock(id) {
            blocks = blocks.filter(b => b.id !== id);
            renderBlocks();
        }

        function moveBlock(id, dir) {
            const idx = blocks.findIndex(b => b.id === id);
            if (idx < 0) return;
            const swapWith = dir === 'up' ? idx - 1 : idx + 1;
            if (swapWith < 0 || swapWith >= blocks.length) return;
            const tmp = blocks[idx];
            blocks[idx] = blocks[swapWith];
            blocks[swapWith] = tmp;
            renderBlocks();
        }

        function renderBlocks() {
            const container = document.getElementById('blocks-container');
            container.innerHTML = '';
            
            blocks.forEach(block => {
                const wrapper = document.createElement('div');
                wrapper.className = 'block-item';
                
                const header = document.createElement('div');
                header.className = 'block-header';
                header.innerHTML = `<strong>${block.type}</strong>`;
                
                const actions = document.createElement('div');
                actions.className = 'block-actions';
                actions.innerHTML = `
                    <button type="button" onclick="moveBlock('${block.id}', 'up')" class="btn btn-secondary btn-sm">↑</button>
                    <button type="button" onclick="moveBlock('${block.id}', 'down')" class="btn btn-secondary btn-sm">↓</button>
                    <button type="button" onclick="removeBlock('${block.id}')" class="btn btn-danger btn-sm">×</button>
                `;
                header.appendChild(actions);
                wrapper.appendChild(header);
                
                // Block content based on type
                if (block.type === 'heading' || block.type === 'subheading') {
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.value = block.data.text || '';
                    input.oninput = (e) => block.data.text = e.target.value;
                    input.style.width = '100%';
                    input.style.padding = '0.5rem';
                    wrapper.appendChild(input);
                } else if (block.type === 'paragraph') {
                    const textarea = document.createElement('textarea');
                    textarea.value = block.data.text || '';
                    textarea.oninput = (e) => block.data.text = e.target.value;
                    textarea.style.width = '100%';
                    textarea.style.minHeight = '100px';
                    textarea.style.padding = '0.5rem';
                    wrapper.appendChild(textarea);
                } else if (block.type === 'image') {
                    const urlInput = document.createElement('input');
                    urlInput.type = 'url';
                    urlInput.placeholder = 'https://...';
                    urlInput.value = block.data.url || '';
                    urlInput.oninput = (e) => block.data.url = e.target.value;
                    urlInput.style.width = '100%';
                    urlInput.style.padding = '0.5rem';
                    urlInput.style.marginBottom = '0.5rem';
                    
                    const altInput = document.createElement('input');
                    altInput.type = 'text';
                    altInput.placeholder = 'Alt text';
                    altInput.value = block.data.alt || '';
                    altInput.oninput = (e) => block.data.alt = e.target.value;
                    altInput.style.width = '100%';
                    altInput.style.padding = '0.5rem';
                    
                    wrapper.appendChild(urlInput);
                    wrapper.appendChild(altInput);
                } else if (block.type === 'blockquote') {
                    const textarea = document.createElement('textarea');
                    textarea.value = block.data.text || '';
                    textarea.oninput = (e) => block.data.text = e.target.value;
                    textarea.style.width = '100%';
                    textarea.style.minHeight = '80px';
                    textarea.style.padding = '0.5rem';
                    wrapper.appendChild(textarea);
                } else if (block.type === 'list') {
                    const items = block.data.items || [];
                    items.forEach((item, idx) => {
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.value = item;
                        input.oninput = (e) => items[idx] = e.target.value;
                        input.style.width = '100%';
                        input.style.padding = '0.5rem';
                        input.style.marginBottom = '0.5rem';
                        wrapper.appendChild(input);
                    });
                    
                    const addBtn = document.createElement('button');
                    addBtn.type = 'button';
                    addBtn.textContent = '+ Add Item';
                    addBtn.className = 'btn btn-secondary btn-sm';
                    addBtn.onclick = () => {
                        items.push('');
                        renderBlocks();
                    };
                    wrapper.appendChild(addBtn);
                }
                
                container.appendChild(wrapper);
            });
            
            // Update textarea with JSON
            document.getElementById('content-body').value = JSON.stringify(blocks);
        }

        // Load maintenance status on page load
        loadMaintenanceStatus();
    </script>
</body>
</html>
