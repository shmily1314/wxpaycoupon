<?php

/**
 * 所有接口的基类
 */
class Common_util_pub
{
    public static $appId;
    public static $mchId;
    public static $subAppId;
    public static $subMchId;
    public static $paykey;
    public static $unionPayGateway;
    public static $unionServicePayGateway;
    public static $queryOrderGateway;
    public static $closeOrderGateway;

    public static $curlTimeout;
    public static $SSLCERT_PATH;
    public static $SSLKEY_PATH;
    public static $microPayGateway;
    public static $reverseGateway;

    function __construct()
    {
    }

    /**
     * 设置商户支付配置
     * @param array $config 支付配置数组
     */
    public static function setConfig(array $config)
    {
        self::$appId    = $config["paymentAccount"]["appid"];
        self::$mchId    = $config["paymentAccount"]["mch_id"];
        self::$subAppId = $config['paymentAccount']['sub_appid'];  //特约支付商户新增
        self::$subMchId = $config["paymentAccount"]["sub_mch_id"];
        self::$paykey   = $config["paymentAccount"]["key"];

        self::$curlTimeout  = 60;
        self::$SSLCERT_PATH = $config["paymentAccount"]['SSLCERT_PATH'];
        self::$SSLKEY_PATH  = $config["paymentAccount"]['SSLKEY_PATH'];

        //沙箱环境请求地址
        self::$unionPayGateway   = 'https://api.mch.weixin.qq.com/sandboxnew/pay/unifiedorder';
        self::$queryOrderGateway = 'https://api.mch.weixin.qq.com/sandboxnew/pay/orderquery';
        self::$closeOrderGateway = 'https://api.mch.weixin.qq.com/sandboxnew/pay/closeorder';
        self::$microPayGateway   = 'https://api.mch.weixin.qq.com/sandboxnew/pay/micropay';
        self::$reverseGateway    = 'https://api.mch.weixin.qq.com/sandboxnew/secapi/pay/reverse';
    }

    function trimString($value)
    {
        $ret = null;
        if (null != $value) {
            $ret = $value;
            if (strlen($ret) == 0) {
                $ret = null;
            }
        }
        return $ret;
    }

    /**
     *    作用：产生随机字符串，不长于32位
     */
    public static function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str   = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     *    作用：格式化参数，签名过程需要使用
     */
    function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = "";
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    /**
     *    作用：格式化参数，签名过程需要使用，参数为空的不参与签名
     */
    function formatBizQueryParaMapForSign($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($v) {
                if ($urlencode) {
                    $v = urlencode($v);
                }
                //$buff .= strtolower($k) . "=" . $v . "&";
                $buff .= $k . "=" . $v . "&";
            }

        }
        $reqPar = "";
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    /**
     *    作用：生成签名
     */
    public function getSign($Obj)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }

        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        //签名步骤二：组装
        $String = $this->formatBizQueryParaMap($Parameters, false);
        $String = $String . "&key=" . self::$paykey;
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        return $result_;
    }

    /**
     *    作用：array转xml
     */
    function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";

            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     *    作用：将xml转为array
     */
    public function xmlToArray($xml)
    {
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    /**
     *    作用：以post方式提交xml到对应的接口url
     */
    public function postXmlCurl($xml, $url, $second = 30)
    {
        try {
            //初始化curl
            $ch = curl_init();
            //设置超时
            curl_setopt($ch, CURLOPT_TIMEOUT, $second);
            //这里设置代理，如果有的话
            //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
            //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            //设置header
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            //要求结果为字符串且输出到屏幕上
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            //post提交方式
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            //运行curl
            $data     = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $info     = curl_getinfo($ch);
            //返回结果
            if ($data) {
                curl_close($ch);
                return $data;
            } else {
                $error  = curl_errno($ch);
                $error1 = curl_error($ch);
                curl_close($ch);
                var_dump('curl出错:---' . $error . '---' . $error1);
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
    }

    /**
     *    作用：使用证书，以post方式提交xml到对应的接口url
     */
    function postXmlSSLCurl($xml, $url, $second = 30)
    {
        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //设置证书
        //使用证书：cert 与 key 分别属于两个.pem文件
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, self::$SSLCERT_PATH);
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, self::$SSLKEY_PATH);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            var_dump('curl出错:---' . $error);
        }
    }

    /**
     *    作用：打印数组
     */
    function printErr($wording = '', $err = '')
    {
        print_r('<pre>');
        echo $wording . "</br>";
        var_dump($err);
        print_r('</pre>');
    }
}

