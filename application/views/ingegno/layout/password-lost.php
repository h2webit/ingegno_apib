<?php
// What is today's date - number
$day = date("z");

//  Days of spring
$spring_starts = date("z", strtotime("March 21"));
$spring_ends   = date("z", strtotime("June 20"));

//  Days of summer
$summer_starts = date("z", strtotime("June 21"));
$summer_ends   = date("z", strtotime("September 22"));

//  Days of autumn
$autumn_starts = date("z", strtotime("September 23"));
$autumn_ends   = date("z", strtotime("December 20"));

//  If $day is between the days of spring, summer, autumn, and winter
if ($day >= $spring_starts && $day <= $spring_ends) :
    $season = "spring";
elseif ($day >= $summer_starts && $day <= $summer_ends) :
    $season = "summer";
elseif ($day >= $autumn_starts && $day <= $autumn_ends) :
    $season = "autumn";
else :
    $season = "winter";
endif;
?>
<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en" class="no-js">
<!--<![endif]-->
<!-- BEGIN HEAD -->

<head>
    <meta charset="utf-8" />
    <title><?php e('Password forget?'); ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <meta name="MobileOptimized" content="320">

    <!-- CORE LEVEL STYLES -->
    <link rel="stylesheet" type="text/css" href="<?php echo base_url_template("template/adminlte/bower_components/bootstrap/dist/css/bootstrap.min.css?v={$this->config->item('version')}"); ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo base_url_template("template/adminlte/bower_components/@fortawesome/fontawesome-free/css/all.min.css?v=" . VERSION); ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo base_url_template("template/adminlte/bower_components/Ionicons/css/ionicons.min.css?v={$this->config->item('version')}"); ?>" />

    <!-- <link rel="stylesheet" type="text/css" href="<?php echo base_url_template("template/adminlte/dist/css/AdminLTE.min.css?v={$this->config->item('version')}"); ?>" /> -->
    <link rel="stylesheet" type="text/css" href="<?php echo base_url_template("template/adminlte/plugins/iCheck/square/blue.css?v={$this->config->item('version')}"); ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo base_url_template("template/adminlte_custom/custom.css?v={$this->config->item('version')}"); ?>" />

    <!-- CUSTOM CSS (ONNLY FOR THIS TEMPLATE)-->
    <?php echo $this->layout->addTemplateStylesheet('ingegno', 'css/ingegno.css'); ?>

    <!-- Google Font -->
    <!-- INTER FONT -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <?php $this->layout->addDinamicJavascript([
            //"var base_url = '" . base_url() . "';",
            //"var base_url_admin = '" . base_url_admin() . "';",
            //"var base_url_template = '" . base_url_template() . "';",
            //"var base_url_scripts = '" . base_url_scripts() . "';",
            //"var base_url_uploads = '" . base_url_uploads() . "';",
            "var lang_code = '" . ((!empty($lang['languages_code'])) ? $lang['languages_code'] : 'en-EN') . "';",
            "var lang_short_code = '" . ((!empty($lang['languages_code'])) ? (explode('-', $lang['languages_code'])[0]) : 'en') . "';",
        ], 'config.js'); ?>

    <?php
        /*if ($this->settings['settings_login_background']) {
            $data['custom'] = [
                    '.background_img' => [
                            'background-image' => "linear-gradient(rgba(23, 23, 23, 0.3), rgba(18, 20, 23, 0.8)), url(" . base_url("uploads/" . $this->settings['settings_login_background']) . ")!important"
                    ]
            ];
        } else {
            $data['custom'] = [
                    '.background_img' => [
                            'background-image' => "linear-gradient(rgba(23, 23, 23, 0.3), rgba(18, 20, 23, 0.8)), url(" . ((!empty($season)) ? base_url("images/{$season}.jpg") : '') . ")!important"
                    ]
            ];
        }
        
        if (defined('LOGIN_COLOR') && !empty(LOGIN_COLOR)) {
            $data['custom'] = array_merge([
                    '.login-page, .register-page' => [
                            'background' => LOGIN_COLOR
                    ]
            ], $data['custom']);
        }
        if (defined('LOGIN_TITLE_COLOR') && !empty(LOGIN_TITLE_COLOR)) {
            $data['custom'] = array_merge([
                    '.logo h2' => [
                            'color' => LOGIN_TITLE_COLOR
                    ]
            ], $data['custom']);
        }
        $this->layout->addDinamicStylesheet($data, "login.css");*/
        
        $data['custom'] = [
            '.background_img' => [
                //'background-image' => "linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.4)), url(" . base_url_template("template/ingegno/images/background_ingegno.png") . ")!important"
                'background-image' => "linear-gradient(rgba(23, 23, 23, 0.3), rgba(18, 20, 23, 0.8)), url(" . ((!empty($season)) ? base_url("images/{$season}.jpg") : '') . ")!important"
            ]
        ];
        if (defined('LOGIN_COLOR') && !empty(LOGIN_COLOR)) {
            $data['custom'] = array_merge([
                '.login-page, .register-page' => [
                    'background' => LOGIN_COLOR
                ]
            ], $data['custom']);
        }
        if (defined('LOGIN_TITLE_COLOR') && !empty(LOGIN_TITLE_COLOR)) {
            $data['custom'] = array_merge([
                '.logo h2' => [
                    'color' => LOGIN_TITLE_COLOR
                ]
            ], $data['custom']);
        }
        
        $this->layout->addDinamicStylesheet($data, "login.css");
        ?>

    <style>
    /* Media query per login box width responsive */
    @media (max-width: 768px) {

        .login-box-security {
            width: 90% !important;
            margin-top: 20px;
        }
    }

    .login-box-security {
        width: 550px;
    }

    .login_container {
        /*min-width: 450px;*/
        background: #ffffff;
        padding: 20px 30px;
        border-radius: 3px;
    }


    .login_logo {
        width: 100%;
        height: 45px;
        display: flex;
        justify-content: center;
    }

    .login_logo i {
        color: #3c8dbc;
        font-size: 36px;
    }

    .login_content .login_heading {
        font-weight: 600;
        font-size: 2.2rem;
        color: #000000;
    }

    .login_content .login_text {
        font-size: 1.5rem;
        color: #000000;
    }

    .login_actions {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        margin-top: 30px
    }

    .login_actions .main_actions {
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 20px
    }

    @media (max-width: 768px) {
        .login_actions .main_actions {
            width: 100%;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 20px;
            flex-direction: column;
        }

        .login_actions .main_actions input {
            width: 100% !important;
            margin-bottom: 15px;
        }
    }

    .login_actions .main_actions input {
        width: 45%;
    }

    .login_actions .main_actions .js_easylogin_ask {
        border: 0;
        background: #3c8dbc;
        color: #ffffff;
        font-size: 1.5rem;
        font-weight: 600;
        padding: 10px 15px;
        transition: all 0.25s ease-in;
    }

    .login_actions .main_actions .js_easylogin_ask:hover {
        background: #367fa9;
    }

    .login_actions .main_actions .js_easylogin_later {
        background: #ffffff;
        border: 1px solid #3c8dbc;
        color: #3c8dbc;
        font-size: 1.5rem;
        font-weight: 600;
        padding: 10px 15px;
        transition: all 0.25s ease-in;
    }

    .login_actions .main_actions .js_easylogin_later:hover {
        background: #3c8dbc;
        color: #ffffff;
    }

    .login_actions .last_action .js_easylogin_back {
        background: #ffffff;
        border: 0;
        color: #3c8dbc;
        font-weight: 600;
        padding: 5px 10px;
        transition: all 0.25s ease-in;
    }

    .login_actions .last_action .js_easylogin_back:hover {
        color: #367fa9;
    }

    .js_show_password {
        pointer-events: initial;
        cursor: pointer;
    }

    .return_login {
        margin-top: 24px;
    }

    .recovery-password-title {
        color: #111111;
        margin-bottom: 24px;
    }
    </style>
