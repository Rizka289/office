<!DOCTYPE html>
<html>
<head>
    <title><?= lang('app_dashboard_title'); ?></title>
    <style>
        body { font-family: Arial; margin: 0; background: #f4f6f9; }
        .header { background: #2c3e50; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header-right { display:flex; align-items:center; gap:16px; }
        .container { padding: 30px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .btn-logout { background: #e74c3c; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; }
        .lang-switch a { color:#fff; margin:0 4px; text-decoration:none; font-size:13px; opacity:0.75; }
        .lang-switch a.active { opacity:1; font-weight:bold; text-decoration:underline; }
    </style>
</head>
<body>
    <div class="header">
        <div><strong><?= lang('app_admin_panel'); ?></strong> | <?= lang('app_hello'); ?>, <?= htmlspecialchars($user['nama']); ?></div>
        <div class="header-right">
            <div class="lang-switch">
                <?php
                    $current = $this->config->item('current_lang');
                    $langs = ['id' => 'ID', 'en' => 'EN', 'zh' => '中文'];
                    foreach ($langs as $code => $label):
                        $class = ($current === $code) ? 'active' : '';
                ?>
                    <a class="<?= $class; ?>" href="<?= site_url('login/lang/' . $code); ?>"><?= $label; ?></a>
                <?php endforeach; ?>
            </div>
            <a href="<?= site_url('login/logout'); ?>" class="btn-logout"><?= lang('app_logout'); ?></a>
        </div>
    </div>
    <div class="container">
        <div class="card">
            <h3><?= lang('app_welcome_admin'); ?></h3>
            <p><?= lang('app_welcome_message'); ?></p>
        </div>
    </div>
</body>
</html>