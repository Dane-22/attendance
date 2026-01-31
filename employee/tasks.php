<?php
// employee/tasks.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

require('../conn/db_connection.php');

// Determine active page for sidebar
$current = basename($_SERVER['PHP_SELF']);

$employeeName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$employeeId = $_SESSION['user_id'];

// Sample tasks (in a real app these come from DB)
$tasks = [
  ['id'=>1,'title'=>'Inspect foundation bolts','priority'=>'High','due'=>'2026-01-14','status'=>'todo'],
  ['id'=>2,'title'=>'Update CAD model for pier B','priority'=>'Medium','due'=>'2026-01-16','status'=>'inprogress'],
  ['id'=>3,'title'=>'Prepare materials list','priority'=>'Low','due'=>'2026-01-20','status'=>'review'],
  ['id'=>4,'title'=>'Site safety audit','priority'=>'High','due'=>'2026-01-13','status'=>'todo'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>My Tasks — JAJR</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../assets/style_employee.css">
</head>
<body class="employee-bg">
  <div class="app-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
      <div class="header-card">
        <div class="header-left">
          <button id="sidebarToggle" class="menu-toggle" aria-label="Toggle sidebar">☰</button>
          <div>
            <div class="welcome">My Tasks</div>
            <div class="text-sm text-gray-500">Drag tasks between columns. You must be Time In to move tasks to Done.</div>
          </div>
        </div>
        <div class="clock js-clock">--:--:--</div>
      </div>

      <div class="board" id="kanban">
        <!-- To Do Column -->
        <div class="column col-todo" data-column="todo">
          <div class="column-header">To Do</div>
          <div class="task-list" id="col-todo">
            <?php foreach($tasks as $t): ?>
              <?php if($t['status'] === 'todo'): ?>
                <div class="task-card" draggable="true" data-id="<?= $t['id'] ?>" data-title="<?= htmlspecialchars($t['title']) ?>" data-priority="<?= $t['priority'] ?>" data-due="<?= $t['due'] ?>">
                  <div class="priority-<?= strtolower($t['priority']) ?>"><?= htmlspecialchars($t['priority']) ?></div>
                  <div class="desc"><?= htmlspecialchars($t['title']) ?></div>
                  <div class="task-meta">Due: <?= htmlspecialchars($t['due']) ?></div>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- In Progress Column -->
        <div class="column col-inprogress" data-column="inprogress">
          <div class="column-header">In Progress</div>
          <div class="task-list" id="col-inprogress">
            <?php foreach($tasks as $t): ?>
              <?php if($t['status'] === 'inprogress'): ?>
                <div class="task-card" draggable="true" data-id="<?= $t['id'] ?>" data-title="<?= htmlspecialchars($t['title']) ?>" data-priority="<?= $t['priority'] ?>" data-due="<?= $t['due'] ?>">
                  <div class="priority-<?= strtolower($t['priority']) ?>"><?= htmlspecialchars($t['priority']) ?></div>
                  <div class="desc"><?= htmlspecialchars($t['title']) ?></div>
                  <div class="task-meta">Due: <?= htmlspecialchars($t['due']) ?></div>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Review Column -->
        <div class="column col-review" data-column="review">
          <div class="column-header">Review</div>
          <div class="task-list" id="col-review">
            <?php foreach($tasks as $t): ?>
              <?php if($t['status'] === 'review'): ?>
                <div class="task-card" draggable="true" data-id="<?= $t['id'] ?>" data-title="<?= htmlspecialchars($t['title']) ?>" data-priority="<?= $t['priority'] ?>" data-due="<?= $t['due'] ?>">
                  <div class="priority-<?= strtolower($t['priority']) ?>"><?= htmlspecialchars($t['priority']) ?></div>
                  <div class="desc"><?= htmlspecialchars($t['title']) ?></div>
                  <div class="task-meta">Due: <?= htmlspecialchars($t['due']) ?></div>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Done Column -->
        <div class="column col-done" data-column="done">
          <div class="column-header">Done</div>
          <div class="task-list" id="col-done">
            <!-- Completed today will appear here -->
          </div>
        </div>
      </div>

      <!-- modal for Clock Out summary (shared) -->
      <div id="doneSummaryModal" class="modal-backdrop">
        <div class="modal">
          <h3>Tasks completed this session</h3>
          <div id="doneList"></div>
          <div style="text-align:right; margin-top:12px;"><button id="closeModal" class="btn-clock out">Close</button></div>
        </div>
      </div>
    </main>
  </div>

  <script src="../assets/js/employee.js"></script>
</body>
</html>