<?php

namespace AdaPayBundle\Services\Request;

use PaymentBundle\Services\Payments\AdaPaymentService;
use GuzzleHttp\Client as Client;
use Dingo\Api\Exception\ResourceException;

class Request
{
    public function call($params)
    {
        $adaPaymentService = new AdaPaymentService();
        $setting = $adaPaymentService->getPaymentSetting($params['company_id']);
//        $setting['api_key'] = '111';
//        $setting['private_key'] = "MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBANkjZTsAD86McaqOzCm4c2I/3E16IWpmJvKSiOsDzsdi5AAX8BZC8Scz/+aLGkd7ZN5RMkT3sXK100WN94PF6bY01hJDJcA5OwvDPBR+bR/2LJbRcOzAecbkape3PbawuqVRv0ObvP2zew/b17SCjMjB0MVIVEpsqO7B8U3xpnvZAgMBAAECgYAiog+iYnci9amnj5Em0mGs+QyVWvZ7dwGdTRwDiB7yFGbTUP4dPt4h55xYVDWD0z2abA79ixhFeJTTEjq5TNbiyo01HwCUB3BZct+QBE83UZGAt1jRTjQoNWLmQEAncjVrNDL6mojWyTiYsVaZmdQmb1nxF7L0+Cf+rMyNYbHMdQJBAP4DAltvbD7810EUdwOmT5gVPFeWhRw3y1+Yv+x5TArzPnwdG6rrg5zBBGIvjcpkXpZfZWvTdcUOuv0DJ35oAfsCQQDa1n+zEJl7OcAl8ov40uvgMIXT9yQMD91bjOktO+OY/4Ykj+QQkltaBHlK5axOEh8xF0mI5CXpYooN+ufF0mU7AkAO0UAe81X+KqOn4Ti8FsSH251EgrxLFBoh/ngbpEvCS8Q2W0BU7R4lU8EctSdxSf+WiAQTkSdKknxn6/ouzoRnAkEAvELjwOx63WOlRgAPIpRxj4Cu4NcwD6BmUig7QUrQVgMdJ78R+J+wLxTCNAi53sAATX83J6j+ZHT9R2GemrSRmQJAXMlO71ZWgW6n43158WEUQKz6kFycfJMaByGVUpdI1R19Wv4DkYDaB4bGClCAYzGf3dUOMp9ojyCB1kQRtaGPbA==";
//        $pub = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDZI2U7AA/OjHGqjswpuHNiP9xNeiFqZibykojrA87HYuQAF/AWQvEnM//mixpHe2TeUTJE97FytdNFjfeDxem2NNYSQyXAOTsLwzwUfm0f9iyW0XDswHnG5GqXtz22sLqlUb9Dm7z9s3sP29e0gozIwdDFSFRKbKjuwfFN8aZ72QIDAQAB";
        if (!$setting) {
            throw new ResourceException('adapay支付未配置');
        }
        $params['api_key'] = $setting['api_key'];
        unset($params['company_id']);
        $params['sign'] = $this->generateSignature($params, $setting['private_key']);
        $url = config('adapay.agent_url');
        $client = new Client(['timeout' => 5]);
        try {
            app('log')->debug('Adapay Agent : input===>' . var_export($params, 1));
            $respStr = $client->post($url, ['json' => $params])->getBody();
            app('log')->debug('Adapay Agent : result===>' . $respStr);
        } catch (\Exception $e) {
            app('log')->debug('Adapay Agent error : ' . $e->getMessage());
            throw new ResourceException('agent错误 : ' . $e->getMessage());
        }

        //代理商报错处理
        $resData = json_decode($respStr, 1);
        if (!isset($resData['data'])) {
            $resData = [
                'errcode' => $resData['errcode'] ?? 50000,
                'errmsg' => $resData['errmsg'] ?? 'agent错误',
                'data' => [
                    'error_msg' => 'agent错误 : '.$respStr,
                    'status' => 'failed'
                ]
            ];
        }

        return $resData;
    }


    public function generateSignature($params, $privateKey)
    {
        if (is_array($params)) {
            $Parameters = array();
            foreach ($params as $k => $v) {
                $Parameters[$k] = $v;
            }
            $data = json_encode($Parameters);
        } else {
            $data = $params;
        }

        $sign = $this->SHA1withRSA($data, $privateKey);
        return $sign;
    }

    public function SHA1withRSA($data, $privateKey)
    {
        $key = "-----BEGIN PRIVATE KEY-----\n".wordwrap($privateKey, 64, "\n", true)."\n-----END PRIVATE KEY-----";

        try {
            openssl_sign($data, $signature, $key);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return base64_encode($signature);
    }
}
