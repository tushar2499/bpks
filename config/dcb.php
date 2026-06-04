<?php

return [

    'grameenphone' => [
        'base_url'     => 'https://api.dob.telenordigital.com',
        'username'     => 'b2mtech-EE0CFD4K',
        'password'     => '1JKpKKUVulyvAbedyiSfBc',
        'operator_id'  => 'GRA-BD',
        'amount'       => 17.391,
        'merchant'     => 'BPKSLotteryTicket',
        'country_code' => 880,
        'product_id'   => 'BPKSLotteryTicket',
        'product_desc' => 'BPKS Lottery Ticket',
        'category'     => 'mTicketing',
        'sms_sender'   => 'GP DOB',
    ],

    'robi' => [
        'base_url'    => env('ROBI_DCB_URL', 'https://api.robi.com.bd/wap'),
        'sp_id'       => '200011',
        'sp_password' => 'Robi1234',
        'service_id'  => '02000112000001226',
        'consent_url' => 'https://dsdpwap.robi.com.bd/store/wapconfirm',
        'dcb_amount'  => 1981,
    ],

    'banglalink' => [
        'base_url'      => env('BL_DCB_URL', 'https://api.banglalink.net/dcb'),
        'client_id'     => env('BL_DCB_CLIENT_ID', ''),
        'client_secret' => env('BL_DCB_CLIENT_SECRET', ''),
        'service_id'    => env('BL_DCB_SERVICE_ID', ''),
        'prices'        => [
            1 => 20.00,  2 => 40.00,  3 => 60.00,  4 => 80.00,
            5 => 100.00, 6 => 120.00, 7 => 140.00, 8 => 160.00,
            9 => 180.00, 10 => 200.00,
        ],
    ],

    // Robi MIFE SMS API (for sending ticket SMS after successful payment)
    'robi_sms' => [
        'token_url'      => 'https://apigate.robi.com.bd/token',
        'sms_url'        => 'https://apigate.robi.com.bd/smsmessaging/v1',
        'username'       => 'MIFE_B2MTechSDP',
        'password'       => 'B2mtEch0nosDp@^MIfe820',
        'auth_header'    => 'Basic TjNHVEpnMEQ4TUVzczI3Njlmem1vdGN1bktVYTpfQ3Z4em43cF90ZUhPN0cwNW90SXBPaDZLcThh',
        'sender_address' => '25063',
    ],

];