/**
 * 请求型接口的基类
 */
class Wxpay_client_pub extends Common_util_pub
{
    var $parameters;//请求参数，类型为关联数组
    public $response;//微信返回的响应
    public $result;//返回参数，类型为关联数组
    var $url;//接口链接
    var $curl_timeout;//curl超时时间

    /**
     *    作用：设置请求参数
     */
    function setParameter($parameter, $parameterValue)
    {
        if ($parameter && isset($parameterValue) && $parameterValue !== null && $parameterValue != '') {
            $this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
        }
    }

    /**
     *    作用：设置标配的请求参数，生成签名，生成接口参数xml
     */
    function createXml()
    {
        $this->parameters["appid"]  = self::$appId;//公众账号ID
        $this->parameters["mch_id"] = self::$mchId;//商户号
        if (self::$subMchId) {
            $this->parameters['sub_mch_id'] = self::$subMchId;
        }
        $this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串
        $this->parameters["sign"]      = $this->getSign($this->parameters);//签名
        return $this->arrayToXml($this->parameters);
    }

    /**
     *    作用：不使用证书,post请求xml
     */
    function postXml()
    {
        $xml            = $this->createXml();
        $this->response = $this->postXmlCurl($xml, $this->url, $this->curl_timeout);
        return $this->response;
    }

    /**
     *    作用：使用证书,post请求xml
     */
    function postXmlSSL()
    {
        $xml            = $this->createXml();
        $this->response = $this->postXmlSSLCurl($xml, $this->url, $this->curl_timeout);
        return $this->response;
    }

    /**
     *    作用：获取结果，默认不使用证书
     */
    function getResult()
    {
        $this->postXml();
        $this->result = $this->xmlToArray($this->response);
        return $this->result;
    }
}

/**
 * 订单查询接口
 */
class OrderQuery_pub extends Wxpay_client_pub
{
    function __construct()
    {
        //设置接口链接
        $this->url = self::$queryOrderGateway;
        //设置curl超时时间
        $this->curl_timeout = self::$curlTimeout;
    }

    /**
     * 生成接口参数xml
     */
    function createXml()
    {
        //检测必填参数
        if ($this->parameters["out_trade_no"] == null &&
            $this->parameters["transaction_id"] == null
        ) {
            throw new Exception("订单查询接口中，out_trade_no、transaction_id至少填一个！");
        }
        if (self::$subMchId) {
            $this->parameters['sub_mch_id'] = self::$subMchId;
        }
        $this->parameters["appid"]     = self::$appId; //公众账号ID
        $this->parameters["mch_id"]    = self::$mchId; //商户号
        $this->parameters["nonce_str"] = $this->createNoncestr(); //随机字符串
        $this->parameters["sign"]      = $this->getSign($this->parameters); //签名
        return $this->arrayToXml($this->parameters);
    }
}

/**
 * 退款申请接口
 */
class Refund_pub extends Wxpay_client_pub
{

    function __construct()
    {
        //设置接口链接
        $this->url = "https://api.mch.weixin.qq.com/sandboxnew/pay/refund";
        //设置curl超时时间
        $this->curl_timeout = self::$curlTimeout;
    }

    /**
     * 生成接口参数xml
     */
    function createXml()
    {
        try {
            //检测必填参数
            if ($this->parameters["out_trade_no"] == null && $this->parameters["transaction_id"] == null) {
                throw new Exception("退款申请接口中，out_trade_no、transaction_id至少填一个！");
            } elseif ($this->parameters["out_refund_no"] == null) {
                throw new Exception("退款申请接口中，缺少必填参数out_refund_no！");
            } elseif ($this->parameters["total_fee"] == null) {
                throw new Exception("退款申请接口中，缺少必填参数total_fee！");
            } elseif ($this->parameters["refund_fee"] == null) {
                throw new Exception("退款申请接口中，缺少必填参数refund_fee！");
            }
            $this->parameters["appid"]  = self::$appId;//公众账号ID
            $this->parameters["mch_id"] = self::$mchId;//商户号
            if (self::$subMchId) {
//                $this->parameters['sub_appid']  = self::$subAppId;
                $this->parameters['sub_mch_id'] = self::$subMchId;
            }
            $this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串
            $this->parameters["sign"]      = $this->getSign($this->parameters);//签名
            return $this->arrayToXml($this->parameters);
        } catch (Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
    }

    /**
     *    作用：获取结果，使用证书通信
     */
    function getResult()
    {
        $this->postXmlSSL();
        $this->result = $this->xmlToArray($this->response);
        return $this->result;
    }

}


/**
 * 退款查询接口
 */
class RefundQuery_pub extends Wxpay_client_pub
{

