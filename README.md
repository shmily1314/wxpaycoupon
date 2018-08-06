# wxpaycoupon

# wxpaycoupon
升级微信支付免充代金券
 * 完成以下四步即可完成升级
 * 接口文档描述: https://pay.weixin.qq.com/wiki/doc/api/download/mczyscsyl.pdf
 * 查询升级进度: https://pay.weixin.qq.com/wiki/doc/api/tools/sp_coupon.php?chapter=15_6&index=4
 * 1.获取沙箱key
 * 2.调用刷卡接口
 * 3.退款
 * 4.对账单下载
 * 注: 免充值券不参与结算

升级分为:
普通商户微信支付升级
服务商微信支付升级

注：
获取沙箱key使用正式商户对应的秘钥
普通商户获取沙箱key，调用接口时，签名key为正式商户号对应的key，获取成功后，调用后续其他接口请使用沙箱key
服务商商户获取沙箱key，调用接口时，签名key为服务商商户号对应的key，获取成功后，调用后续其他接口请使用沙箱key

验证刷卡付款、查单时，订单金额必须是501分

验证退款时，证书注意事项：
退款时的订单金额必须是502分
普通商户退款，为商户号对应的证书
服务商商户退款，证书为服务商商户号对应的证书

请按照自己的微信支付商户类型替换文件中对应的参数

