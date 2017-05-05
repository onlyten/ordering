<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE  PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">


    <head>
        <meta charset="utf-8" />
        <meta  http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="Content-Language" content="en" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no" />
        <meta name="misapplication-tap-highlight" content="no" />
        <meta name="HandheldFriendly" content="true" />
        <meta name="MobileOptimized" content="320" />
        <title>订餐系统</title>
        <!--日历插件-->
        <link type="text/css" href="/share/ordering/Public/Phone/css/jquery-ui-1.10.0.custom.css" rel="stylesheet" />

        <!--end 日历插件-->
        <link rel="stylesheet" href="/share/ordering/Public/Phone/css/common.css" type="text/css" charset="utf-8" />
        <style type="text/css">
        a{
        text-decoration:none;
        }
            #footer ul li:nth-child(1) {
                background-image: url('/share/ordering/Public/Phone/images/index/bottombtn0101.png');
            }
            
            #footer ul li:nth-child(2) {
                background-image: url('/share/ordering/Public/Phone/images/index/bottombtn0201.png');
            }
            
            #footer ul li:nth-child(3) {
                background-image: url('/share/ordering/Public/Phone/images/index/bottombtn0301.png');
            }
            
            #footer ul li:nth-child(4) {
                background-image: url('/share/ordering/Public/Phone/images/index/bottombtn0401.png');
            }
            
            #footer ul li:nth-child(5) {
                background-image: url('/share/ordering/Public/Phone/images/index/bottombtn0401.png');
            }
            
            #footer ul li.active {
                color: #FF7D7D !important;
            }
            #footer ul li.active>a {
                color: #FF7D7D !important;
            }
            
            #footer ul li:nth-child(1).active {
                background-image: url('/share/ordering/Public/Phone/images/index/bottombtn0102.png');
            }
            
            #footer ul li:nth-child(2).active {
                background-image: url('/share/ordering/Public/Phone/images/index/bottombtn0202.png');
            }
            
            #footer ul li:nth-child(3).active {
                background-image: url('/share/ordering/Public/Phone/images/index/bottombtn0302.png');
            }
            
            #footer ul li:nth-child(4).active {
                background-image: url('/share/ordering/Public/Phone/images/index/bottombtn0402.png');
            }
            
            #footer ul li:nth-child(5).active {
                background-image: url('/share/ordering/Public/Phone/images/index/bottombtn0102.png');
            }
        </style>
    </head>

    <body style="overflow-x: hidden;">
        <header id="header">
            <div class="nvtt centerTitle">订餐系统</div>
            <div class="nvbt rightMenu" id="calTogger">收起日历</div>
        </header>
        <div id="pageContent" class="pageContent sdcontent" style="position:relative;overflow-x: hidden;">
            <div style="height:auto;width: 100%;" id="dateCj" class="dateCj">
                <div id="datepicker" ></div>
            </div>

            <div id="infoA" style="width: 100%;text-align: left;">
                 
            </div>

            <div class="sellerInfo" style="background: #f2f2f2;">
                <ul>
                    <?php if(is_array($shop)): foreach($shop as $key=>$vo): ?><li style="background-image: url('/share/ordering/Public/Uploads/<?php echo ($vo["savepath"]); echo ($vo["savename"]); ?>');" onclick='location.href="<?php echo U("User/EnterStore",array('shop_id'=>$vo['id']));?>"'>
                                <!-- <div style="line-height: 130px;height: 100%;width: 100%;" onclick='location.href="<?php echo U("User/EnterStore",array('shop_id'=>$vo['id']));?>"'><?php echo ($vo["name"]); ?></div> -->
                            </li>
                
                            <!-- <img src="/share/ordering/Public/Uploads/<?php echo ($vo["savepath"]); echo ($vo["savename"]); ?>"> --><?php endforeach; endif; ?>
                </ul>
            </div>
        </div>

        <div id="footer" class="footer indexFoot">
            <ul id="menus">
                <li onclick="switchIndex(this);location.href='<?php echo U('User/getList');?>'" class="active">订餐</li>
                <li onclick="switchIndex(this);location.href='<?php echo U('User/news');?>'">消息</li>
                <li onclick="switchIndex(this)"><font color="#6c6c6c">商场</font></li>
                <li onclick="switchIndex(this);location.href='<?php echo U('User/personal');?>'">我</li>
            </ul>
        </div>
    </body>
    <script type="text/javascript" src="/share/ordering/Public/Phone/js/jquery-1.7.2.js"></script>
    <script src="/share/ordering/Public/Phone/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="/share/ordering/Public/Phone/js/jquery-ui-1.10.0.custom.min.js" type="text/javascript"></script>
    <script src="/share/ordering/Public/Phone/js/docs.js" type="text/javascript"></script>
    <script src="/share/ordering/Public/Phone/js/demo.js" type="text/javascript"></script>

    <script src="/share/ordering/Public/Phone/js/api.js" type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript">
        window.onload = function() {
            $("#calTogger").toggle(
            function(){
              $(this).html("打开日历");
              $("#dateCj").hide();
               $("#infoA").show();
            },function(){
              $(this).html("收起日历");
              $("#dateCj").show(); 
              $("#infoA").hide(); 
            }); 
            var arr = $("#datepicker").val().split("/");
            var date = arr[2]+"-"+arr[0]+"-"+arr[1];
            //alert(date);
            var b='0';
             <?php if(is_array($detail)): foreach($detail as $key=>$vo): ?>if(date === '<?php echo ($vo["order_date"]); ?>'){
                    //$('#result').html('您今日的套餐是：<br/>'+'<?php echo ($vo["name"]); ?>');
                    $('#infoA').html('<img style="width:65px;height:65px;margin: 14px 14px" src="/share/ordering/Public/Uploads/<?php echo ($vo["savepath"]); echo ($vo["savename"]); ?>"/><div style="font-size: 16px;color: #000;display:inline-block;width: 70%;float: right;position: absolute;margin: 19px 6px;"><?php echo ($vo["menu_name"]); ?><br><a style="color: #999999;line-height: 38px;font-size: 12px;"><?php echo ($vo["name"]); ?></a></div>');
                    b = '1';
                 }
                 if(b == '0'){
                    $('#infoA').html('');
                 }<?php endforeach; endif; ?>


            <?php if(is_array($detail_pan)): foreach($detail_pan as $key=>$vo): ?>var da="<?php echo ($vo['order_date']); ?>";
                 $("#"+da+"").css("color","red");<?php endforeach; endif; ?>
            // <?php if(is_array($detail)): foreach($detail as $k=>$voo): ?>//       var da="<?php echo ($voo['id']); ?>";
            //       var h = '<?php echo ($k); ?>';
            //       alert(da);
            //       //alert(h);
            //      $("#"+date+"").css("border","1px red solid");
            //     //alert("f");
            //<?php endforeach; endif; ?>
           // alert($("#datepicker").val());  
        }
        function switchIndex(tag) {
            if(tag == $api.dom('#footer li.active')) return;
            var eFootLis = $api.domAll('#footer li'),
                index = 0;
            for(var i = 0, len = eFootLis.length; i < len; i++) {
                if(tag == eFootLis[i]) {
                    index = i;
                } else {
                    $api.removeCls(eFootLis[i], 'active');
                }
            }
            $api.addCls(eFootLis[index], 'active');

        }
        function haha(){
            var arr = $("#datepicker").val().split("/");
            var date = arr[2]+"-"+arr[0]+"-"+arr[1];
            //alert(date);
            var b='0';
             <?php if(is_array($detail)): foreach($detail as $key=>$vo): ?>if(date === '<?php echo ($vo["order_date"]); ?>'){
                    //$('#result').html('您今日的套餐是：<br/>'+'<?php echo ($vo["name"]); ?>');
                    $('#infoA').html('<img style="width:65px;height:65px;margin: 14px 14px" src="/share/ordering/Public/Uploads/<?php echo ($vo["savepath"]); echo ($vo["savename"]); ?>"/><div style="font-size: 16px;color: #000;display:inline-block;width: 70%;float: right;position: absolute;margin: 19px 6px;"><?php echo ($vo["menu_name"]); ?><br><a style="color: #999999;line-height: 38px;font-size: 12px;"><?php echo ($vo["name"]); ?></a></div>');
                    b = '1';
                 }
                 if(b == '0'){
                    $('#infoA').html('');
                 }<?php endforeach; endif; ?>
            <?php if(is_array($detail_pan)): foreach($detail_pan as $key=>$vo): ?>var d="<?php echo ($vo['order_date']); ?>";
                 $("#"+d+"").css("color","red");<?php endforeach; endif; ?>
        }
    </script>

</html>