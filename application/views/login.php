<!DOCTYPE html>
<html lang="<?= $this->config->item('current_lang') ?: 'id'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= translate('app_login_title'); ?></title>
    <!-- Bootstrap 5 & Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --brand: #0b5d5b;
            --brand-dark: #083f3e;
            --brand-light: #e6f2f1;
            --bg: #f4f6f7;
        }

        body {
            background: linear-gradient(135deg, var(--bg) 0%, #e0e6e8 100%);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #1f2937;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(11, 93, 91, 0.08);
            border: 1px solid #edf0f1;
            width: 100%;
            max-width: 380px;
            padding: 2rem 1.75rem;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            background: var(--brand-light);
            color: var(--brand);
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: .75rem;
        }

        .brand-title {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--brand-dark);
            margin-bottom: .25rem;
        }

        /* Language Switcher */
        .lang-pills {
            display: flex;
            justify-content: center;
            gap: .35rem;
            margin-bottom: 1.5rem;
            background: var(--bg);
            padding: 4px;
            border-radius: 20px;
        }

        .lang-pills a {
            flex: 1;
            text-align: center;
            padding: .3rem .5rem;
            font-size: .78rem;
            font-weight: 600;
            color: #6b7280;
            text-decoration: none;
            border-radius: 16px;
            transition: all .2s ease;
        }

        .lang-pills a.active {
            background: #ffffff;
            color: var(--brand);
            box-shadow: 0 2px 6px rgba(0, 0, 0, .06);
        }

        /* Input Group Fixing */
        .input-group-text {
            background-color: #f9fafb;
            border-color: #d1d5db;
            color: #6b7280;
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
            padding-left: 0.85rem;
            padding-right: 0.85rem;
        }

        .form-control {
            border-color: #d1d5db;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            padding: .6rem .75rem;
            font-size: .88rem;
        }

        .form-control:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(11, 93, 91, 0.15);
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--brand);
            color: var(--brand);
        }

        .btn-brand {
            background: var(--brand);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: .65rem;
            font-weight: 600;
            font-size: .9rem;
            width: 100%;
            transition: background .2s ease;
        }

        .btn-brand:hover {
            background: var(--brand-dark);
            color: #ffffff;
        }

        .alert-custom {
            font-size: .82rem;
            border-radius: 8px;
            padding: .65rem .85rem;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <!-- Switcher Bahasa -->
        <div class="lang-pills">
            <?php
            $current = get_current_lang('id');
            $langs = ['id' => 'Indonesia', 'en' => 'English', 'zh' => '中文'];
            foreach ($langs as $code => $label):
                $class = ($current === $code) ? 'active' : '';
            ?>
                <a class="<?= $class; ?>" href="<?= site_url('language/switch_lang/' . $code); ?>"><?= $label; ?></a>
            <?php endforeach; ?>
        </div>

        <!-- Header Brand -->
        <div class="brand-header">
            <div class="brand-icon">
                <i class="bi bi-columns-gap"></i>
            </div>
            <div class="brand-title"><?= translate('app_login_title');  ?></div>
           
        </div>

        <!-- Pesan Error Flashdata -->
        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger alert-custom mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                <div><?= $this->session->flashdata('error'); ?></div>
            </div>
        <?php endif; ?>

        <!-- Validation Errors -->
        <?php if (validation_errors()): ?>
            <div class="alert alert-warning alert-custom mb-3">
                <?= validation_errors('<div class="d-flex align-items-center gap-1"><i class="bi bi-dot"></i>', '</div>'); ?>
            </div>
        <?php endif; ?>

        <!-- Form Login -->
        <form action="<?= site_url('login/proses'); ?>" method="post">
            <!-- CSRF Protection -->
            <input type="hidden"
                name="<?= $this->security->get_csrf_token_name(); ?>"
                value="<?= $this->security->get_csrf_hash(); ?>">
            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary"><?= translate('app_username'); ?></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control" value="<?= set_value('username'); ?>" placeholder="Masukkan username" required autocomplete="off">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold text-secondary"><?= translate('app_password'); ?></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-brand shadow-sm">
                <?= translate('app_login_button'); ?> <i class="bi bi-arrow-right-short ms-1"></i>
            </button>
        </form>

        <div class="text-center mt-4 small text-muted" style="font-size: .75rem;">
            &copy; 2026 PT Oupai Pintu Jendela Indonesia
        </div>
    </div>

</body>

</html>