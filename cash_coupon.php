<?php
error_reporting(0);
header("Content-Type:text/html;charset=UTF-8");
date_default_timezone_set("Asia/Shanghai");
/**
 *
 * 升级微信支付免充代金券
 * 完成以下四步即可完成升级
 * 接口文档描述: https://pay.weixin.qq.com/wiki/doc/api/download/mczyscsyl.pdf
 * 查询升级进度: https://pay.weixin.qq.com/wiki/doc/api/tools/sp_coupon.php?chapter=15_6&index=4
 * 获取沙箱key
 * 调用刷卡接口
 * 退款
 * 对账单下载
 * 注: 免充值券不参与结算
 * Class Cash_Coupon
 */
require_once './WxPayPubHelper.php';

class Cash_Coupon
{
    /**
     * 刷卡支付提交
     */
    public static function getMicroCardPayData(array $params)
    {
        Common_util_pub::setConfig($params);
        $micropay                        = new MicroPay();
        $orderparams["sub_mch_id"]       = $params["paymentAccount"]["sub_mch_id"];
        $orderparams["nonce_str"]        = Common_util_pub::createNoncestr(10);
        $orderparams["body"]             = '免冲代金券升级接口测试' . date('YmdHis');
        $orderparams["out_trade_no"]     = strval($params["tid"]);
        $orderparams["total_fee"]        = strval($params["total_fee"]);
        $orderparams["spbill_create_ip"] = '192.168.0.211';
        $orderparams["auth_code"]        = '134671333324313071';
        foreach ($orderparams as $key => $value) {
            if ($value) { //为空的参数不要参与签名
                $micropay->setParameter($key, $value);
            }
        }
        $payresult = $micropay->micropay();
        return $payresult;
    }


    /**
     * 订单查询接口
     * @param array $params 参数数组
     * @throws YwkPay_Exception
     * @return array $orderQueryResult
     */
    public static function queryOrder(array $params)
    {
        Common_util_pub::setConfig($params);
        $out_trade_no   = $params['tid'];
        $transaction_id = $params['transaction_id'];
        //使用订单查询接口
        $orderQuery = new OrderQuery_pub();
        if ($out_trade_no) {
            $orderQuery->setParameter("out_trade_no", "$out_trade_no");//商户订单号
        }
        if ($transaction_id) {
            $orderQuery->setParameter("transaction_id", "$transaction_id");//微信订单号
        }
        if ($params["config"]["paymentAccount"]["sub_appid"] && $params["config"]["paymentAccount"]["sub_mch_id"]) {
            //服务商信息
            $orderQuery->setParameter("sub_mch_id", $params["config"]["paymentAccount"]["sub_mch_id"]);//子商户号
            $orderQuery->setParameter("sub_appid", $params["config"]["paymentAccount"]["sub_appid"]);//子商户appid
        }
        $orderQueryResult = $orderQuery->getResult();
        if ($orderQueryResult["return_code"] == "FAIL") {
            throw new YwkPay_Exception("通信出错：" . $orderQueryResult['return_msg'], 9001);
        } elseif ($orderQueryResult["result_code"] == "FAIL") {
            throw new YwkPay_Exception("错误代码：" . $orderQueryResult['err_code'] . ",错误代码描述："
                . $orderQueryResult['err_code_des'], 9001);
        } else {
            return $orderQueryResult;
        }
    }
    //申请退款
    public static function refundOrder(array $params)
    {
        //设置商户支付配置
        Common_util_pub::setConfig($params);
        $out_trade_no   = $params['tid'];
        $out_refund_no  = $params['out_refund_no'];//string
        $total_fee      = (int)$params['total_fee'];
        $refund_fee     = (int)$params['refund_fee'];
        $mchid          = $params['paymentAccount']['mch_id'];
        $transaction_id = $params['transaction_id'];
        //使用退款接口
        $refund = new Refund_pub();
        $refund->setParameter("out_trade_no", "$out_trade_no");//商户订单号
        $refund->setParameter("out_refund_no", "$out_refund_no");//商户退款单号
        $refund->setParameter("total_fee", "$total_fee");//总金额
        $refund->setParameter("refund_fee", "$refund_fee");//退款金额
        if ($params['paymentAccount']['sub_appid'] && $params['paymentAccount']["sub_mch_id"]) {
            $refund->setParameter("transaction_id", "$transaction_id");//服务商模式，需要微信订单号
        }
        $refundResult = $refund->getResult();
        return $refundResult;
    }
    //查询退款
    public static function queryRefundOrder(array $params)
    {
        Common_util_pub::setConfig($params);
        $out_trade_no  = $params['tid'];
        $out_refund_no = $params['out_refund_no'];//string
        $refund_query  = new RefundQuery_pub();
        $refund_query->setParameter("out_trade_no", "$out_trade_no");//商户订单号
        $refund_query->setParameter("out_refund_no", "$out_refund_no");//商户退款单号
        $refundResult = $refund_query->getResult();
        return $refundResult;
    }

