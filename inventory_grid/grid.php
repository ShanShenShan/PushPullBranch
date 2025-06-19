<?php
// inventory_grid/index.php
require_once '../includes/connection.php';

// Handle grid creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_grid'])) {
    $name = $_POST['name'];
    $rows = intval($_POST['rows']);
    $cols = intval($_POST['cols']);
    $background_color = $_POST['background_color'];
    $stmt = $pdo->prepare("INSERT INTO grids (name, rows, cols, background_color, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$name, $rows, $cols, $background_color]);
}

// Handle adding a box (location)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_box'])) {
    $grid_id = intval($_POST['grid_id']);
    $name = $_POST['box_name'];
    $row = intval($_POST['box_row']);
    $col = intval($_POST['box_col']);
    $width = intval($_POST['box_width']);
    $height = intval($_POST['box_height']);
    $color = $_POST['box_color'];
    $stmt = $pdo->prepare("INSERT INTO locations (grid_id, name, row, col, width, height, color, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$grid_id, $name, $row, $col, $width, $height, $color]);
}

// Handle layout saving
$save_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_layout'])) {
    $positions = json_decode($_POST['positions'], true);
    if (is_array($positions)) {
        $success = true;
        foreach ($positions as $box) {
            if (!isset($box['id'], $box['row'], $box['col'])) {
                $success = false;
                break;
            }
            $stmt = $pdo->prepare("UPDATE locations SET row = ?, col = ? WHERE id = ?");
            if (!$stmt->execute([$box['row'], $box['col'], $box['id']])) {
                $success = false;
                break;
            }
        }
        $save_message = $success ? 'Layout saved successfully!' : 'Error saving layout.';
    } else {
        $save_message = 'Invalid data submitted.';
    }
}

// Fetch all grids
$grids = [];
$stmt = $pdo->query("SELECT * FROM grids ORDER BY created_at DESC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $grids[] = $row;
}

