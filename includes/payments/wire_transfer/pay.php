<?php
header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("Expires: 0");
$mysqli = db_connect();
$currency = $config['currency_code'];

if (isset($_SESSION['quickad'][$access_token]['payment_type'])) {
    if(!checkloggedin()){
        header("Location: ".$link['LOGIN']);
        exit();
    }else{
        /* Uploading payment proof */
        $payment_proof_file = null;
        if(get_option('wire_transfer_payment_proof', 1)) {
            if (!empty($_FILES['payment_proof'])) {
                $file = $_FILES['payment_proof'];
                // Valid formats
                $valid_formats = array("jpeg", "jpg", "png");

                $filename = $file['name'];
                $ext = getExtension($filename);
                $ext = strtolower($ext);
                if (!empty($filename)) {
                    //File extension check
                    if (in_array($ext, $valid_formats)) {
                        $main_path = ROOTPATH . "/storage/payment_proofs/";
                        $result = quick_file_upload('payment_proof', $main_path);

                        if ($result['success']) {
                            $payment_proof_file = $result['file_name'];
                            resizeImage(500, $main_path . $payment_proof_file, $main_path . $payment_proof_file);
                        } else {
                            error(__("Error: Please try again."), __LINE__, __FILE__, 1);
                            exit();
                        }

                    } else {
                        error(__("Only jpg, jpeg & png allowed."), __LINE__, __FILE__, 1);
                        exit();
                    }
                }
            } else {
                error(__("Payment proof is required."), __LINE__, __FILE__, 1);
                exit();
            }
        }

        $title = $_SESSION['quickad'][$access_token]['name'];
        $amount = $_SESSION['quickad'][$access_token]['amount'];
        $base_amount = isset($_SESSION['quickad'][$access_token]['base_amount'])? $_SESSION['quickad'][$access_token]['base_amount'] : $amount;
        $folder = $_SESSION['quickad'][$access_token]['folder'];
        $payment_type = $_SESSION['quickad'][$access_token]['payment_type'];
        $user_id = $_SESSION['user']['id'];

        $billing = array(
            'type' => get_user_option($_SESSION['user']['id'],'billing_details_type'),
            'tax_id' => get_user_option($_SESSION['user']['id'],'billing_tax_id'),
            'name' => get_user_option($_SESSION['user']['id'],'billing_name', $_SESSION['user']['username']),
            'address' => get_user_option($_SESSION['user']['id'],'billing_address'),
            'city' => get_user_option($_SESSION['user']['id'],'billing_city'),
            'state' => get_user_option($_SESSION['user']['id'],'billing_state'),
            'zipcode' => get_user_option($_SESSION['user']['id'],'billing_zipcode'),
            'country' => get_user_option($_SESSION['user']['id'],'billing_country')
        );

        $taxes_ids = isset($_SESSION['quickad'][$access_token]['taxes_ids'])? $_SESSION['quickad'][$access_token]['taxes_ids'] : null;

        if($payment_type == "subscr") {
            $trans_desc = $title;
            $subcription_id = $_SESSION['quickad'][$access_token]['sub_id'];
            $plan_interval = $_SESSION['quickad'][$access_token]['plan_interval'];

            $query = "INSERT INTO " . $config['db']['pre'] . "transaction set
                product_name = '".mysqli_real_escape_string($mysqli, validate_input($title))."',
                product_id = '$subcription_id',
                seller_id = '" . $_SESSION['user']['id'] . "',
                status = 'pending',
                amount = '$amount',
                base_amount = '$base_amount',
                transaction_gatway = '".validate_input($folder)."',
                transaction_ip = '" . encode_ip($_SERVER, $_ENV) . "',
                transaction_time = '" . time() . "',
                transaction_description = '".mysqli_real_escape_string($mysqli, validate_input($trans_desc))."',
                transaction_method = 'Subscription',
                frequency = '$plan_interval',
                billing = '".mysqli_real_escape_string($mysqli, json_encode($billing, JSON_UNESCAPED_UNICODE))."',
                taxes_ids = '$taxes_ids',
                payment_proof = '$payment_proof_file'
                ";
        }elseif($payment_type == "prepaid_plan") {
            $trans_desc = $title;
            $subcription_id = $_SESSION['quickad'][$access_token]['sub_id'];

            $query = "INSERT INTO " . $config['db']['pre'] . "transaction set
                product_name = '".mysqli_real_escape_string($mysqli, validate_input($title))."',
                product_id = '$subcription_id',
                seller_id = '" . $_SESSION['user']['id'] . "',
                status = 'pending',
                amount = '$amount',
                base_amount = '$base_amount',
                transaction_gatway = '".validate_input($folder)."',
                transaction_ip = '" . encode_ip($_SERVER, $_ENV) . "',
                transaction_time = '" . time() . "',
                transaction_description = '".mysqli_real_escape_string($mysqli, validate_input($trans_desc))."',
                transaction_method = 'prepaid_plan',
                billing = '".mysqli_real_escape_string($mysqli, json_encode($billing, JSON_UNESCAPED_UNICODE))."',
                taxes_ids = '$taxes_ids',
                payment_proof = '$payment_proof_file'
                ";
        } elseif($payment_type == "banner-advertise"){
            $item_pro_id = $_SESSION['quickad'][$access_token]['product_id'];
            $trans_desc = $_SESSION['quickad'][$access_token]['trans_desc'];

            $query = "INSERT INTO " . $config['db']['pre'] . "transaction set
                    product_name = '".mysqli_real_escape_string($mysqli, validate_input($title))."',
                    product_id = '$item_pro_id',
                    seller_id = '" . $user_id . "',
                    status = 'pending',
                    amount = '$amount',
                    base_amount = '$base_amount',
                    transaction_gatway = '".validate_input($folder)."',
                    transaction_ip = '" . encode_ip($_SERVER, $_ENV) . "',
                    transaction_time = '" . time() . "',
                    transaction_description = '".mysqli_real_escape_string($mysqli, validate_input($trans_desc))."',
                    transaction_method = 'banner-advertise',
                    billing = '".mysqli_real_escape_string($mysqli, json_encode($billing, JSON_UNESCAPED_UNICODE))."',
                    taxes_ids = '$taxes_ids'
                payment_proof = '$payment_proof_file'";
        }
        else{
            $item_pro_id = $_SESSION['quickad'][$access_token]['product_id'];
            $item_featured = $_SESSION['quickad'][$access_token]['featured'];
            $item_urgent = $_SESSION['quickad'][$access_token]['urgent'];
            $item_highlight = $_SESSION['quickad'][$access_token]['highlight'];
            $trans_desc = $_SESSION['quickad'][$access_token]['trans_desc'];

            $query = "INSERT INTO " . $config['db']['pre'] . "transaction set
                    product_name = '".mysqli_real_escape_string($mysqli, validate_input($title))."',
                    product_id = '$item_pro_id',
                    seller_id = '" . $user_id . "',
                    status = 'pending',
                    amount = '$amount',
                    base_amount = '$base_amount',
                    featured = '$item_featured',
                    urgent = '$item_urgent',
                    highlight = '$item_highlight',
                    transaction_gatway = '".validate_input($folder)."',
                    transaction_ip = '" . encode_ip($_SERVER, $_ENV) . "',
                    transaction_time = '" . time() . "',
                    transaction_description = '".mysqli_real_escape_string($mysqli, validate_input($trans_desc))."',
                    transaction_method = 'Premium Ad',
                    billing = '".mysqli_real_escape_string($mysqli, json_encode($billing, JSON_UNESCAPED_UNICODE))."',
                    taxes_ids = '$taxes_ids'
                payment_proof = '$payment_proof_file'";
        }


        $mysqli->query($query) OR error(mysqli_error($mysqli));

        $transaction_id = $mysqli->insert_id;

        // assign posted variables to local variables
        $bank_information = nl2br(get_option('company_bank_info'));
        $item_name = $trans_desc;
        unset($_SESSION['quickad'][$access_token]);

        HtmlTemplate::display('includes/payments/wire_transfer/pay-template', array(
            'bank_info' => $bank_information,
            'transaction_id' => $transaction_id,
            'amount' => price_format($amount),
            'order_title' => $item_name,
            'username' => $_SESSION['user']['username']
        ), true, true);
    }
}else{
    exit('Invalid Process');
}