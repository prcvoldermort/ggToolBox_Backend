<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Redis;

class BaiduPicRecognizeController extends Controller
{
    // 识别图片的种类
    private $photoTypes = [
        'animal' => ['name' => '动物图片识别', 'desc' => '上传一张动物图片,告诉你是什么动物'], // 动物
        'plant' => ['name' => '植物图片识别', 'desc' => '上传一张植物图片,告诉你是什么植物'],  // 植物
        'logo' => ['name' => 'logo图片识别', 'desc' => '上传一张logo图片,告诉你是什么logo'],   // Logo标志
        'ingredient' => ['name' => '果蔬图片识别', 'desc' => '上传一张果蔬图片,告诉你是什么果蔬'],  // 果蔬
        'dish' => ['name' => '菜品图片识别', 'desc' => '上传一张菜品图片,告诉你是什么菜品'],  // 菜品
        'redwine' => ['name' => '红酒酒标图片识别', 'desc' => '上传一张红酒酒标图片,告诉你红酒的信息'], // 红酒酒标
        'currency' => ['name' => '货币图片识别', 'desc' => '上传一张钱的图片,告诉你是哪国的钱'],  // 货币
        'landmark' => ['name' => '地标图片识别', 'desc' => '上传一张地标建筑的图片,告诉你是什么建筑'],  // 地标
        'ocr_text_basic' => ['name' => '文字识别', 'desc' => '上传一张带文字的图片,识别出图片中的文字'], // 通用文字识别
        'ocr_idcard' => ['name' => '身份证图片识别', 'desc' => '上传一张身份证图片,识别出身份证上的信息'],  // 中国身份证
        'ocr_business_license' => ['name' => '营业执照图片识别', 'desc' => '上传一张营业执照图片,识别出上面的信息'],  // 营业执照
        'ocr_business_card' => ['name' => '名片图片识别', 'desc' => '上传一张名片图片,识别出上面的信息'], // 名片
        'ocr_passport' => ['name' => '护照图片识别', 'desc' => '上传一张护照图片,识别出上面的信息'],  // 护照
        'ocr_hkmacau' => ['name' => '港澳通行证图片识别', 'desc' => '上传一张港澳通行证图片,识别出上面的信息'], // 港澳通行证
        'ocr_taiwan' => ['name' => '台湾通行证图片识别', 'desc' => '上传一张台湾通行证图片,识别出上面的信息'], // 台湾通行证
        'ocr_household' => ['name' => '户口本图片识别', 'desc' => '上传一张户口本图片,识别出上面的信息'], // 中国户口本
        'ocr_birth' => ['name' => '出生证明图片识别', 'desc' => '上传一张出生证明图片,识别出上面的信息'], // 出生证明
        'ocr_vat_invoice' => ['name' => '增值税发票图片识别', 'desc' => '上传一张增值税发票图片,识别出上面的信息'], // 增值税发票
        'ocr_quota_invoice' => ['name' => '定额发票图片识别', 'desc' => '上传一张定额发票图片,识别出上面的信息'], // 定额发票
        'ocr_train_ticket' => ['name' => '火车票图片识别', 'desc' => '上传一张火车票图片,识别出上面的信息'], // 火车票
        'ocr_taxi_receipt' => ['name' => '出租车票图片识别', 'desc' => '上传一张出租车票图片,识别出上面的信息'], // 出租车票
        'ocr_air_ticket' => ['name' => '机票行程单图片识别', 'desc' => '上传一张机票行程单图片,识别出上面的信息'], // 机票行程单
        'ocr_receipt' => ['name' => '超市小票图片识别', 'desc' => '上传一张超市小票图片,识别出上面的信息'], // 小票
        'ocr_vehicle_license' => ['name' => '行驶证图片识别', 'desc' => '上传一张行驶证图片,识别出上面的信息'], // 行驶证
        'ocr_driving_license' => ['name' => '驾驶证图片识别', 'desc' => '上传一张驾驶证图片,识别出上面的信息'], // 驾驶证
        'ocr_handwriting' => ['name' => '手写文字识别', 'desc' => '上传一张手写文字图片，识别出上面的文字'], // 手写文字识别
    ];

