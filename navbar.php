<div class="navbar">
    <div style="font-weight: 700; color: var(--text-main); font-family: 'Outfit'; font-size: 1.1rem;">
        <?php 
            $title = str_replace('.php', '', basename($_SERVER['PHP_SELF']));
            echo ucfirst($title == 'index' ? 'Dashboard' : $title);
        ?>
    </div>
    <div class="user-profile">
        <span style="color: var(--text-muted); font-size: 0.9rem;"><?php echo $_SESSION['username'] ?? 'Administrator'; ?></span>
        <div class="user-avatar">
            <?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?>
        </div>
    </div>
</div>
