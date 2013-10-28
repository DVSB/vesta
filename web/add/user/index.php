<?php
// Init
ob_start();
session_start();
$TAB = 'USER';
include($_SERVER['DOCUMENT_ROOT']."/inc/main.php");

if (empty($_SESSION['user'])) {
    header("Location: /login/");
}

// Header
include($_SERVER['DOCUMENT_ROOT'].'/templates/header.html');

// Panel
top_panel($user,$TAB);

// Are you admin?
if ($_SESSION['user'] == 'admin') {
    if (!empty($_POST['ok'])) {
        // Check input
        if (empty($_POST['v_username'])) $errors[] = __('user');
        if (empty($_POST['v_password'])) $errors[] = __('password');
        if (empty($_POST['v_package'])) $errrors[] = __('package');
        if (empty($_POST['v_email'])) $errors[] = __('email');
        if (empty($_POST['v_fname'])) $errors[] = __('first name');
        if (empty($_POST['v_lname'])) $errors[] = __('last name');

        // Protect input
        $v_username = escapeshellarg($_POST['v_username']);
        $v_password = escapeshellarg($_POST['v_password']);
        $v_email = escapeshellarg($_POST['v_email']);
        $v_package = escapeshellarg($_POST['v_package']);
        $v_language = escapeshellarg($_POST['v_language']);
        $v_fname = escapeshellarg($_POST['v_fname']);
        $v_lname = escapeshellarg($_POST['v_lname']);
        $v_notify = $_POST['v_notify'];

        // Validate email
        if (!filter_var($_POST['v_email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error_msg'] = __('Please enter valid email address.');
        }

        // Check for errors
        if (!empty($errors[0])) {
            foreach ($errors as $i => $error) {
                if ( $i == 0 ) {
                    $error_msg = $error;
                } else {
                    $error_msg = $error_msg.", ".$error;
                }
            }
            $_SESSION['error_msg'] = __('Field "%s" can not be blank.',$error_msg);
        }

        // Check password length
        if (empty($_SESSION['error_msg'])) {
            $pw_len = strlen($_POST['v_password']);
            if ($pw_len < 6 ) $_SESSION['error_msg'] = __('Password is too short.',$error_msg);
        }

        if (empty($_SESSION['error_msg'])) {
            exec (VESTA_CMD."v-add-user ".$v_username." ".$v_password." ".$v_email." ".$v_package." ".$v_fname." ".$v_lname, $output, $return_var);
            check_return_code($return_var,$output);
            unset($output);
            if (empty($_SESSION['error_msg'])) {
                exec (VESTA_CMD."v-change-user-language ".$v_username." ".$v_language, $output, $return_var);
                if (!empty($v_notify)) {
                    $to = $_POST['v_notify'];
                    $subject = _translate($_POST['v_language'],"Welcome to Vesta Control Panel");
                    $hostname = exec('hostname');
                    $from = _translate($_POST['v_language'],'MAIL_FROM',$hostname);
                    if (!empty($_POST['v_fname'])) {
                        $mailtext = _translate($_POST['v_language'],'GREETINGS_GORDON_FREEMAN',$_POST['v_fname'],$_POST['v_lname']);
                    } else {
                        $mailtext = _translate($_POST['v_language'],'GREETINGS');
                    }
                    $mailtext .= _translate($_POST['v_language'],'ACCOUNT_READY',$_SERVER['HTTP_HOST'],$_POST['v_username'],$_POST['v_password']);
                    send_email($to, $subject, $mailtext, $from);
                }

                $_SESSION['ok_msg'] = __('USER_CREATED_OK',$_POST[v_username],$_POST[v_username]);
                $_SESSION['ok_msg'] .= " / <a href=/login/?loginas=".$_POST[v_username].">" . __('login as') ." ".$_POST[v_username]. "</a>";
                unset($v_username);
                unset($v_password);
                unset($v_email);
                unset($v_fname);
                unset($v_lname);
                unset($v_notify);
            }
            unset($output);
        }
    }

    exec (VESTA_CMD."v-list-user-packages json", $output, $return_var);
    check_error($return_var);
    $data = json_decode(implode('', $output), true);
    unset($output);

    exec (VESTA_CMD."v-list-sys-languages json", $output, $return_var);
    $languages = json_decode(implode('', $output), true);
    unset($output);

    include($_SERVER['DOCUMENT_ROOT'].'/templates/admin/add_user.html');
    unset($_SESSION['error_msg']);
    unset($_SESSION['ok_msg']);
}

// Footer
include($_SERVER['DOCUMENT_ROOT'].'/templates/footer.html');
