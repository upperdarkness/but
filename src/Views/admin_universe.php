<?php
$title = 'Universe Management - Admin - BlackNova Traders';
$showHeader = false;
ob_start();

// Get search functionality
$search = trim($_GET['search'] ?? '');
?>

<style>
    .admin-nav {
        background: rgba(231, 76, 60, 0.2);
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid rgba(231, 76, 60, 0.5);
    }

    .admin-nav a {
        color: #e74c3c;
        margin-right: 15px;
        text-decoration: none;
        padding: 8px 15px;
        border-radius: 5px;
        transition: background 0.3s;
    }

    .admin-nav a:hover {
        background: rgba(231, 76, 60, 0.3);
    }
</style>

<h2 style="color: #e74c3c;">🛡️ Universe Management</h2>

<div class="admin-nav">
    <a href="/admin">Dashboard</a>
    <a href="/admin/players">Players</a>
    <a href="/admin/teams">Teams</a>
    <a href="/admin/universe"><strong>Universe</strong></a>
    <a href="/admin/settings">Settings</a>
    <a href="/admin/logs">Logs</a>
    <a href="/admin/statistics">Statistics</a>
</div>

<div class="alert alert-info">
    <strong>Universe Status:</strong> <?= number_format($sectorCount) ?> sectors exist in the game universe.
</div>

<div style="background: rgba(15, 76, 117, 0.2); padding: 30px; border-radius: 8px; margin-bottom: 30px;">
    <h3 style="color: #e74c3c;">⚠️ Danger Zone: Regenerate Universe</h3>
    <p style="margin: 20px 0; color: #e0e0e0;">
        Regenerating the universe will:
    </p>
    <ul style="margin-left: 20px; color: #e0e0e0; line-height: 1.8;">
        <li>Delete all existing sectors and links</li>
        <li>Delete all planets</li>
        <li>Generate a completely new universe</li>
        <li>Players will remain but be placed in sector 1</li>
        <li><strong>This action cannot be undone!</strong></li>
    </ul>

    <div class="alert alert-warning" style="margin: 20px 0;">
        <strong>Warning:</strong> This will disrupt all active games. Only use this when starting a new game round
        or if the universe is corrupted.
    </div>

    <form action="/admin/universe/regenerate" method="post"
          onsubmit="return confirm('Are you ABSOLUTELY SURE you want to regenerate the entire universe? This will delete all sectors and planets! Type CONFIRM in the box below.') && prompt('Type CONFIRM to proceed:') === 'CONFIRM';">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($session->getCsrfToken()) ?>">
        <button type="submit" class="btn" style="background: rgba(231, 76, 60, 0.5); border-color: #e74c3c;">
            Regenerate Universe
        </button>
    </form>
</div>

<h3>All Sectors</h3>
<div style="margin-bottom: 15px;">
    <form method="get" action="/admin/universe" style="display: inline-block;">
        <input type="text" name="search" placeholder="Search by Sector ID or Name..." 
               value="<?= htmlspecialchars($search) ?>" 
               style="padding: 8px; width: 300px;">
        <button type="submit" class="btn">Search</button>
        <?php if (!empty($search)): ?>
        <a href="/admin/universe" class="btn" style="margin-left: 10px;">Clear</a>
        <?php endif; ?>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>Sector ID</th>
            <th>Name</th>
            <th>Port</th>
            <th>Starbase</th>
            <th>Beacon</th>
            <th>Planets</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($sectors as $sector): ?>
        <tr>
            <td><?= (int)$sector['sector_id'] ?></td>
            <td><?= htmlspecialchars($sector['sector_name']) ?></td>
            <td>
                <?php if ($sector['port_type'] && $sector['port_type'] !== 'none'): ?>
                    <span style="color: #2ecc71;"><?= htmlspecialchars(ucfirst($sector['port_type'])) ?></span>
                <?php else: ?>
                    <span style="color: #7f8c8d;">None</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($sector['is_starbase'] ?? false): ?>
                    <span style="color: #2ecc71;">🛡️ Yes</span>
                <?php else: ?>
                    <span style="color: #7f8c8d;">No</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if (!empty($sector['beacon'])): ?>
                    <?= htmlspecialchars(substr($sector['beacon'], 0, 30)) ?><?= strlen($sector['beacon']) > 30 ? '...' : '' ?>
                <?php else: ?>
                    <span style="color: #7f8c8d;">-</span>
                <?php endif; ?>
            </td>
            <td>
                <?php
                $planetCount = (int)($sector['planet_count'] ?? 0);
                echo $planetCount > 0 ? "<span style='color: #3498db;'>$planetCount</span>" : '-';
                ?>
            </td>
            <td>
                <a href="/admin/universe/sector/<?= (int)$sector['sector_id'] ?>" class="btn" style="padding: 5px 10px; font-size: 12px;">
                    Edit
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (empty($_GET['search']) && $totalPages > 1): ?>
<div style="margin-top: 20px; text-align: center;">
    <?php if ($page > 1): ?>
    <a href="/admin/universe?page=<?= $page - 1 ?>" class="btn">Previous</a>
    <?php endif; ?>
    
    <span style="margin: 0 20px; color: #e0e0e0;">
        Page <?= $page ?> of <?= $totalPages ?>
    </span>
    
    <?php if ($page < $totalPages): ?>
    <a href="/admin/universe?page=<?= $page + 1 ?>" class="btn">Next</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<div style="margin-top: 30px;">
    <a href="/admin" class="btn">Back to Dashboard</a>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>