    /**
     * 1、下载‘付款账单’，bill_type 固定传“PAYMENT”
     */
    public static function downloadbill($params)
    {
        if (!is_array($params)) {
            return array('err_code_des' => '参数必须是数组', 'return_code' => 'Fail');
        } else {
            Common_util_pub::setConfig($params);
            $bill_date = $params['bill_date']; //下载付款账单的日期，格式：20140603
            //单总单时 bill_type=ALL
            //交易成功单时 bill_type=SUCCESS，
            //账单退款单时 bill_type=REFUND
//            $bill_type  = 'PAYMENT'; //付款账单固定传“PAYMENT”
            $bill_type        = $params['bill_type']; //付款账单固定传“PAYMENT”
            $DownloadBill_pub = new DownloadBill_pub();
            $DownloadBill_pub->setParameter("bill_date", strval($bill_date));
            $DownloadBill_pub->setParameter("bill_type", $bill_type);
            $DownloadBillResult = $DownloadBill_pub->getResult();
            return $DownloadBillResult;
        }
    }

    /**
     * 获取沙箱环境的密钥
     * getSignKey
     */
    public static function getSignKey($params)
    {
        if (!is_array($params)) {
            return array('err_code_des' => '参数必须是数组', 'return_code' => 'Fail');
        } else {
            Common_util_pub::setConfig($params);
            $sandboxKey = new SandboxKey();
            $keyInfo    = $sandboxKey->getResult();
            return $keyInfo;
        }
    }

    public static function newLine()
    {
        echo PHP_EOL . PHP_EOL;
    }
}

//普通商户升级---开始获取沙箱key
//为普通商户的微信支付秘钥
$params["paymentAccount"]['key'] = ''; //正式秘钥获取沙箱秘钥，用于调用后续沙箱环境签名时的key获取
$params["paymentAccount"]['mch_id']     = ''; //商户号(如果是服务商，则填写服务商商户号，否则填写普通商户号)
$params["paymentAccount"]['sub_mch_id'] = ''; //子商户号
$params["paymentAccount"]['appid']      = ''; //appid
//证书路径，如果是服务商升级，则为服务商证书，否则为普通商户证书
$params["paymentAccount"]['SSLCERT_PATH'] = '';
$params["paymentAccount"]['SSLKEY_PATH']  = '';


/*
//服务商升级
//key为服务商的秘钥
$params["paymentAccount"]['key']        = ''; //正式秘钥获取沙箱秘钥，用于调用后续沙箱环境签名时的key获取
$params["paymentAccount"]['mch_id']     = ''; //商户号(如果是服务商，则填写服务商商户号，否则填写普通商户号)
$params["paymentAccount"]['sub_mch_id'] = ''; //子商户号
$params["paymentAccount"]['sub_appid']  = ''; //子商户appid
$params["paymentAccount"]['appid']      = ''; //appid
//证书路径，如果是服务商升级，则为服务商证书，否则为普通商户证书
$params["paymentAccount"]['SSLCERT_PATH'] = '';
$params["paymentAccount"]['SSLKEY_PATH']  = '';
*/
$file_name   = $params["paymentAccount"]['mch_id'] . '_sandboxkey.txt';
$sandbox_key = file_get_contents($file_name);
if ($sandbox_key) {

} else {
    $keyInfo = Cash_Coupon::getSignKey($params);
    if ($keyInfo['sandbox_signkey'] && $keyInfo['return_code'] == 'SUCCESS') {
        $sandbox_key = $keyInfo['sandbox_signkey'];
        file_put_contents($file_name, $keyInfo['sandbox_signkey']);
    } else { //获取沙箱key失败
        var_dump($keyInfo['return_msg']);
        exit;
    }
}

//注意-----------------------------------------------------
//只验证刷卡支付提交，刷卡支付查单，订单总金额为 501
//验证退款接口时，订单总金额为 502
//注意-----------------------------------------------------

