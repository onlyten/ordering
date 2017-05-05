<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE HTML>
<html>

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no" />
        <meta name="misapplication-tap-highlight" content="no" />
        <meta name="HandheldFriendly" content="true" />
        <meta name="MobileOptimized" content="320" />
        <title><?php echo ($shop["name"]); ?></title>
        <link rel="stylesheet" href="/share/ordering/Public/Phone/css/common.css" type="text/css" charset="utf-8" />
        <link href="/share/ordering/Public/Phone/css/lanrenzhijia.css" type="text/css" rel="stylesheet" />
        <link href="/share/ordering/Public/Phone/css/bootstrap-combined.min.css" rel="stylesheet">
        <script type="text/javascript" src="/share/ordering/Public/Phone/js/jquery-1.7.2.js"></script>
        <script type="text/javascript" src="/share/ordering/Public/Phone/js/bootstrap.min.js"></script>
        <script type="text/javascript">
            window.onload=function(){
                $(".ulTabC").height($(".abcdUl").find("li").length*180);
            }
        </script>
    </head>

    <body>
    <input type="hidden" name="one" id="one" value="<?php echo ($package_one_num); ?>">
    <input type="hidden" name="two" id="two" value="<?php echo ($menu[0]['name']); ?>">
    <input type="hidden" name="menu_id" id="menu_id" value="">
    <input type="hidden" name="old_menu_id" id="old_menu_id" value="<?php echo ($menu_id); ?>">
        <header id="header">
            <div class="nvbt" onclick="javascript :history.back(-1);"><img src="/share/ordering/Public/Phone/images/index/back.png"/></div>
            <div class="nvtt"><?php echo ($shop["name"]); ?></div>
            <div onclick="location.href='<?php echo U('shop_detail',array('shop_id'=>$shop['id']));?>'">详情</div>
        </header>
        <div id="pageContent" class="pageContent sdcontent detilsWm">
            <div class="container-fluid">
                <div class="row-fluid">
                    <div class="span12">
                        <div class="tabbable tabs-left" id="tabs-156692">
                            <ul class="nav nav-tabs">
                                <?php if(is_array($menu)): foreach($menu as $k=>$vo): if($k == 0): ?><li class="active">
                                            <a href="javascript:void(0)"  onclick="changed(<?php echo ($vo["id"]); ?>)" data-toggle="tab"><?php echo ($vo["name"]); ?></a>
                                        </li>
                                        <?php else: ?><li >
                                            <a href="javascript:void(0)"  onclick="changed(<?php echo ($vo["id"]); ?>)" data-toggle="tab"><?php echo ($vo["name"]); ?></a>
                                        </li><?php endif; endforeach; endif; ?>
                            </ul>
                            <div class="tab-content ulTabC">
                                <div class="tab-pane active" id="panel-15a">
                                    <ul  class="abcdUl">
                                        <?php if(is_array($package_one)): foreach($package_one as $key=>$vo): ?><li>
                                                <div class="liTop">
                                                    &nbsp;&nbsp;<?php echo ($vo["order_date"]); ?>  <?php echo ($vo["week"]); ?>
                                                </div>
                                                <div class="liFoot">
                                                    <a class="example" href="/ordering/Public/Uploads/<?php echo ($vo["savepath"]); echo ($vo["savename"]); ?>"><img width="75px" height="75px" src="/ordering/Public/Uploads/<?php echo ($vo["savepath"]); echo ($vo["savename"]); ?>"></a>
                                                    <div class="tcInfo">
                                                        <br><?php echo ($vo["name"]); ?>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="menu_one_id" id="menu_one_id" value="<?php echo ($vo["menu_id"]); ?>">
                                            </li><?php endforeach; endif; ?>    
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="goBuyInfo" class="footer">
            <div id="orderInfo">
                <img style="position: relative;margin:-5px 5px;" src="/share/ordering/Public/Phone/images/index/shopCar.png" />
                <div style="position:absolute;display: inline-block;padding-top:4px " class="">
                    <a id="menu_name">N套餐</a><a id="num">N份</a><br>
                    <a id="tcTime"><?php echo ($zhouqi); ?></a>
                </div>
            </div>
            <div id="goBuy">
                <a href="javascript:void(0)" onclick="change()"><font color="white">去结算</font></a>
            </div>
        </div>
        <script src="/share/ordering/Public/Phone/js/jquery.min.js"></script>
        <script src="/share/ordering/Public/Phone/js/jquery.imgbox.pack.js"></script>
        <script>
         $(function(){
            $(".example").imgbox({
                'speedIn'       : 0,
                'speedOut'      : 0,
                'alignment'     : 'center',
                'overlayShow'   : true,
                'allowMultiple' : false
            });
        });
        </script>
        <script type="text/javascript">
            $(document).ready(function(){
                document.getElementById("menu_id").value = document.getElementById("menu_one_id").value;
                document.getElementById("num").innerHTML = document.getElementById("one").value+"份";
                document.getElementById("menu_name").innerHTML = document.getElementById("two").value;

                });
            var swiper = new Swiper('.swiper-container', {
                pagination: '.swiper-pagination',
                paginationClickable: true,
                spaceBetween: 30,
            });

            $(function(){
                $('#nav').onePageNav();
            });

            </script>

            <script>
            $(function(){
            $(".example").imgbox({
                'speedIn'       : 0,
                'speedOut'      : 0,
                'alignment'     : 'center',
                'overlayShow'   : true,
                'allowMultiple' : false
            });
        });
                //正常流程去结算
                function change() {    
                    var y=document.getElementById("menu_id").value;
                    var url="<?php echo U('User/cart');?>?menu_id="+y;
                    window.location.href=url;
                }

                //点击套餐名显示套餐中所有的商品
                function changed(b) {
                    document.getElementById("menu_id").value = b;
                    // alert(b);
                    $.ajax({
                                type: "POST",
                                url: "<?php echo U('menu_select');?>",
                                dataType: "json",
                                data: "menu_id="+b,
                                success: function(msg){
                                    var data = msg.split("***");
                                     document.getElementById('panel-15a').innerHTML= data[0];
                                     document.getElementById("num").innerHTML = data[1]+"份";
                                     document.getElementById("menu_name").innerHTML = data[2];
                                     document.getElementById("tcTime").innerHTML = data[3];
                                    // document.getElementById("test").value=msg;
                                     //alert(msg);
                                    // window.location.reload();
                                    // 
                                    // 
                                              $(".example").imgbox({
                                                'speedIn'       : 0,
                                                'speedOut'      : 0,
                                                'alignment'     : 'center',
                                                'overlayShow'   : true,
                                                'allowMultiple' : false
                                            });
                                }
                            });
                }
            </script>
    </body>

</html>