    function __construct()
    {
        //设置接口链接
        $this->url = "https://api.mch.weixin.qq.com/sandboxnew/pay/refundquery";
        //设置curl超时时间
        $this->curl_timeout = self::$curlTimeout;
    }

    /**
     * 生成接口参数xml
     */
    function createXml()
    {
        try {
            if ($this->parameters["out_refund_no"] == null &&
                $this->parameters["out_trade_no"] == null &&
                $this->parameters["transaction_id"] == null &&
                $this->parameters["refund_id "] == null) {
                throw new Exception("退款查询接口中，out_refund_no、out_trade_no、transaction_id、refund_id四个参数必填一个！");
            }
            $this->parameters["appid"]  = self::$appId;//公众账号ID
            $this->parameters["mch_id"] = self::$mchId;//商户号
            if (self::$subMchId) {
//                $this->parameters['sub_appid']  = self::$subAppId;
                $this->parameters['sub_mch_id'] = self::$subMchId;
            }
            $this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串
            $this->parameters["sign"]      = $this->getSign($this->parameters);//签名
            return $this->arrayToXml($this->parameters);
        } catch (Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
    }

    /**
     *    作用：获取结果，使用证书通信
     */
    function getResult()
    {
        $this->postXml();
        $this->result = $this->xmlToArray($this->response);
        return $this->result;
    }

}

/**
 * 对账单接口
 */
class DownloadBill_pub extends Wxpay_client_pub
{

    function __construct()
    {

        //设置接口链接
        $this->url = "https://api.mch.weixin.qq.com/sandboxnew/pay/downloadbill";
        //设置curl超时时间
        $this->curl_timeout = self::$curlTimeout;
    }

    /**
     * 生成接口参数xml
     */
    function createXml()
    {
        try {
            if ($this->parameters["bill_date"] == null) {
                throw new Exception("对账单接口中，缺少必填参数bill_date！");
            }
            $this->parameters["appid"]  = self::$appId;//公众账号ID
            $this->parameters["mch_id"] = self::$mchId;//商户号
            if (self::$subMchId) {  //子商户号,如果是服务商的模式，子商户号必须传递
                $this->parameters["sub_mch_id"] = self::$subMchId;//子商户号
            }
            $this->parameters["nonce_str"] = $this->createNoncestr(10);//随机字符串
            $this->parameters["sign"]      = $this->getSign($this->parameters);//签名
            return $this->arrayToXml($this->parameters);
        } catch (Exception $e) {
            die($e->errorMessage());
        }
    }

    /**
     *    作用：获取结果，默认不使用证书
     */
    function getResult()
    {
        $this->postXml();
        $this->result = $this->xmlToArray($this->response);
        if ($this->result['return_code'] == 'FAIL') {
            return '';
        }
        return $this->response;
    }


}

/**
 * 刷卡支付
 * Class MicroPay
 */
class MicroPay extends Wxpay_client_pub
{
    public $data;//接收到的数据，类型为关联数组

    function __construct()
    {
        //设置接口链接
        $this->url = self:: $microPayGateway;
        //设置curl超时时间
        $this->curl_timeout = self::$curlTimeout;
    }

    /**
     * 将微信的请求xml转换成关联数组，以方便数据处理
     */
    function saveData($microPayData)
    {
        $this->data = $microPayData;
    }

    /**
     *    作用：设置请求参数
     */
    function setParameter($parameter, $parameterValue)
    {
        $this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
    }