    /**
     * 根据传入的识别类型的代码,返回该类型的名称及描述信息
     */
    public function getCategoryNameDesc(Request $request) {
        try {
            $categoryCode = $request->get('category');
            if (isset($categoryCode)) {
                if (array_key_exists($categoryCode, $this->photoTypes)) {
                    return response()->json($this->photoTypes[$categoryCode]);
                } else {
                    throw new \Exception('类型代码错误！');
                }
            } else {
                throw new \Exception('种类代码不能为空！');
            }
        } catch (\Exception $e) {
            return response($e->getMessage(), 500);
        }
    }
    /**
     * 接收客户端识别请求的方法
     */
    public function getRequest(Request $request) {
        try {
            $photoType = $request->get('photo_type');
            // if (!in_array($photoType, $this->photoTypes)) {
            //     throw new \Exception('非法图片类型！');
            // }
            $accessToken = Redis::get('baiduAPIAccessToken');
            if (!isset($accessToken)) {
                // Redis中baiduAPIAccessToken过期，需要请求接口刷新token
                $this->getAccessToken();
                $accessToken = Redis::get('baiduAPIAccessToken');
            }
            // 继续下面的逻辑
            if ($request->file('photo')->isValid()) {
                // 上传的文件没有问题
                $photo = $request->file('photo');
                $photoContents= $photo->get();
                $photoContentsBase64 = \base64_encode($photoContents);
                switch ($photoType) {
                    case 'animal':
                        // 识别动物
                        $photoResult = $this->recognizeAnimal($accessToken, $photoContentsBase64);
                        break;
                    case 'plant':
                        // 识别植物
                        $photoResult = $this->recognizePlant($accessToken, $photoContentsBase64);
                        break;
                    case 'logo':
                        // 识别logo
                        $photoResult = $this->recognizeLogo($accessToken, $photoContentsBase64);
                        break;
                    case 'ingredient':
                        // 识别果蔬
                        $photoResult = $this->recognizeIngredient($accessToken, $photoContentsBase64);
                        break;
                    case 'dish':
                        // 识别菜品
                        $photoResult = $this->recognizeDish($accessToken, $photoContentsBase64);
                        break;
                    case 'redwine':
                        // 识别红酒酒标
                        $photoResult = $this->recognizeRedwine($accessToken, $photoContentsBase64);
                        break;
                    case 'currency':
                        // 识别货币
                        $photoResult = $this->recognizeCurrency($accessToken, $photoContentsBase64);
                        break;
                    case 'landmark':
                        // 识别地标
                        $photoResult = $this->recognizeLandmark($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_text_basic':
                        // 通用文字识别
                        $photoResult = $this->ocrTextBasic($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_idcard':
                        // 身份证识别
                        $photoResult = $this->ocrIdcard($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_business_license':
                        // 营业执照, 非法图片类型
                        $photoResult = $this->ocrBusinessLicense($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_business_card':
                        // 名片，非法图片类型
                        $photoResult = $this->ocrBusinessCard($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_passport':
                        // 中国护照
                        $photoResult = $this->ocrPassport($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_hkmacau':
                        // 港澳通行证
                        $photoResult = $this->ocrHkmacau($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_taiwan':
                        // 台湾通行证
                        $photoResult = $this->ocrTaiwan($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_household':
                        // 中国户口本
                        $photoResult = $this->ocrHousehold($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_birth':
                        // 出生证明
                        $photoResult = $this->ocrBirth($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_vat_invoice':
                        // 增值税发票
                        $photoResult = $this->ocrVatInvoice($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_quota_invoice':
                        // 定额发票
                        $photoResult = $this->ocrQuotaInvoice($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_train_ticket':
                        // 火车票
                        $photoResult = $this->ocrTrainTicket($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_taxi_receipt':
                        // 出租车票
                        $photoResult = $this->ocrTaxiReceipt($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_air_ticket':
                        // 机票行程单
                        $photoResult = $this->ocrAirTicket($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_receipt':
                        // 小票
                        $photoResult = $this->ocrReceipt($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_vehicle_license':
                        // 行驶证
                        $photoResult = $this->ocrVehicleLicense($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_driving_license':
                        // 驾驶证
                        $photoResult = $this->ocrDrivingLicense($accessToken, $photoContentsBase64);
                        break;
                    case 'ocr_handwriting':
                        // 手写文本数字
                        $photoResult = $this->ocrHandwriting($accessToken, $photoContentsBase64);
                        break;
                    default:
                        # code...
                        break;
                }
                return response()->json($photoResult);
            }
        } catch (\Exception $e) {
            return response($e->getMessage(), 500);
        }
    }

    /**
     * 识别动物图片的方法
     */
    private function recognizeAnimal($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/image-classify/v1/animal?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64,
            'baike_num' => 1
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        // 解析结果
        $result = $responseObject['result'][0];
        return $result;
    }

    /**
     * 识别植物图片的方法
     */
    private function recognizePlant($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/image-classify/v1/plant?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64,
            'baike_num' => 1
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        // 解析结果
        $result = $responseObject['result'][0];
        return $result;
    }

    /**
     * 识别logo
     */
    private function recognizeLogo($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/image-classify/v2/logo?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        // 解析结果
        $result = $responseObject['result'][0];
        return $result;
    }

    /**
     * 识别果蔬
     */
    private function recognizeIngredient($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/image-classify/v1/classify/ingredient?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        // 解析结果
        $result = $responseObject['result'][0];
        return $result;
    }

    /**
     * 识别菜品
     */
    private function recognizeDish($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/image-classify/v2/dish?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64,
            'top_num' => 1,
            'filter_threshold' => 0.95,
            'baike_num' => 1
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['result'][0];
        return $result;
    }

    /**
     * 识别红酒酒标
     */
    private function recognizeRedwine($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/image-classify/v1/redwine?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['result'];
        return $result;
    }

    /**
     * 识别货币
     */
    private function recognizeCurrency($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/image-classify/v1/currency?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['result'];
        return $result;
    }

    /**
     * 识别地标
     */
    private function recognizeLandmark($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/image-classify/v1/landmark?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['result'];
        return $result;
    }

    /**
     * 通用文字识别
     */
    private function ocrTextBasic($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/general_basic?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64,
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['words_result'];
        return $result;
    }

    /**
     * 身份证识别
     */
    private function ocrIdcard($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/idcard?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64,
            'id_card_side' => 'front'
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['words_result'];
        return $result;
    }

    /**
     * 营业执照
     */
    private function ocrBusinessLicense($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/business_license?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['words_result'];
        return $result;
    }

    /**
     * 名片识别
     */
    private function ocrBusinessCard($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/business_card?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['words_result'];
        return $result;
    }

    /**
     * 中国护照
     */
    private function ocrPassport($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/passport?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['words_result'];
        return $result;
    }

    /**
     * 港澳通行证
     */
    private function ocrHkmacau($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/HK_Macau_exitentrypermit?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['words_result'];
        return $result;
    }

    /**
     * 台湾通行证
     */
    private function ocrTaiwan($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/taiwan_exitentrypermit?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['words_result'];
        return $result;
    }

    /**
     * 户口本
     */
    private function ocrHousehold($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/household_register?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['words_result'];
        return $result;
    }

    /**
     * 出生证明
     */
    private function ocrBirth($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/birth_certificate?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['words_result'];
        return $result;
    }

    /**
     * 增值税发票
     */
    private function ocrVatInvoice($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/vat_invoice?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['words_result'];
        return $result;
    }

    /**
     * 定额发票
     */
    private function ocrQuotaInvoice($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/quota_invoice?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['words_result'];
        return $result;
    }

    /**
     * 火车篇
     */
    private function ocrTrainTicket($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/train_ticket?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject;
        return $result;
    }

    /**
     * 出租车票
     */
    private function ocrTaxiReceipt($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/taxi_receipt?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['words_result'];
        return $result;
    }

    /**
     * 机票行程单
     */
    private function ocrAirTicket($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/air_ticket?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['words_result'];
        return $result;
    }

    /**
     * 小票
     */
    private function ocrReceipt($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/receipt?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['words_result'];
        return $result;
    }

    /**
     * 行驶证
     */
    private function ocrVehicleLicense($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/vehicle_license?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['words_result'];
        return $result;
    }

    /**
     * 驾驶证
     */
    private function ocrDrivingLicense($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/driving_license?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['words_result'];
        return $result;
    }

    /**
     * 手写文字识别
     */
    private function ocrHandwriting($accessToken, $imageContentsBase64) {
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/handwriting?access_token=' . $accessToken;
        $formParamsArray = [
            'image' => $imageContentsBase64
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $result = $responseObject['words_result'];
        return $result;
    }

    private function getAccessToken() {
        $apiKey = 'RDGSF0KWUY1VRALcpTGHLwTl';
        $secretKey = 'FDagivgU42ONHLR54i777NIMvlsjzk7W';
        $grantType = 'client_credentials';
        $url = 'https://aip.baidubce.com/oauth/2.0/token';
        $formParamsArray = [
            'grant_type' => $grantType,
            'client_id' => $apiKey,
            'client_secret' => $secretKey
        ];
        $responseBody = $this->sendHttpPostRequest($url, $formParamsArray);
        $responseObject = \json_decode($responseBody, true);
        $accessToken = $responseObject['access_token'];
        $expiresTime = $responseObject['expires_in'];
        // 把accessToken存入redis，并设定TTL时效
        Redis::set('baiduAPIAccessToken', $accessToken, 'EX', $expiresTime);
    }

    private function sendHttpPostRequest($url, $formParamsArray) {
        $client = new Client();
        $response = $client->request('POST', $url, [
            'form_params' => $formParamsArray
        ]);
        $code = $response->getStatusCode();
        if ($code == 200) {
            // 请求成功
            $body = $response->getBody();
            $bodyContents = $body->getContents();
            return $bodyContents;
        } else {
            // 请求失败
        }
    }
}