// Fetch selected grid
$selected_grid = null;
$boxes = [];
if (isset($_GET['grid_id'])) {
    $grid_id = intval($_GET['grid_id']);
    $stmt = $pdo->prepare("SELECT * FROM grids WHERE id = ?");
    $stmt->execute([$grid_id]);
    $selected_grid = $stmt->fetch(PDO::FETCH_ASSOC);
    // Fetch boxes (locations) for this grid
    $stmt = $pdo->prepare("SELECT * FROM locations WHERE grid_id = ?");
    $stmt->execute([$grid_id]);
    $boxes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Grid Management</title>
    <link rel="stylesheet" href="../css/sb-admin-2.min.css">
    <style>
        .grid-container {
            display: grid;
            gap: 2px;
            margin-top: 20px;
            position: relative;
        }
        .grid-cell {
            background: #f8f9fc;
            border: 1px solid #ddd;
            width: 40px;
            height: 40px;
            position: relative;
        }
        .box {
            width: 100%;
            height: 100%;
            background: #007bff;
            border: 2px solid #333;
            opacity: 0.85;
            color: #fff;
            text-align: center;
            font-size: 0.9em;
            cursor: grab;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
        }
        .box.dragging {
            opacity: 0.5;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h2>Inventory Grid Management</h2>
    <?php if (!empty($save_message)): ?>
        <div class="alert alert-info"> <?= htmlspecialchars($save_message) ?> </div>
    <?php endif; ?>
    <form method="POST" class="mb-4">
        <div class="form-row">
            <div class="form-group col-md-3">
                <input type="text" name="name" class="form-control" placeholder="Grid Name" required>
            </div>
            <div class="form-group col-md-2">
                <input type="number" name="rows" class="form-control" placeholder="Rows" min="1" required>
            </div>
            <div class="form-group col-md-2">
                <input type="number" name="cols" class="form-control" placeholder="Columns" min="1" required>
            </div>
            <div class="form-group col-md-2">
                <input type="color" name="background_color" class="form-control" value="#f8f9fc">
            </div>
            <div class="form-group col-md-2">
                <button type="submit" name="create_grid" class="btn btn-primary">Create Grid</button>
            </div>
        </div>
    </form>
    <h4>Existing Grids</h4>
    <ul class="list-group mb-4">
        <?php foreach ($grids as $grid): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <a href="?grid_id=<?= $grid['id'] ?>"> <?= htmlspecialchars($grid['name']) ?> (<?= $grid['rows'] ?>x<?= $grid['cols'] ?>)</a>
                <span style="background: <?= htmlspecialchars($grid['background_color']) ?>; width: 20px; height: 20px; display: inline-block; border: 1px solid #ccc;"></span>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if ($selected_grid): ?>
        <h4>Grid: <?= htmlspecialchars($selected_grid['name']) ?> (<?= $selected_grid['rows'] ?>x<?= $selected_grid['cols'] ?>)</h4>
        <!-- Add Box Form -->
        <form method="POST" class="mb-3">
            <input type="hidden" name="grid_id" value="<?= $selected_grid['id'] ?>">
            <div class="form-row">
                <div class="form-group col-md-2">
                    <input type="text" name="box_name" class="form-control" placeholder="Box Name" required>
                </div>
                <div class="form-group col-md-1">
                    <input type="number" name="box_row" class="form-control" placeholder="Row" min="0" max="<?= $selected_grid['rows']-1 ?>" required>
                </div>
                <div class="form-group col-md-1">
                    <input type="number" name="box_col" class="form-control" placeholder="Col" min="0" max="<?= $selected_grid['cols']-1 ?>" required>
                </div>
                <div class="form-group col-md-1">
                    <input type="number" name="box_width" class="form-control" placeholder="W" min="1" max="<?= $selected_grid['cols'] ?>" value="1" required>
                </div>
                <div class="form-group col-md-1">
                    <input type="number" name="box_height" class="form-control" placeholder="H" min="1" max="<?= $selected_grid['rows'] ?>" value="1" required>
                </div>
                <div class="form-group col-md-2">
                    <input type="color" name="box_color" class="form-control" value="#007bff">
                </div>
                <div class="form-group col-md-2">
                    <button type="submit" name="add_box" class="btn btn-success">Add Box</button>
                </div>
            </div>
        </form>
        <!-- Save Layout Form -->
        <form id="saveLayoutForm" method="POST">
            <input type="hidden" name="positions" id="positionsInput">
            <button type="submit" name="save_layout" id="saveLayoutBtn" class="btn btn-primary mb-3" disabled>Save Layout</button>
        </form>
        <!-- Render Grid with Boxes -->
        <div id="grid" class="grid-container" style="grid-template-columns: repeat(<?= $selected_grid['cols'] ?>, 40px); grid-template-rows: repeat(<?= $selected_grid['rows'] ?>, 40px); background: <?= htmlspecialchars($selected_grid['background_color']) ?>; width: calc(<?= $selected_grid['cols'] ?> * 42px); height: calc(<?= $selected_grid['rows'] ?> * 42px);">
            <?php for ($r = 0; $r < $selected_grid['rows']; $r++): ?>
                <?php for ($c = 0; $c < $selected_grid['cols']; $c++): ?>
                    <div class="grid-cell" data-row="<?= $r ?>" data-col="<?= $c ?>">
                        <?php
                        foreach ($boxes as $box) {
                            if ($box['row'] == $r && $box['col'] == $c) {
                                echo '<div class="box" draggable="true" data-box-id="' . $box['id'] . '" style="background:' . htmlspecialchars($box['color']) . ';">' . htmlspecialchars($box['name']) . '</div>';
                            }
                        }
                        ?>
                    </div>
                <?php endfor; ?>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
<script>
const grid = document.getElementById('grid');
let draggedBox = null;
let draggedBoxId = null;
let layoutChanged = false;
const saveBtn = document.getElementById('saveLayoutBtn');

function enableSaveButton() {
    saveBtn.disabled = false;
    saveBtn.classList.remove('btn-secondary');
    saveBtn.classList.add('btn-primary');
}
function disableSaveButton() {
    saveBtn.disabled = true;
    saveBtn.classList.remove('btn-primary');
    saveBtn.classList.add('btn-secondary');
}
disableSaveButton();

grid && grid.addEventListener('dragstart', function(e) {
    if (e.target.classList.contains('box')) {
        draggedBox = e.target;
        draggedBoxId = e.target.getAttribute('data-box-id');
        e.target.classList.add('dragging');
    }
});
grid && grid.addEventListener('dragend', function(e) {
    if (draggedBox) {
        draggedBox.classList.remove('dragging');
        draggedBox = null;
        draggedBoxId = null;
    }
});

grid && grid.querySelectorAll('.grid-cell').forEach(cell => {
    cell.addEventListener('dragover', function(e) {
        e.preventDefault();
    });
    cell.addEventListener('drop', function(e) {
        e.preventDefault();
        if (draggedBoxId) {
            // Move the box in the DOM
            this.innerHTML = '';
            this.appendChild(draggedBox);
            // Update box data attributes
            draggedBox.setAttribute('data-row', this.getAttribute('data-row'));
            draggedBox.setAttribute('data-col', this.getAttribute('data-col'));
            layoutChanged = true;
            enableSaveButton();
        }
    });
});

document.getElementById('saveLayoutForm')?.addEventListener('submit', function(e) {
    // Gather all box positions
    const boxes = Array.from(document.querySelectorAll('.box')).map(box => ({
        id: box.getAttribute('data-box-id'),
        row: box.parentElement.getAttribute('data-row'),
        col: box.parentElement.getAttribute('data-col')
    }));
    document.getElementById('positionsInput').value = JSON.stringify(boxes);
    // Do NOT disable the button here; let the form submit normally
    // The button will be disabled after reload
});
</script>
</body>
</html>
