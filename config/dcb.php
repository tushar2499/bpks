<?php

return [

    'grameenphone' => [
        'base_url'     => env('GP_DOB_URL', 'https://api.dob.telenordigital.com'),
        'username'     => env('GP_DOB_USERNAME', 'b2mtech'),
        'password'     => env('GP_DOB_PASSWORD', ''),
        'operator_id'  => env('GP_DOB_OPERATOR_ID', 'GRA-BD'),
        'amount'       => (float) env('GP_DOB_AMOUNT', 17.391),
        'merchant'     => env('GP_DOB_MERCHANT', 'GRA-BD'),
        'country_code' => (int) env('GP_DOB_COUNTRY_CODE', 880),
        'product_id'   => env('GP_DOB_PRODUCT_ID', 'BPKSLotteryTicket'),
        'product_desc' => env('GP_DOB_PRODUCT_DESC', 'BPKS Lottery Ticket'),
        'category'     => env('GP_DOB_CATEGORY', 'mTicketing'),
        'sms_sender'   => env('GP_DOB_SMS_SENDER', '8801323174104'),
    ],

    'robi' => [
        'base_url'    => env('ROBI_DCB_URL', 'https://api.robi.com.bd/wap'),
        'sp_id'       => env('ROBI_SP_ID', '20011'),
        'sp_password' => env('ROBI_SP_PASSWORD', 'Robi1234'),
        'service_id'  => env('ROBI_SERVICE_ID', '02000192000001220'),
        'consent_url' => env('ROBI_CONSENT_URL', 'https://dsdpwap.robi.com.bd/store/wapconfirm'),
        // Base DCB amount in poisha (excluding VAT/SD/SC — must match Robi's configured value)
        'dcb_amount'  => (int) env('ROBI_DCB_AMOUNT', 1981),
    ],

    'banglalink' => [
        'base_url'      => env('BL_DCB_URL', 'https://api.banglalink.net/dcb'),
        'client_id'     => env('BL_DCB_CLIENT_ID', ''),
        'client_secret' => env('BL_DCB_CLIENT_SECRET', ''),
        'service_id'    => env('BL_DCB_SERVICE_ID', ''),
    ],

    // Robi MIFE SMS API (for sending ticket SMS after successful payment)
    'robi_sms' => [
        'token_url'      => env('ROBI_SMS_TOKEN_URL', 'https://apigate.robi.com.bd/token'),
        'sms_url'        => env('ROBI_SMS_URL', 'https://apigate.robi.com.bd/smsmessaging/v1'),
        'username'       => env('ROBI_SMS_USERNAME', ''),
        'password'       => env('ROBI_SMS_PASSWORD', ''),
        'auth_header'    => env('ROBI_SMS_AUTH_HEADER', ''),
        'sender_address' => env('ROBI_SMS_SENDER', '200011'),
    ],

];
