<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE HTML>
<html>

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
        <title>购物车</title>
        <link rel="stylesheet" href="/share/ordering/Public/Phone/css/common.css" type="text/css" charset="utf-8" />
        <link href="/share/ordering/Public/Phone/css/bootstrap-combined.min.css" rel="stylesheet">
        <script type="text/javascript" src="/share/ordering/Public/Phone/js/jquery-1.7.2.js"></script>
        <script type="text/javascript" src="/share/ordering/Public/Phone/js/bootstrap.min.js"></script>
        <link rel="stylesheet" type="text/css" href="/share/ordering/Public/Phone/css/shopping-cart.css">
        <link href="/share/ordering/Public/Phone/css/reset.css" rel="stylesheet" type="text/css">
    </head>

    <body>
    <form name="form" action="<?php echo U('cart_pay');?>" method="post" >
        <header id="header">
            <div class="nvbt" onclick="javascript :history.back(-1);"><img src="/share/ordering/Public/Phone/images/index/back.png"/></div>
            <div class="nvtt">购物车</div>
            <!-- <div class="nvbt iphone"><img src="/share/ordering/Public/Phone/images/index/phone.png"/></div> -->
        </header>
        <div id="pageContent" class="pageContent sdcontent orderDetil">
                <ul>
                <div class="grayDivBackGround">
                    <!--这是页面显示效果，内部无值-->
                </div>
                <li>

                    <div class="liOrderTop detilTop">
                        <div class="liLeft detilLiLeft" style="width:90%"><?php echo ($user_message["xsxm"]); ?>&nbsp;&nbsp;&nbsp;
                        <?php echo ($user_message["xxmc"]); echo ($user_message["bjmc"]); ?></div>
                        <!-- <div class="liRight detilLiRight"><img src="/share/ordering/Public/Phone/images/index/next.png"/></div> -->
                    </div>
                    <div class="liOrderFoot detilFoot">
                        <ul>
                        <?php if(is_array($product)): foreach($product as $k=>$vo): ?><li>
                                <div class="detilLeft">
                                    <?php echo ($vo["order_date"]); ?>
                                </div>
                                <div class="detilMiddle">
                                    <?php echo ($vo["name"]); ?>
                                </div><!-- 
                                <div class="detilRight">
                                    ￥<?php echo ($vo["price"]); ?>
                                </div> -->
                            </li><?php endforeach; endif; ?>
                            <li>
                                <ul class="orderUl">
                                    <li>
                                        送餐日期：<?php echo ($range); ?>
                                    </li>
                                    <li>
                                        店铺：<?php echo ($shop_name); ?>
                                    </li>
                                    <li>
                                        套餐：<?php echo ($package_name); ?>
                                    </li>
                                    <li>
                                        <input type="text" name="mark" id="mark" value="" placeholder="填写备注" style="font-size:1em;width:95%;padding-left: 0px;padding-bottom: 15px;padding-right: 0px;padding-top: 0px;padding-top: 15px;"><br/><br/><br/><br/><br/><br/><br/><br/>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>

                </li>
            </ul>
            
        </div>


        <div style="position:absolute;bottom:0px;width:100%" id="thisIsfoot">
            <section>
                <div class="shopping-cart" id="ds_gal">
                    <div class="top clearfix">
                        <div class="pro-num">
                            <p>总计<?php echo ($days); ?>天 ￥<?php echo ($total); ?></p>
                        </div>
                        <a class="continue" href="#">实付￥<?php echo ($total); ?></a>
                    </div>
                </div>
            </section>
            <section>
                <div class="shopping-cart" id="ds_gal">
                    <div class="account">
                        <div class="total" style="margin-bottom: 0px;">
                            <div class="final">
                                <p>待支付：<strong id="cart_amount_desc">￥<?php echo ($total); ?>元</strong><button type="submit" style="background-color: red; float: right; padding: 15px; border-width: 0px; margin-right: -8px;">提交订单</button></p>
                                <input type="hidden" name="menu_id" id="menu_id" value="<?php echo ($menu_id); ?>">
                                <input type="hidden" name="total" id="total" value="<?php echo ($total); ?>">
                                <input type="hidden" name="shop_id" id="shop_id" value="<?php echo ($shop_id); ?>">
                                <input type="hidden" name="shop_name" id="shop_name" value="<?php echo ($shop_name); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        </form>
        <script type="text/javascript">
            window.onload=function(){
                $("input").focus(function(){$("#thisIsfoot").hide();});
                $("input").blur(function(){$("#thisIsfoot").show();});
            }
        </script>
    </body>

</html>