<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>

<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="/share/ordering/Public/Phone/css/eb/snower.css" media="all">
<link rel="stylesheet" type="text/css" href="/share/ordering/Public/Phone/css/eb/theme_red.css">
<link rel="stylesheet" type="text/css" href="/share/ordering/Public/Phone/css/eb/main.css">
<link rel="stylesheet" type="text/css" href="/share/ordering/Public/Phone/css/eb/eb.css">
<link rel="stylesheet" type="text/css" href="/share/ordering/Public/Phone/css/eb/reset.css">
<link rel="stylesheet" type="text/css" href="/share/ordering/Public/Phone/css/eb/common.css">
<link rel="stylesheet" type="text/css" href="/share/ordering/Public/Phone/css/eb/font-awesome.css">
<style type="text/css">
   .footer{ position: fixed;height: 48px; width: 100%; max-width: 640px; background-color: #3F3B3A; bottom:0px; }
   .body{ margin-bottom:48px; }
   .footer_left{font-size: 18px; color:#fff; padding-top: 10px;}
   .fleft{ float: left; }
   .fright{ float: right; }
   .width9{ width:90%; margin:0 auto; }
   .pay_way{ float:right;margin-right:30px;margin-top: -16px; }
   .pay_way_two{ padding-top: 15px;font-size:18px; }
   .modify{ background:#f5f4f3;height:50px;margin-top:11px; }
   .button.red{
    border:1px solid #b42323;
    box-shadow: 0 1px 2px #e99494 inset,0 -1px 0 #954b4b inset,0 -2px 3px #e99494 inset;
    background: -webkit-linear-gradient(top,#e85455,#e85455);
    background: -moz-linear-gradient(top,#e85455,#e85455);
    background: linear-gradient(top,#e85455,#e85455);
    }
</style>
<title>在线支付</title>  
		<meta content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
        <meta content="telephone=no, address=no" name="format-detection">
        <meta name="apple-mobile-web-app-capable" content="yes"> <!-- apple devices fullscreen -->
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <!-- Mobile Devices Support @end -->
        <link rel="stylesheet" type="text/css" href="/share/ordering/Public/Phone/css/eb/weimobfont.css" media="all">
        <!-- <link rel="shortcut icon" href="http://stc.weimob.com/img/favicon.ico"> -->
    </head>
    <body onselectstart="return true;" ondragstart="return false;" style="background:#eae9e8;">
        
<div data-role="container" class="container" >
    <header data-role="header">
    <div style="background:#eae9e8;">
        <div style="margin-left:15px;margin-right:15px;padding-top:15px;padding-bottom:10px;">
            <center><font size="4px">请在今天完成支付</font></center>
        </div>
    </div>

    <div style="background:#f5f4f3;height:80px;">
    <img style="height:80px;width:80px;border-radius:60%;" src="/share/ordering/Public/Uploads/<?php echo ($shop["savepath"]); echo ($shop["savename"]); ?>"/>
        <p style="margin-left:100px;padding-top: 15px;font-size:18px;margin-top: -85px;"><?php echo ($shop_name); ?><br/><br/>￥<?php echo ($total); ?>元</p>
    </div>
    <br/>
    <!-- <p style="font-size:18px;color:red;">&nbsp;&nbsp;&nbsp;&nbsp;请选择支付方式</p> -->
    <!-- <?php if($yue != null): ?>-->
    <!--<?php endif; ?> -->
    <?php if($yue != null): ?><div class="modify">
            <label for="wechat"><p class="pay_way_two">&nbsp;&nbsp;&nbsp;&nbsp;您的钱包有<?php echo ($yue); ?>元可用</p></label>
        </div><?php endif; ?>
    <div class="modify">
        <label for="wechat"><p class="pay_way_two">&nbsp;&nbsp;&nbsp;&nbsp;微信支付</p></label>
         <input class="pay_way" type="radio" name="way" id="wechat" value="wechat" checked="checked"/>
    </div>
    <form id="test">
        <input type="hidden" id="" name="body" value="<?php echo ($body_detail); ?>">
        <input type="hidden" id="" name="mch_create_ip" value="<?php echo ($ip); ?>">
        <input type="hidden" id="" name="method" value="submitOrderInfo">
        <input type="hidden" id="" name="out_trade_no" value="<?php echo ($order_id); ?>">
        <input type="hidden" id="" name="sub_openid" value="<?php echo ($_COOKIE['openid']); ?>">
        <input type="hidden" id="" name="total_fee" value="<?php echo ($zhifu_money); ?>">
    </form>
    <!-- <div class="modify">
        <label for="alipay"><p class="pay_way_two">&nbsp;&nbsp;&nbsp;&nbsp;支付宝支付</p></label>
        <input class="pay_way" type="radio" name="way" id="alipay" value="alipay" />
    </div>
    <div class="modify">
        <label for="card"><p class="pay_way_two">&nbsp;&nbsp;&nbsp;&nbsp;银行卡</p></label>
        <input class="pay_way" type="radio" name="way" id="card" value="card" />
    </div> --><br/><br/><br/><br/><br/>
    <?php if($zhifu_money != 0): ?><div align="center">
            <button style="width:160px; height:50px; background-color:red; border:0px #FE6714 solid; cursor: pointer;  color:white;  font-size:20px;" type="button" onclick="callpay()" >确认支付</button>
        </div><?php endif; ?>
    <?php if($zhifu_money == 0): ?><div align="center">
            <button style="width:160px; height:50px; background-color:red; border:0px #FE6714 solid; cursor: pointer;  color:white;  font-size:20px;" type="button" onclick="window.location.href='<?php echo U('order_update',array('order_id'=>$order_id));?>'">余额支付</button>
        </div><?php endif; ?>
    </header>  
</div>
<script type="text/javascript" src="/share/ordering/Public/Phone/js/jquery-1.7.1.min.js"></script>
<script type="text/javascript">
function callpay(){
    //alert("kk");
    var y=$("#test").serialize();
    //alert(y);
    //var b = {"out_trade_no":"824582671640705","method":"submitOrderInfo","body":"测试购买商品","attach":"附加信息","total_fee":"10","mch_create_ip":"127.0.0.1"};
    //var a= {"body":"xiaomi","mch_create_ip":"127.0.0.1","method":"submitOrderInfo","out_trade_no":"343559655","sub_openid":"","total_fee":"11"};
    //alert(JSON.stringify(a));
   // var b = {"out_trade_no":"35","body":"37","attach":"43","total_fee":"10","mch_create_ip":"127.0.0.1"};
    //document.getElementById("test").value=b;
    // $.ajax({
    //             type: "POST",
    //             url: "http://127.0.0.1/share/ordering/index.php/Home/Request",
    //             dataType: "xml",
    //             data: y,
    //             success: function(msg){
    //                 var data = msg;
                    
    //                 document.getElementById("test").value=msg;
    //                  alert(msg);
    //                 // window.location.reload();
    //             }
    //         });




    $.ajax({
         type: "POST",  //访问WebService使用post方式请求
         //async: false,
         //contentType: "text/xml;utf-8",//使用的xml格式的
         url: "/ordering/index.php/Phone/Request", //调用WebService的地址和方法名称组合<?php echo U('Pay/test');?>
         //url: "<?php echo U('Pay/test');?>";
         data: y,  //这里是要传递的参数
         dataType: "json",  //参数类型为xml
         success: function (res) {
             alert(res.token_id);
             //alert(res.msg);
             window.location.href="https://pay.swiftpass.cn/pay/jspay?token_id="+res.token_id+"&showwxtitle=1";
             //alert(JSON.stringify(res));
         },

         error:function(msg){
            alert("出错啦/(ㄒoㄒ)/~~");
         }
     });
    //alert("(ㄒoㄒ)程序猿回花果山了(ㄒoㄒ)");
}
</script>
</body></html>