</head>

<body class="hold-transition login-page">
    <div class="background_img">

        <div class="container_login">

            <div class="company_info">
                <div class="logo_ingegno">
                    <img src="<?php echo $this->layout->templateAssets('ingegno', 'images/logo_ingegno_white.png'); ?>" alt="">
                </div>
                <div class="info">
                    <span class="copyright">INGEGNO Suite &copy; - Based on <a href="https://openbuilder.net" target="_blank" class="footer_link_login">OpenBuilder</a>.</span> Tutti i diritti riservati. Sviluppato da <a href="https://h2web.it" target="_blank" class="footer_link_login">H2 S.r.l.</a>
                </div>
            </div>

            <div class="login_column">
                <div class="logo_container">
                    <div class="logo">
                        <?php if ($this->settings === array()) : ?>
                        <h2 class="login-logo"><?php e('La tua azienda'); ?></h2>
                        <?php elseif ($this->settings['settings_company_logo']) : ?>
                        <img src="<?php echo base_url_uploads("uploads/{$this->settings['settings_company_logo']}"); ?>" alt="logo" class="logo" />
                        <?php else : ?>
                        <h2 class=" text-danger"><?php echo $this->settings['settings_company_short_name']; ?></h2>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="login_form_container">
                    <?php if (!empty($sent)) : ?>
                    <h3 class="recovery-password-title text-center"><?php e("E-mail sent successfully"); ?></h3>
                    <div class="alert alert-success">
                        <p class="login-p"><?php e("We've sent you and email to"); ?> <strong><?php echo $receiver; ?></strong> <?php e("which contains the link for reset the password."); ?> </p>
                    </div>
                    <?php elseif (!empty($pwd_resetted)) : ?>
                    <h3 class="recovery-password-title text-center"><?php e("Password resetted successfully"); ?></h3>
                    <div class="alert alert-success">
                        <p class="login-p"><?php e("Your new password has been sent to"); ?> <strong><?php echo $receiver; ?></strong></p>
                    </div>
                    <?php else : ?>
                    <form id="login" class="formAjax" action="<?php echo base_url('access/reset_password_request'); ?>" method="post" novalidate="novalidate">
                        <?php add_csrf(); ?>

                        <div class="input_container">
                            <input class="form-control" type="text" autocomplete="off" placeholder="Email" name="email">
                            <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                        </div>

                        <div class="form-group">
                            <div class="controls">
                                <div id="msg_login" class="alert alert-danger hide"></div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <div class="row">
                                <div class="col-xs-12">
                                    <button type="submit" class="btn btn-block login_btn"><?php e('Reset Password'); ?></button>
                                </div>
                            </div>
                        </div>

                        <p class="text-center return_login"><?php echo anchor(base_url('access/login'), '<b>'.t('Click here').'</b> '.t('to login'), ['style' => 'color: #000000;']); ?></p>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>



    <!-- COMMON PLUGINS -->
    <script src="<?php echo base_url_template("template/adminlte/bower_components/jquery/dist/jquery.min.js?v={$this->config->item('version')}"); ?>"></script>
    <script src="<?php echo base_url_template("template/adminlte/bower_components/bootstrap/dist/js/bootstrap.min.js?v=" . $this->config->item('version')); ?>"></script>
    <script src="<?php echo base_url_template("template/adminlte/plugins/iCheck/icheck.min.js?v={$this->config->item('version')}"); ?>"></script>
    <!-- CUSTOM COMPONENTS -->
    <script type="text/javascript" src="<?php echo base_url_scripts("script/js/submitajax.js?v={$this->config->item('version')}"); ?>"></script>
    <!-- Bootstrap-select -->
    <script src="<?php echo base_url("script/global/plugins/bootstrap-select/bootstrap-select.min.js?v={$this->config->item('version')}"); ?>"></script>



    <script src="<?php echo base_url("script/js/easylogin.js?v={$this->config->item('version')}"); ?>"></script>

    <script>
    $(function() {
        const password = $('[name="users_users_password"]');
        const handleShowPassword = $('.js_show_password');

        handleShowPassword.on("click", function() {
            if (password.attr('type') === 'password') {
                password.attr('type', 'text');
                $(this).removeClass('glyphicon-eye-open');
                $(this).addClass('glyphicon-eye-close');
            } else {
                password.attr('type', 'password');
                $(this).removeClass('glyphicon-eye-close');
                $(this).addClass('glyphicon-eye-open');
            }
        });
    })
    </script>
</body>

</html>