    function checkSign()
    {
        $tmpData = $this->data;
        unset($tmpData['sign']);
        $sign = $this->getSign($tmpData);//本地签名
        if ($this->data['sign'] == $sign) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * 刷卡支付
     */
    function micropay()
    {
        $this->postXml();
        $this->result = $this->xmlToArray($this->response);  //调用刷卡支付接口，并返回支付结果
        $out_trade_no = $this->parameters['out_trade_no'];
        if ($this->result["return_code"] == "SUCCESS" && $this->result["result_code"] == "FAIL" && $this->result["err_code"] != "USERPAYING" && $this->result["err_code"] != "SYSTEMERROR") {
            return $this->result;
        }
        $queryTimes = 3; //③、确认支付是否成功
        while ($queryTimes > 0) {
            $succResult  = 0;
            $queryResult = $this->query($out_trade_no, $succResult);
            if ($succResult == 2) {  //继续处理  状态为: USERPAYING,SYSTEMERROR //如果需要等待1s后继续
                sleep(3);
                $queryTimes--;  //循环处理3次
                continue;
            } else if ($succResult == 1) { //查询成功
                return $queryResult;
            } else if ($succResult == 3) {
                sleep(3);
                $queryTimes--;  //循环处理3次
                continue;
            } else { //订单交易失败
                return array('status'       => 1, 'errmsg' => '交易失败,请重新扫码收款.', 'payCode' => '7002',
                             'err_code_des' => '交易失败,请重新扫码收款.', 'err_code' => 'TRADE_FAIL');
            }
        }
        $wxQueryResult = array('status'       => 1, 'errmsg' => '交易异常,请确认用户是否已付款', 'payCode' => '70002',
                               'err_code_des' => '交易异常,请确认用户是否已付款.', 'err_code' => 'TRADE_FAIL');
        if ($succResult == 2) {
            $wxQueryResult['trade_state'] = 'USERPAYING';
        }
        if ($succResult == 3) {
            $wxQueryResult['trade_state'] = 'PAYERROR';
        }
        return $wxQueryResult;
    }

    /**
     * 查询刷卡支付订单
     * @param $out_trade_no
     * @param $succCode
     * @return bool|mixed
     */
    function query($out_trade_no, &$succCode)
    {
        unset($this->result);
        $orderQuery = new OrderQuery_pub();
        $orderQuery->setParameter('out_trade_no', $out_trade_no);
        $result = $orderQuery->getResult();
        if ($result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS") {
            //支付成功
            if ($result["trade_state"] == "SUCCESS") {
                $succCode = 1;
                return $result;
            } else if ($result["trade_state"] == "USERPAYING") {  //用户支付中
                $succCode = 2;
                return false;
            } else if ($result['trade_state'] == 'PAYERROR') { //支付错误
                $succCode = 3;
                return false;
            }
        }
        //如果返回错误码为“此交易订单号不存在”则直接认定失败
        if ($result["err_code"] == "ORDERNOTEXIST") {
            $succCode = 0;
        } else {
            //如果是系统错误，则后续继续
            $succCode = 2;
        }
        return false;
    }

    /**
     *  收银员使用扫码设备读取微信用户刷卡授权码以后，二维码或条码信息传送至商户收银台，
     *  由商户收银台或者商户后台调用该接口发起支付。
     *  WxPayWxPayMicroPay中body、out_trade_no、total_fee、auth_code参数必填
     *  appid、mchid、spbill_create_ip、nonce_str不需要填入
     * @return array
     * @throws Exception
     */
    function createXml()
    {
        //检测必填参数
        if (!$this->parameters['body']) {
            throw new Exception("提交被扫支付API接口中，缺少必填参数body！");
        } else if (!$this->parameters['out_trade_no']) {
            throw new Exception("提交被扫支付API接口中，缺少必填参数out_trade_no！");
        } else if (!$this->parameters['total_fee']) {
            throw new Exception("提交被扫支付API接口中，缺少必填参数total_fee！");
        } else if (!$this->parameters['auth_code']) {
            throw new Exception("提交被扫支付API接口中，缺少必填参数auth_code！");
        }

        $this->parameters["appid"]            = self::$appId; //公众账号ID
        $this->parameters["mch_id"]           = self::$mchId; //商户号
        $this->parameters["spbill_create_ip"] = '192.168.0.211'; //终端ip
        $this->parameters["nonce_str"]        = $this->createNoncestr(); //随机字符串
        unset($this->parameters["sign"]);
        $this->parameters["sign"] = $this->getSign($this->parameters); //签名
        return $this->arrayToXml($this->parameters);
    }
}

/**
 * 沙箱环境
 * Class SandboxKey
 */
class SandboxKey extends Wxpay_client_pub
{
    function __construct()
    {
        $this->url          = "https://api.mch.weixin.qq.com/sandboxnew/pay/getsignkey";//设置接口链接
        $this->curl_timeout = self::$curlTimeout; //设置curl超时时间
    }

    /**
     *  作用：获取结果,使用不使用证书
     */
    function getResult()
    {
        $this->postXml();
        $this->result = $this->xmlToArray($this->response);
        return $this->result;
    }

    /**
     * 生成接口参数xml
     */
    function createXml()
    {
        $this->parameters["mch_id"]    = self::$mchId;//商户号
        $this->parameters["nonce_str"] = $this->createNoncestr(); //随机字符串
        unset($this->parameters["sign"]);
        $this->parameters["sign"] = $this->getSign($this->parameters); //签名
        return $this->arrayToXml($this->parameters);
    }
}
