<?php

return [

    'grameenphone' => [
        'base_url'   => env('GP_DCB_URL', 'https://api.gp.com.bd/dsdp'),
        'app_id'     => env('GP_DCB_APP_ID', ''),
        'password'   => env('GP_DCB_PASSWORD', ''),
        'service_id' => env('GP_DCB_SERVICE_ID', ''),
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
