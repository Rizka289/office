<!DOCTYPE html>
<html>

<head>
    <title><?= lang('app_login_title'); ?></title>
    <style>
        body {
            font-family: Arial;
            margin: 0;
            background: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .box {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            width: 320px;
        }

        .box h3 {
            margin-top: 0;
        }

        .box label {
            display: block;
            margin: 12px 0 4px;
            font-size: 14px;
        }

        .box input[type=text],
        .box input[type=password] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .box button {
            margin-top: 18px;
            width: 100%;
            padding: 10px;
            background: #2c3e50;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .error {
            background: #fdecea;
            color: #c0392b;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .lang-switch {
            text-align: center;
            margin-bottom: 14px;
            font-size: 13px;
        }

        .lang-switch a {
            margin: 0 6px;
            text-decoration: none;
            color: #2c3e50;
        }

        .lang-switch a.active {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="box">
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

        <h3><?= lang('app_login_title'); ?></h3>

        <?php if ($this->session->flashdata('error')): ?>
            <div class="error"><?= $this->session->flashdata('error'); ?></div>
        <?php endif; ?>

        <?= validation_errors('<div class="error">', '</div>'); ?>

        <form action="<?= site_url('login/proses'); ?>" method="post">
            <label><?= lang('app_username'); ?></label>
            <input type="text" name="username" value="<?= set_value('username'); ?>">

            <label><?= lang('app_password'); ?></label>
            <input type="password" name="password">

            <button type="submit"><?= lang('app_login_button'); ?></button>
        </form>
    </div>
</body>

</html>