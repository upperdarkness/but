<?php
$title = 'Edit Sector - Admin - BlackNova Traders';
$showHeader = false;
ob_start();
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

<h2 style="color: #e74c3c;">🛡️ Edit Sector: <?= (int)$sector['sector_id'] ?></h2>

<div class="admin-nav">
    <a href="/admin">Dashboard</a>
    <a href="/admin/players">Players</a>
    <a href="/admin/teams">Teams</a>
    <a href="/admin/universe"><strong>Universe</strong></a>
    <a href="/admin/settings">Settings</a>
    <a href="/admin/logs">Logs</a>
    <a href="/admin/statistics">Statistics</a>
</div>

<div style="background: rgba(15, 76, 117, 0.2); padding: 30px; border-radius: 8px; max-width: 1000px;">
    <form action="/admin/universe/sector/<?= (int)$sector['sector_id'] ?>/update" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($session->getCsrfToken()) ?>">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div>
                <h3>Sector Information</h3>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Sector ID:</label>
                    <input type="text" value="<?= (int)$sector['sector_id'] ?>" disabled style="background: rgba(127, 140, 141, 0.2);">
                    <small style="color: #7f8c8d; display: block; margin-top: 5px;">Sector ID cannot be changed</small>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Sector Name:</label>
                    <input type="text" name="sector_name" value="<?= htmlspecialchars($sector['sector_name'] ?? '') ?>" style="width: 100%;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Zone ID:</label>
                    <input type="number" name="zone_id" value="<?= (int)($sector['zone_id'] ?? 1) ?>" min="1" style="width: 100%;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Port Type:</label>
                    <select name="port_type" style="width: 100%; padding: 8px;">
                        <option value="none" <?= ($sector['port_type'] ?? 'none') === 'none' ? 'selected' : '' ?>>None</option>
                        <option value="ore" <?= ($sector['port_type'] ?? '') === 'ore' ? 'selected' : '' ?>>Ore</option>
                        <option value="organics" <?= ($sector['port_type'] ?? '') === 'organics' ? 'selected' : '' ?>>Organics</option>
                        <option value="goods" <?= ($sector['port_type'] ?? '') === 'goods' ? 'selected' : '' ?>>Goods</option>
                        <option value="energy" <?= ($sector['port_type'] ?? '') === 'energy' ? 'selected' : '' ?>>Energy</option>
                        <option value="special" <?= ($sector['port_type'] ?? '') === 'special' ? 'selected' : '' ?>>Special</option>
                    </select>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">
                        <input type="checkbox" name="is_starbase" value="1" <?= ($sector['is_starbase'] ?? false) ? 'checked' : '' ?>>
                        Starbase (Safe Zone)
                    </label>
                    <small style="color: #7f8c8d; display: block; margin-top: 5px;">
                        Starbases prohibit combat and allow ship upgrades
                    </small>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Beacon Message:</label>
                    <textarea name="beacon" rows="3" style="width: 100%;"><?= htmlspecialchars($sector['beacon'] ?? '') ?></textarea>
                    <small style="color: #7f8c8d; display: block; margin-top: 5px;">
                        Beacon message displayed to players entering this sector
                    </small>
                </div>
            </div>

            <div>
                <h3>Port Inventory</h3>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Ore:</label>
                    <input type="number" name="port_ore" value="<?= (int)($sector['port_ore'] ?? 0) ?>" min="0" style="width: 100%;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Organics:</label>
                    <input type="number" name="port_organics" value="<?= (int)($sector['port_organics'] ?? 0) ?>" min="0" style="width: 100%;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Goods:</label>
                    <input type="number" name="port_goods" value="<?= (int)($sector['port_goods'] ?? 0) ?>" min="0" style="width: 100%;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Energy:</label>
                    <input type="number" name="port_energy" value="<?= (int)($sector['port_energy'] ?? 0) ?>" min="0" style="width: 100%;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Colonists:</label>
                    <input type="number" name="port_colonists" value="<?= (int)($sector['port_colonists'] ?? 0) ?>" min="0" style="width: 100%;">
                </div>
            </div>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(52, 152, 219, 0.2);">
            <h3>Sector Statistics</h3>
            <table style="width: 100%;">
                <tr>
                    <td><strong>Planets:</strong></td>
                    <td><?= number_format($planetCount) ?></td>
                </tr>
                <tr>
                    <td><strong>Linked Sectors:</strong></td>
                    <td><?= count($linkedSectors) ?></td>
                </tr>
                <?php if (!empty($linkedSectors)): ?>
                <tr>
                    <td><strong>Links To:</strong></td>
                    <td>
                        <?php foreach ($linkedSectors as $link): ?>
                            <a href="/admin/universe/sector/<?= (int)$link['sector_id'] ?>" style="color: #3498db; margin-right: 10px;">
                                Sector <?= (int)$link['sector_id'] ?>
                            </a>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 30px;">
            <button type="submit" class="btn" style="flex: 1; background: rgba(46, 204, 113, 0.3); border-color: #2ecc71;">
                Update Sector
            </button>
            <a href="/admin/universe" class="btn" style="flex: 1; text-align: center;">
                Back to Universe
            </a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>
