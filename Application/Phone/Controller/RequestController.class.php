<?php
/**
 * 支付接口调测例子
 * ================================================================
 * index 进入口，方法中转
 * submitOrderInfo 提交订单信息
 * queryOrder 查询订单
 * 
 * ================================================================
 */
/*require('Utils.class.php');
require('config/config.php');
require('class/RequestHandler.class.php');
require('class/ClientResponseHandler.class.php');
require('class/PayHttpClient.class.php');
*/
namespace Phone\Controller;
use Common\Controller\Utils;
use Common\Controller\Payconfig;
use Common\Controller\RequestHandler;
use Common\Controller\ClientResponseHandler;
use Common\Controller\PayHttpClient;

Class RequestController{
    //$url = 'http://192.168.1.185:9000/pay/gateway';

    private $resHandler = null;
    private $reqHandler = null;
    private $pay = null;
    private $cfg = null;
    
    public function __construct(){
        $this->Request();
    }

    public function Request(){
        $this->resHandler = new ClientResponseHandler();
        $this->reqHandler = new RequestHandler();
        $this->pay = new PayHttpClient();
        $this->cfg = new Payconfig();

        $this->reqHandler->setGateUrl($this->cfg->PayC('url'));
        $this->reqHandler->setKey($this->cfg->PayC('key'));
    }
    
    public function index(){
        $method = isset($_REQUEST['method'])?$_REQUEST['method']:'submitOrderInfo';
        switch($method){
            case 'submitOrderInfo'://提交订单
                $this->submitOrderInfo();
            break;
            case 'queryOrder'://查询订单
                $this->queryOrder();
            break;
            case 'submitRefund'://提交退款
                $this->submitRefund();
            break;
            case 'queryRefund'://查询退款
                $this->queryRefund();
            break;
            case 'callback':
                $this->callback();
            break;
        }
    }
    
    /**
     * 提交订单信息
     */
    public function submitOrderInfo(){
        $this->reqHandler->setReqParams($_POST,array('method'));
        $this->reqHandler->setParameter('service','pay.weixin.jspay');//接口类型：pay.weixin.jspay
        $this->reqHandler->setParameter('mch_id',$this->cfg->PayC('mchId'));//必填项，商户号，由威富通分配
        $this->reqHandler->setParameter('version',$this->cfg->PayC('version'));
        
        //通知地址，必填项，接收威富通通知的URL，需给绝对路径，255字符内格式如:http://wap.tenpay.com/tenpay.asp
        //$notify_url = 'http://'.$_SERVER['HTTP_HOST'];
        //$this->reqHandler->setParameter('notify_url',$notify_url.'/payInterface/request.php?method=callback');
		$this->reqHandler->setParameter('notify_url','http://1.189.88.140/ordering/Phone/Request/index/method/callback');
		$this->reqHandler->setParameter('callback_url','http://dctest.wanshuyun.com/ordering/Phone/User/personal');
        $this->reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//随机字符串，必填项，不长于 32 位
        $this->reqHandler->createSign();//创建签名
        
        $data = Utils::toXml($this->reqHandler->getAllParameters());
        //var_dump($data);
        
        $this->pay->setReqContent($this->reqHandler->getGateURL(),$data);
        if($this->pay->call()){
            $this->resHandler->setContent($this->pay->getResContent());
            $this->resHandler->setKey($this->reqHandler->getKey());
            if($this->resHandler->isTenpaySign()){
                //当返回状态与业务结果都为0时才返回支付二维码，其它结果请查看接口文档
                if($this->resHandler->getParameter('status') == 0 && $this->resHandler->getParameter('result_code') == 0){
                    echo json_encode(array('token_id'=>$this->resHandler->getParameter('token_id')));
                    exit();
                }else{
                    echo json_encode(array('status'=>500,'msg'=>'Error Code:'.$this->resHandler->getParameter('err_code').' Error Message:'.$this->resHandler->getParameter('err_msg')));
                    exit();
                }
            }
            echo json_encode(array('status'=>500,'msg'=>'Error Code:'.$this->resHandler->getParameter('status').' Error Message:'.$this->resHandler->getParameter('message')));
        }else{
            echo json_encode(array('status'=>500,'msg'=>'Response Code:'.$this->pay->getResponseCode().' Error Info:'.$this->pay->getErrInfo()));
        }
    }

    /**
     * 查询订单
     */
    public function queryOrder(){
        $this->reqHandler->setReqParams($_POST,array('method'));
        $reqParam = $this->reqHandler->getAllParameters();
        if(empty($reqParam['transaction_id']) && empty($reqParam['out_trade_no'])){
            echo json_encode(array('status'=>500,
                                   'msg'=>'请输入商户订单号,威富通订单号!'));
            exit();
        }
        $this->reqHandler->setParameter('version',$this->cfg->PayC('version'));
        $this->reqHandler->setParameter('service','trade.single.query');//接口类型：trade.single.query
        $this->reqHandler->setParameter('mch_id',$this->cfg->PayC('mchId'));//必填项，商户号，由威富通分配
        $this->reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//随机字符串，必填项，不长于 32 位
        $this->reqHandler->createSign();//创建签名
        $data = Utils::toXml($this->reqHandler->getAllParameters());

        $this->pay->setReqContent($this->reqHandler->getGateURL(),$data);
        if($this->pay->call()){
            $this->resHandler->setContent($this->pay->getResContent());
            $this->resHandler->setKey($this->reqHandler->getKey());
            if($this->resHandler->isTenpaySign()){
                $res = $this->resHandler->getAllParameters();
                Utils::dataRecodes('查询订单',$res);
                //支付成功会输出更多参数，详情请查看文档中的7.1.4返回结果
                echo json_encode(array('status'=>200,'msg'=>'查询订单成功，请查看result.txt文件！','data'=>$res));
                exit();
            }
            echo json_encode(array('status'=>500,'msg'=>'Error Code:'.$this->resHandler->getParameter('status').' Error Message:'.$this->resHandler->getParameter('message')));
        }else{
            echo json_encode(array('status'=>500,'msg'=>'Response Code:'.$this->pay->getResponseCode().' Error Info:'.$this->pay->getErrInfo()));
        }
    }
    
    /**
     * 实现curl的post和get访问方式
     * @param string $url
     * @param booleam $https
     * @param string $method
     * @param array $data
     */
    public function _request($curl,$https = true,$method='GET',$data = null){
               
        $ch = curl_init();  //初始化
        $this_header = array(
					"content-type: application/x-www-form-urlencoded; 
					charset=UTF-8"
					);
        curl_setopt($ch,CURLOPT_URL,$curl);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$this_header);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); //返回字符串，不直接输出
        //判断是否使用https协议
        if($https){
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false); //不做服务器的验证
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2); //服务器证书验证
        }
        //是否是POST请求
        if($method == 'POST'){
            curl_setopt($ch,CURLOPT_POST,true); //设置为POST请求
            curl_setopt($ch,CURLOPT_POSTFIELDS,$data); //设置POST的请求数据
        }
        $content = curl_exec($ch); //访问指定URL
        curl_close($ch); //关闭cURL释放资源
        return $content;
    } 
    /**
     * 提供给威富通的回调方法
     */
    public function callback(){
    	
        $xml = file_get_contents('php://input');      
        $this->resHandler->setContent($xml);		
        $this->resHandler->setKey($this->cfg->PayC('key'));
        if($this->resHandler->isTenpaySign()){
			
            if($this->resHandler->getParameter('status') == 0 && $this->resHandler->getParameter('result_code') == 0){			
				//更改订单状态               
				if($this->resHandler->getParameter('pay_result') == 0){
                     Utils::dataRecodes('接口回调',$this->resHandler->getAllParameters());
                     echo 'success';
                     $datacall = array(                                    
                                    "mch_id" =>  $this->resHandler->getParameter('mch_id'),                                    
                                    "status" =>  $this->resHandler->getParameter('status'),
                                    "openid" =>  $this->resHandler->getParameter('openid'),
                                    "orderid" => $this->resHandler->getParameter('out_trade_no'),
                                    "result_code" =>  $this->resHandler->getParameter('result_code'),
                                    "pay_result" =>  $this->resHandler->getParameter('pay_result')
                                                                       
                                );
                    $callid = D("CallbackBefore")->add($datacall);
                    //支付成功,更改订单状态
                    $out_trade_no = $this->resHandler->getParameter('out_trade_no'); //订单号
                    $data = array(
                        "pay_status" => 1
                    );
                    $neworder = M('Order')->where('orderid="'.$out_trade_no.'"')->save($data);
                    if($neworder > 0){
						
						//客户充值的方法调用，当微支付异步通知调用后调用此接口。调用此接口分订餐、补餐两种类型
						$order = D("Order")->get(array("orderid" => $out_trade_no));    //获取用户id
						$custId =$order['user_id']; 
						//得到开始时间和结束时间
						$timeBegin = 'SELECT p.order_date,s.totalprice,s.user_id,s.shop_id FROM multi_product p ,'.
								'(SELECT o.user_id,o.totalprice,d.product_id,o.shop_id    FROM   multi_order o ,'.
								'multi_order_detail d WHERE d.order_id=o.id AND o.orderid="'.$out_trade_no.'") '.
								's WHERE p.id = s.product_id ORDER BY p.order_date LIMIT 1';
						$timeEnd = 'SELECT p.order_date,s.totalprice,s.user_id,s.shop_id FROM multi_product p ,'.
								'(SELECT o.user_id,o.totalprice,d.product_id,o.shop_id    FROM   multi_order o ,'.
								'multi_order_detail d WHERE d.order_id=o.id AND o.orderid="'.$out_trade_no.'") '.
								's WHERE p.id = s.product_id ORDER BY p.order_date DESC LIMIT 1';
								
						 $beginL = M()->query($timeBegin); 
						 $endL = M()->query($timeEnd);  
						 if(!empty($beginL))
						 {
							 $money = $beginL[0]["totalprice"];
							 $beginDate =  $beginL[0]["order_date"];
							 $merchantId=$beginL[0]["shop_id"];
						 }
						 if(!empty($beginL))
						 {
							  $endDate =  $endL[0]["order_date"];
						 }
					 
						
						$orderType = 1;  //1:订餐，2：补餐						
						$openid = $this->resHandler->getParameter('openid'); //openid
						
						//客户充值的方法调用
						$urlt = CONNECT."recharge/".$custId."/".$merchantId."/".$openid."/".$out_trade_no."/".$orderType."/".$money."/".$beginDate."/".$endDate;
						
						$data = array(
									"orderid" => $out_trade_no,
									"custid" => $custId,
									"merchantid" => $merchantId,
									"openid" => $openid,
									"money" => $money,
									"beginDate" => $beginDate,
									"endDate" => $endDate,
									"url" => $urlt
									
								);
						$t = D("Callback")->add($data);
						$result = $this->_request($urlt,true,'POST');
						if(empty($result))
						{
							$res = $result.'---返回结果为空';
						}else
                        {
							$res = $result;
						}							
												
						$result = json_decode($result);
						
						$dataend = array(
						   "result" =>$res					
						);
						D('Callback')->where('id='.$t)->save($dataend);					
						
						//客户充值的方法调用，当微支付异步通知调用后调用此接口。调用此接口分订餐、补餐两种类型 end
                        
                        exit();
                    }else{
                        echo 'fail';
                        exit();
                    }
                }else{
                    echo 'fail';
                    exit();
                }
                
            }else{
                echo 'fail';
                exit();
            }
        }else{
            echo 'fail';
        }
    }
}

?>