$params["paymentAccount"]['key'] = $sandbox_key; //沙箱秘钥，调用后续接口用
//调用沙箱刷卡付款接口
echo "-------开始调用刷卡支付接口:-------" . PHP_EOL;
$params['total_fee'] = 501;//单位分
$params['tid']       = 'mcdjq_' . date('YmdHis') . rand(100000, 99999);
echo "-------订单号:-------" . $params['tid'] . PHP_EOL;
Cash_Coupon::newLine();
try {
    $payResult = Cash_Coupon::getMicroCardPayData($params);
    echo "-------查询订单支付结果:-------" . PHP_EOL;
    if ($payResult['result_code'] == 'SUCCESS' && $payResult['return_code'] == 'SUCCESS' && $payResult['trade_state'] == 'SUCCESS') {
        echo '-------订单总金额(分)-------:' . $payResult['total_fee'] . PHP_EOL;
        echo '-------支付结果-------:' . $payResult['trade_state_desc'] . PHP_EOL;
        echo '-------现金支付金额(分)-------:' . $payResult['cash_fee'] . PHP_EOL;
        echo '-------代金券金额(分)-------:' . $payResult['coupon_fee'] . PHP_EOL;
        echo '-------应结订单金额(分)-------:' . $payResult['settlement_total_fee'] . PHP_EOL;
        echo '-------微信订单号-------:' . $payResult['transaction_id'] . PHP_EOL;
        echo '-------商户系统单号-------:' . $payResult['out_trade_no'] . PHP_EOL;
    } else {
        var_dump($payResult['err_code'], $payResult['trade_state_desc'], '调用接口交易失败');
    }
    //当订单金额时502分时可发起退款验证
    if ($params['total_fee'] == 502) {
        //沙箱退款操作处理开始
        $refund_params['transaction_id'] = $payResult['transaction_id'];
        //$refund_params['tid']            = 'mcdjq-20180803190521100000';
        $refund_params['out_refund_no'] = 'mcdjqtk_' . date('YmdHis') . rand(100000, 99999);
        $refund_params['refund_fee']    = $params['total_fee'];
        $refund_params                  = $refund_params + $params;
        //var_dump($refund_params);exit;
        //退款申请提交,退款是否成功需要调用退款查询接口
        Cash_Coupon::newLine();

        echo "-------提交申请退款:-------" . PHP_EOL;
        $refundResult = Cash_Coupon::refundOrder($refund_params);
        echo '-------业务结果-------:' . $refundResult['result_code'] . PHP_EOL;
        echo '-------微信订单号-------:' . $refundResult['transaction_id'] . PHP_EOL;
        echo '-------商户订单号-------:' . $refundResult['out_trade_no'] . PHP_EOL;
        echo '-------商户退款单号-------:' . $refundResult['out_refund_no'] . PHP_EOL;
        echo '-------微信退款单号-------:' . $refundResult['refund_id'] . PHP_EOL;
        echo '-------退款金额(分)-------:' . $refundResult['refund_fee'] . PHP_EOL;
        Cash_Coupon::newLine();

        echo "-------查询退款结果:-------" . PHP_EOL;
        $queryRefundResult = Cash_Coupon::queryRefundOrder($refund_params);
        echo '-------业务结果-------:' . $queryRefundResult['result_code'] . PHP_EOL;
        echo '-------退款状态-------:' . $queryRefundResult['refund_status_0'] . PHP_EOL;
        echo '-------微信订单号-------:' . $queryRefundResult['transaction_id'] . PHP_EOL;
        echo '-------商户订单号-------:' . $queryRefundResult['out_trade_no'] . PHP_EOL;
        echo '-------商户退款单号-------:' . $queryRefundResult['out_refund_no_0'] . PHP_EOL;
        echo '-------微信退款单号-------:' . $queryRefundResult['refund_id_0'] . PHP_EOL;
        echo '-------退款金额(分)-------:' . $queryRefundResult['refund_fee_0'] . PHP_EOL;
        echo '-------订单金额(分)-------:' . $queryRefundResult['total_fee'] . PHP_EOL;
        echo '-------应结订单金额(分)-------:' . $queryRefundResult['settlement_total_fee'] . PHP_EOL;
        echo '-------总代金券退款金额(分)-------:' . $queryRefundResult['coupon_refund_fee_0'] . PHP_EOL;
        Cash_Coupon::newLine();
    }
    //对账单
    echo "-------对账单获取:-------" . PHP_EOL;
    $billParams['bill_type'] = 'PAYMENT';
    $billParams['bill_date'] = date('Ymd');
    $billParams              = $billParams + $params;
    $billResult              = Cash_Coupon::downloadbill($billParams);
    //对账单存储到文件,查看文件是否有账单信息,如果有说明下载成功，否则失败，重新调用接口
    file_put_contents('./' . date('Ymd') . '.txt', $billResult);
    echo "-------对账单获取完成，请查看当前目录下是否存在" . date('Ymd') . ".txt文件-------" . PHP_EOL;
    Cash_Coupon::newLine();
    echo '请访问: https://pay.weixin.qq.com/wiki/doc/api/tools/sp_coupon.php?chapter=15_6&index=4 查询升级进度' . PHP_EOL;
} catch (Exception $e) {
    var_dump($e->getMessage());
    exit;
}






























