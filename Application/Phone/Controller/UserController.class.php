<?php
namespace Phone\Controller;
use Think\Controller;
class UserController extends BaseController
{
    public function getList(){//店铺列表
        $login_name = cookie("username");
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $user = $Model->query("SELECT id,company_id FROM multi_sys_user WHERE login_name = '$login_name' limit 1");
        //$user_id = $user[0]['id'];
        $company_id = $user[0]['company_id'];
        $m = M("school_shop");
        $mapp["company_id"] = $company_id;
        $shop_id = $m->where($mapp)->field('shop_id')->select();
        $long = count($shop_id);
        for($i=0;$i<$long;$i++){
            $arr[] = $shop_id[$i]["shop_id"]; 
        }
        if(count($arr) != 0){
            $map["status"] = array('eq',2);
            $map["id"] = array("in",$arr);
            $shop = D("ShopFile")->where($map)->select();
            $mmap["user_id"] = $user[0]['id'];
            $mmap["leave_status"] = array("neq",1);
            $mmap["retreat_status"] = array("neq",1);
            $detail = D("DePro")->where($mmap)->select();
            /**************************日历格式 所有订餐标红 start*****************************/
            $detail_pan = $detail;//日历日期格式更改用到
            // dump($detail_pan);
            // die();
            $long = count($detail_pan);
            for($j=0;$j<$long;$j++){
                if(strtotime($detail_pan[$j]["order_date"]) > time()){
                    $detail_pa[] = $detail_pan[$j];
                }
            }
            $long = count($detail_pa);
            for($i=0;$i<$long;$i++){//日期格式从 20160103 转化成 201613
                $arr1 = str_split($detail_pa[$i]["order_date"]);
                $arr1[4] = "";
                $arr1[7] = "";
                if($arr1[5] == 0){
                    $arr1[5] = "";
                }
                if($arr1[8] == 0){
                    $arr1[8] = "";
                }
                $str = implode("",$arr1);
                $detail_pa[$i]["order_date"] = $str;
            }
            /**************************日历格式 所有订餐标红  end*****************************/
            $this->assign("detail_pan",$detail_pa);
            $this->assign("detail",$detail);
            $this->assign("shop",$shop);
            $this->display();
        }else{
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo  "<script>alert('学校附近没有餐厅');window.location.href='https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx7cd8c8ccc5c9747f&redirect_uri=http://dctest.wanshuyun.com/ordering/Phone/Login/login&response_type=code&scope=snsapi_base&state=123&connect_redirect=1#wechat_redirect'</script>";
        }
    }

    public function EnterStore(){//进入具体某一店铺
        $shop_id = I("get.shop_id");
        $n = M("menu");
        $p = D("ProFile");
        //$p = M("product");
        $map["shop_id"] = $shop_id;
        $map["status"] = 1;//菜单已发布 9.10 add
        $menu = $n->where($map)->select();
        //$menu_count = count($menu);
        for($i=0;$i<1;$i++){//只需要一次，其他的通过ajax
            $mapp['menu_id'] = array("eq",$menu[$i]['id']);
            $product = $p->where($mapp)->field('name,menu_id,order_date,savename,savepath')->select(); 
            $pro_count = count($product);
            for($z=0;$z<$pro_count;$z++){//所有的商品配送日期改为时间戳
                $product[$z]["order_date"] = strtotime($product[$z]["order_date"]);
            }
            for($l=0;$l<$pro_count;$l++){//如果配送日期已过，则不显示
                if($product[$l]["order_date"] > time()){
                    $product1[]=$product[$l];
                }
            }

            $num = count($product1);
            for($j=0;$j<$num;$j++){//商品的配送日期转化为星期
                $time = $product1[$j]["order_date"];
                $lib = date('w',$time);
                $weekarray=array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");
                $product1[$j]["week"] = $weekarray[$lib];
                $product1[$j]["order_date"] = date("Y-m-d",$product1[$j]["order_date"]);//时间戳换回日期格式
            }
            $arr[] = $product1;
            $zhouqi = $product1[0]["order_date"]." 至 ".$product1[$num-1]["order_date"];
        }
        $m = M("shop");
        $mapp['id'] = array("eq",$shop_id);
        $shop = $m->where($mapp)->find();
        $this->assign("shop",$shop);
        $this->assign("package_one",$arr[0]);
        $this->assign("package_one_num",count($arr[0]));
        $this->assign("menu",$menu);
        $this->assign("zhouqi",$zhouqi);
       $this->display();
    }

    public function cart(){//加入购物车
        $menu_id = I("get.menu_id");
        $n = D("ShopMenu");
        $map['id'] = $menu_id;
        $message = $n->where($map)->find();
        // dump($message);
        if($message){
            $m = M("product");
            $mapp["menu_id"] = $menu_id;
            $product1 = $m->where($mapp)->field("name,price,order_date")->select();

            $pro_count = count($product1);
            for($z=0;$z<$pro_count;$z++){//所有的商品配送日期改为时间戳
                $product1[$z]["order_date"] = strtotime($product1[$z]["order_date"]);
            }
            for($l=0;$l<$pro_count;$l++){//如果配送日期已过，则不显示
                if($product1[$l]["order_date"] > time()){
                    $product1[$l]["order_date"] = date("Y-m-d",$product1[$l]["order_date"]);//时间戳换回日期格式
                    $product[]=$product1[$l];
                }
            }

            $num = count($product);
            if($num == 0){//如果套餐内什么都没有，则返回重新选择
                echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                echo  "<script>alert('这个套餐目前为空，请重新选择！');javascript:self.location=document.referrer;</script>";
            }
            $range = $product[0]["order_date"]." 至 ".$product[$num-1]["order_date"];
            $login_name = cookie("username");
            $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
            $user_mobile = $Model->query("SELECT mobile FROM multi_sys_user WHERE login_name = '$login_name' limit 1");
            $user_mobile = $user_mobile[0]['mobile'];
            $long = count($product);
            for($i=0;$i<$long;$i++){
                $product[$i]['order_date'] = str_replace("-","月",substr($product[$i]['order_date'],5))."日";
            }

            //学生学校班级信息
            $mmap["login_name"] = cookie("username");
            $user_message = D("UserAddress")->where($mmap)->find();

            //查询套餐的总价
            $mmaa["id"] = $menu_id;
            $price = M("menu")->where($mmaa)->getField("price");
            $price = $price*$num;
            // echo $price;
            // die();
            $this->assign("range",$range);
            //$this->assign("total",$product[0]['price']*count($product));
            $this->assign("total",$price);
            $this->assign("product",$product);
            $this->assign("days",count($product));
            $this->assign("shop_name",$message["shop_name"]);
            $this->assign("shop_id",$message["shop_id"]);
            $this->assign("package_name",$message["name"]);
            $this->assign("menu_id",$menu_id);
            $this->assign("user_message",$user_message);
            $this->assign("user_mobile",$user_mobile);
            $this->display();
        }else{
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo  "<script>alert('您还没有选择套餐！');javascript:self.location=document.referrer;</script>";
        }
        
    }


    public function cart_pay(){//支付页面
        $login_name = cookie("username");
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $user = $Model->query("SELECT id FROM multi_sys_user WHERE login_name = '$login_name' limit 1");
        $user_id = $user[0]['id'];
        /*
        判断钱包里面的余额 start
         */
        $url="http://172.168.8.25:8080/jeecg/rest/CustFund/".$user_id;
        //echo $url;
        $result = $this->_request($url,true,'POST'); 
        $result = json_decode($result);
        //dump($result);
        $money_s = $result[0]->resideMoney;//剩余资金
        $money_d = $result[0]->payMoney;//已付金额
        /*
        判断钱包里面的余额 end
         */
        
        //查询此套餐的价格
        $mama["id"] = I("post.menu_id");
        $total_price = M("menu")->where($mama)->getField('price');
        $map_zou["menu_id"] = I("post.menu_id");
        $product1 = M("product")->where($map_zou)->select();

        $pro_count = count($product1);
        for($z=0;$z<$pro_count;$z++){//所有的商品配送日期改为时间戳
            $product1[$z]["order_date"] = strtotime($product1[$z]["order_date"]);
        }
        for($l=0;$l<$pro_count;$l++){//如果配送日期已过，则不显示
            if($product1[$l]["order_date"] > time()){
                $product1[$l]["order_date"] = date("Y-m-d",$product1[$l]["order_date"]);//时间戳换回日期格式
                $product[]=$product1[$l];
            }
        }
        $pro_count1 = count($product);
        $total_price = $total_price*$pro_count1;
        
        //判断是否显示余额（钱包里有零钱，但不够）
        if($money_s != 0.00){
            if($total_price > $money_s){
                $this->assign("yue",$money_s);
            }
        }

        //查询对应的商店名和套餐名
        $mamm["id"] = I("post.menu_id");
        $name_detail = D("ShopMenu")->where($mamm)->find();
        $body_detail = $name_detail["shop_name"].$name_detail["name"];
        // dump($name_detail);
        
        //实际支付金额
        $difference = $total_price - $money_s;
        if($difference <= 0){
            $zhifu_money = 0;
        }else{
            $zhifu_money = $difference;
        }

        //获取班级id 并插入到order表中
        $bj["dyzh"] = array("eq",$user_id);
        $class_id = M("xj_student")->where($bj)->getField("bj");
    
        $m = M("order");
        $data["class_id"] = $class_id;
        $data["menu_id"] = I("post.menu_id");
        $data["shop_id"] = I("post.shop_id");
        $data["time"] = date("Y-m-d H:i:s", time());
        $data["totalprice"] = I("post.total");
        $data["pay_status"] = 0;
        $data["remark"] = I("post.mark");
        $data["user_id"] = $user_id;
        //$data["orderid"] = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        do{
            $orderid = $this->guid();
            $maporderid["orderid"] = $orderid;
            $orderid_num = $m->where($maporderid)->count();
        }while($orderid_num);
        $data["orderid"] = $orderid;
        $m->data($data)->add();//向order表中插入一条记录
        

        $mmap["user_id"] = $user_id;
        $order = $m->where($mmap)->order('id desc')->limit(1)->find();

        $num = count($product);
        $nn = M("order_detail");
        for($i=0;$i<$num;$i++){//向order_detail表中插入多条记录
            $dataa["product_id"] = $product[$i]["id"];
            $dataa["user_id"] = $user_id;
            $dataa["class_id"] = $class_id;
            $dataa["name"] = $product[$i]["name"];
            $dataa["num"] = 1;
            $dataa["time"] = date("Y-m-d H:i:s", time());
            $dataa["price"] = $product[$i]["price"];
            $dataa["order_id"] = $order['id'];
            $nn->data($dataa)->add();
        }

        $map["status"] = array('eq',2);
        $map["name"] = array('eq',I("post.shop_name"));
        $shop = D("ShopFile")->where($map)->find();
        // dump($shop);
        $ip = get_client_ip();
        $this->assign("ip",$ip);
        $this->assign("body_detail",$body_detail);
        $this->assign("order_id",$order["orderid"]);
        $this->assign("zhifu_money",$zhifu_money*100);
        $this->assign("shop",$shop);
        $this->assign("shop_name",I("post.shop_name"));
        $this->assign("total",I("post.total"));
        $this->display();
    }
    
    public function guid(){//生成唯一的uuid
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
                    .substr($charid, 0, 8).$hyphen
                    .substr($charid, 8, 4).$hyphen
                    .substr($charid,12, 4).$hyphen
                    .substr($charid,16, 4).$hyphen
                    .substr($charid,20,12)
                    .chr(125);// "}"
            $str = str_replace("{","",$uuid);
            $str = str_replace("}","",$str);
            $str = str_replace("-","",$str);
            return $str;
        }
    }

    public function order_update(){//钱包余额支付更新
        /*哈尔滨处理零钱的扣除*/
        $orderid = I("get.order_id");//订单号
        $pay = I("get.pay_money");//需要从钱包里扣除的金额
        //echo $orderid;
        // $map["orderid"] = $orderid;
        // $data["pay_status"] = 1;
        // M("order")->$where($map)->save($data);
		
		//客户充值的方法调用，当微支付异步通知调用后调用此接口。调用此接口分订餐、补餐两种类型
        $order = D("Order")->get(array("orderid" => $orderid));    //获取用户id
		$custId =$order['user_id']; 
		//得到开始时间和结束时间
		$timeBegin = 'SELECT p.order_date,s.totalprice,s.user_id,s.shop_id FROM multi_product p ,'.
					'(SELECT o.user_id,o.totalprice,d.product_id,o.shop_id    FROM   multi_order o ,'.
					'multi_order_detail d WHERE d.order_id=o.id AND o.orderid="'.$orderid.'") '.
					's WHERE p.id = s.product_id ORDER BY p.order_date LIMIT 1';
		$timeEnd = 'SELECT p.order_date,s.totalprice,s.user_id,s.shop_id FROM multi_product p ,'.
					'(SELECT o.user_id,o.totalprice,d.product_id,o.shop_id    FROM   multi_order o ,'.
					'multi_order_detail d WHERE d.order_id=o.id AND o.orderid="'.$orderid.'") '.
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
//		  /*
//      判断钱包里面的余额 start
//       */
//      $url="http://172.168.8.25:8080/jeecg/rest/CustFund/".$user_id;
//      //echo $url;
//      $result = $this->_request($url,true,'POST'); 
//      $result = json_decode($result);
//      //dump($result);
//      $money_s = $result[0]->resideMoney;//剩余资金     
//      $money=$totalprice-$money_s;
//						
			$orderType = 1;  //1:订餐，2：补餐
						
						
		//$money =  $pay;
		$openid = $_COOKIE['openid']; //openid
						
		//客户充值的方法调用
		$urlt = CONNECT."recharge/".$custId."/".$merchantId."/".$openid."/".$orderid."/".$orderType."/".$money."/".$beginDate."/".$endDate;
						
		$data = array(
					"orderid" => $orderid,
					"custid" => $custId,
					"merchantid" => $merchantId,
					"openid" => $openid,
					"money" => $money,
					"beginDate" => $beginDate,
					"endDate" => $endDate,
					"url" => $urlt,
					"flag"=>'1'
									
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
						
		//echo($result[0]->msg);													
		//客户充值的方法调用，当微支付异步通知调用后调用此接口。调用此接口分订餐、补餐两种类型 end						
        //echo "<br/>"."哈尔滨处理零钱的扣除";
        if($result[0]->success == 1){
			$dataor = array(
                    "pay_status" => 1
                );
            $neworder = M('Order')->where('orderid="'.$orderid.'"')->save($dataor);
			if($neworder > 0){
				echo '订单状态更新成功';
			}
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo  "<script>alert('支付成功！');window.location.href='".__ROOT__."/Phone/User/personal'</script>";
        }else{
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo  "<script>alert('支付失败！');window.location.href='".__ROOT__."/Phone/User/personal'</script>";
        }
		
    }


    public function cart_pay_again(){//从我的订单里面再次发起支付
        //dump(I("get."));
        $m = M("order");
        $map['orderid']=array('eq',I("get.order_id"));
        $order = $m->where($map)->find();
        $this->assign("order_id",$order['orderid']);
        $ip = get_client_ip();
        $this->assign("ip",$ip);
        $cong["id"] = $order["menu_id"];
        $name_detail = D("ShopMenu")->where($cong)->find();
        $body_detail = $name_detail["shop_name"].$name_detail["name"];
        $this->assign("body_detail",$body_detail);

/*****新增支付start*******/
        $login_name = cookie("username");
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $user = $Model->query("SELECT id FROM multi_sys_user WHERE login_name = '$login_name' limit 1");
        $user_id = $user[0]['id'];
        /*
        判断钱包里面的余额 start
         */
        $url="http://172.168.8.25:8080/jeecg/rest/CustFund/".$user_id;
        //echo $url;
        $result = $this->_request($url,true,'POST'); 
        $result = json_decode($result);
        //dump($result);
        $money_s = $result[0]->resideMoney;//剩余资金
        $money_d = $result[0]->payMoney;//已付金额
        /*
        判断钱包里面的余额 end
         */
        
        //查询此套餐的价格
        $mama["id"] = $order["menu_id"];
        $total_price = M("menu")->where($mama)->getField('price');
        $map_zou["menu_id"] = $order["menu_id"];
        $product1 = M("product")->where($map_zou)->select();

        $pro_count = count($product1);
        for($z=0;$z<$pro_count;$z++){//所有的商品配送日期改为时间戳
            $product1[$z]["order_date"] = strtotime($product1[$z]["order_date"]);
        }
        for($l=0;$l<$pro_count;$l++){//如果配送日期已过，则不显示
            if($product1[$l]["order_date"] > time()){
                $product1[$l]["order_date"] = date("Y-m-d",$product1[$l]["order_date"]);//时间戳换回日期格式
                $product[]=$product1[$l];
            }
        }
        $pro_count1 = count($product);
        $total_price = $total_price*$pro_count1;  

        //判断是否显示余额（钱包里有零钱，但不够）
        if($money_s != 0.00){
            if($total_price > $money_s){
                $this->assign("yue",$money_s);
            }
        }

        //查询对应的商店名和套餐名
        $mamm["id"] = $order["menu_id"];
        $name_detail = D("ShopMenu")->where($mamm)->find();
        $body_detail = $name_detail["shop_name"].$name_detail["name"];
        // dump($name_detail);

        //实际支付金额
        $difference = $total_price - $money_s;
        if($difference <= 0){
            $zhifu_money = 0;
        }else{
            $zhifu_money = $difference;
        }
        // echo $zhifu_money;

/*****新增支付end*******/

        $mapp['id'] = $order['shop_id'];
        $message = D("ShopFile")->where($mapp)->find();
        //dump($order);
        //dump($message);
        $this->assign("zhifu_money",$zhifu_money*100);
        $this->assign("total",$total_price);
        $this->assign("shop_name",$message["name"]);
        $this->assign("shop",$message);
        $this->display("User_cart_pay");
    }
	
    public function personal(){
        $login_name = cookie("username");
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $user = $Model->query("SELECT id,name,photo FROM multi_sys_user WHERE login_name = '$login_name' limit 1");
        $map["dyzh"] = array("eq",$user[0]["id"]);
        $name = M("xj_student")->where($map)->getField("xsxm");//找到学生的真实姓名
        $this->assign("user_name",$name);
        $this->assign("user",$user);
        $this->display();
    }


    public function order(){//我的订单
        $hh = I("get.user_id");
        $n = M("order");
        $map["user_id"] = array("eq",I("get.user_id"));
        $order_all = $n->where($map)->select();//未支付的订单
        $long = count($order_all);
        if($long == 0){
            $this->assign("nono",0);
            $this->display();
        }else{
            $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
            $order_w = $Model->query("SELECT multi_order.id,multi_shop.id AS shop_id,multi_order.menu_id,multi_order.orderid,multi_order.id,multi_order.pay_status,multi_order.time,multi_order.totalprice,multi_shop.name,multi_file.id AS file_id,multi_file.savename,multi_file.savepath FROM multi_order INNER JOIN multi_shop ON multi_order.shop_id = multi_shop.id INNER JOIN multi_file ON multi_shop.file_id = multi_file.id WHERE multi_order.user_id = '$hh' AND multi_order.pay_status = 0 ORDER BY id desc");;
            //dump($mmm);
            $order_j = $Model->query("SELECT multi_order.id,multi_shop.id AS shop_id,multi_order.menu_id,multi_order.id,multi_order.pay_status,multi_order.time,multi_order.totalprice,multi_shop.name,multi_file.id AS file_id,multi_file.savename,multi_file.savepath FROM multi_order INNER JOIN multi_shop ON multi_order.shop_id = multi_shop.id INNER JOIN multi_file ON multi_shop.file_id = multi_file.id WHERE multi_order.user_id = '$hh' AND multi_order.pay_status = 1 ORDER BY id desc");
            $order_y = $Model->query("SELECT multi_order.id,multi_shop.id AS shop_id,multi_order.menu_id,multi_order.pay_status,multi_order.time,multi_order.totalprice,multi_shop.name,multi_file.id AS file_id,multi_file.savename,multi_file.savepath FROM multi_order INNER JOIN multi_shop ON multi_order.shop_id = multi_shop.id INNER JOIN multi_file ON multi_shop.file_id = multi_file.id WHERE multi_order.user_id = '$hh' AND multi_order.pay_status = 2 ORDER BY id desc");
            
            $count_w = count($order_w);
            for($i=0;$i<$count_w;$i++){//未付款
                /********************根据套餐id找到订单id，再根据订单id从订单详情里面找到商品，最终拿到周期****************start***********/
                $map["menu_id"] = $order_w[$i]["menu_id"];
                $map["user_id"] = $hh;
                $order_id = M("order")->where($map)->getField("id");
                $map_zou['order_id'] = $order_id;
                $product_id_all = M("order_detail")->where($map_zou)->field("product_id")->select();
                $pro_count = count($product_id_all);
                for($j=0;$j<$pro_count;$j++){
                    $product_id[] = $product_id_all[$j]['product_id'];
                }
                $mmap_zou["id"] = array("in",$product_id);
                $product = M("product")->where($mmap_zou)->field("order_date")->select();
                $product_id = "";//清除上次循环的内容
                $num = count($product);
                $order_w[$i]["range"] = $product[0]["order_date"]." -- ".$product[$num-1]["order_date"];
            }


            $count_j = count($order_j);
            for($i=0;$i<$count_j;$i++){//进行中
                $n = M("shop");
                $mapp["id"] = $order_j[$i]["shop_id"];
                $shop = $n->where($mapp)->find();
                /********************根据套餐id找到订单id，再根据订单id从订单详情里面找到商品，最终拿到周期****************start***********/
                $map["menu_id"] = $order_j[$i]["menu_id"];
                $map["user_id"] = $hh;
                $order_id = M("order")->where($map)->getField("id");
                $map_zou['order_id'] = $order_id;
                $product_id_all = M("order_detail")->where($map_zou)->field("product_id")->select();
                $pro_count = count($product_id_all);
                for($j=0;$j<$pro_count;$j++){
                    $product_id[] = $product_id_all[$j]['product_id'];
                }
                $mmap_zou["id"] = array("in",$product_id);
                $product = M("product")->where($mmap_zou)->field("order_date")->select();
                $product_id = "";//清除上次循环的内容
                $num = count($product);
                $order_j[$i]["is_supple"] = $shop["is_supple"];
                $order_j[$i]["is_leave"] = $shop["is_leave"];
                $order_j[$i]["is_retreat"] = $shop["is_retreat"];
                $order_j[$i]["deadline"] = $shop["deadline"];
                $order_j[$i]["range"] = $product[0]["order_date"]." -- ".$product[$num-1]["order_date"];
            }


            $count_y = count($order_y);
            for($i=0;$i<$count_y;$i++){//已完成
                /********************根据套餐id找到订单id，再根据订单id从订单详情里面找到商品，最终拿到周期****************start***********/
                $map["menu_id"] = $order_y[$i]["menu_id"];
                $map["user_id"] = $hh;
                $order_id = M("order")->where($map)->getField("id");
                $map_zou['order_id'] = $order_id;
                $product_id_all = M("order_detail")->where($map_zou)->field("product_id")->select();
                $pro_count = count($product_id_all);
                for($j=0;$j<$pro_count;$j++){
                    $product_id[] = $product_id_all[$j]['product_id'];
                }
                $mmap_zou["id"] = array("in",$product_id);
                $product = M("product")->where($mmap_zou)->field("order_date")->select();
                $product_id = "";//清除上次循环的内容
                $num = count($product);
                $order_y[$i]["range"] = $product[0]["order_date"]." -- ".$product[$num-1]["order_date"];
            }
            /*
             在未付款(order_w)的订单中隐藏过期(不是今天的订单)的订单
            */
            $all_num = count($order_w);
            for($i=0;$i<$all_num;$i++){
                $arr = explode(" ",$order_w[$i]["time"]);
                if($arr[0] == date("Y-m-d", time())){
                    $order_ww[] = $order_w[$i];
                }
            }
            
            $this->assign("order_w",$order_ww);
            $this->assign("order_j",$order_j);
            $this->assign("order_y",$order_y);
            $this->assign("nono",1);
            $this->display();
        }
    }


    public function order_detail(){//订单详情
        $n = D("ShopFile");
        $mapp["id"] = I("get.shop_id");
        $savepath = $n->where($mapp)->getField('savepath');
        $savename = $n->where($mapp)->getField('savename');

        $mm = M("order");
        $maap["id"] = array("eq",I("get.order_id"));
        $order = $mm->where($maap)->find();

        $m = D("PrOrdt");
        $map["order_id"] = array("eq",I("get.order_id"));
        $order_detail = $m->where($map)->select();
        $num = count($order_detail);
        $range = $order_detail[0]["order_date"]." 至 ".$order_detail[$num-1]["order_date"];

        for($i=0;$i<$num;$i++){
            $order_detail[$i]["order_date"] = substr($order_detail[$i]["order_date"],5);//去除前面的年份
        }
        //dump($order);
        $nn = M("shop");
        $mmpp["id"] = I("get.shop_id");
        $shop = $nn->where($mmpp)->find();

        //学生学校班级信息
        $mmap["login_name"] = cookie("username");
        $user_message = D("UserAddress")->where($mmap)->find();

        $this->assign("user_message",$user_message);
        $this->assign("range",$range);
        $this->assign("order",$order);
        $this->assign("savepath",$savepath);
        $this->assign("savename",$savename);
        $this->assign("shop",$shop);
        $this->assign("order_detail",$order_detail);
        $this->assign("total",count($order_detail)*$order_detail[0]['price']);
        $this->display();

    }


    public function packet(){//我的钱包
        $login_name = cookie("username");
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $user = $Model->query("SELECT id,name,photo FROM multi_sys_user WHERE login_name = '$login_name' limit 1");
        $user_id = $user[0]['id'];
        $url="http://172.168.8.25:8080/jeecg/rest/CustFund/".$user_id;
        //echo $url;
        $result = $this->_request($url,true,'POST'); 
        $result = json_decode($result);
        //dump($result);
        $money_s = $result[0]->resideMoney;//剩余资金
        $money_d = $result[0]->payMoney;//已付金额
        $this->assign("money_s",$money_s);
        $this->assign("money_d",$money_d);
        $this->display();
    }

    public function packet_xi(){//交易明细
        $login_name = cookie("username");
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $user = $Model->query("SELECT id,name,photo FROM multi_sys_user WHERE login_name = '$login_name' limit 1");
        $user_id = $user[0]['id'];
        
        $mingxi = $Model->query("
        SELECT
            multi_order.id AS id,
            multi_order.time AS time,
            multi_menu.id AS menu_id,
            multi_menu.name AS menu_name
        FROM
            multi_order INNER JOIN
          multi_menu ON multi_order.menu_id = multi_menu.id
        WHERE
            `user_id` = '$user_id'
        AND `pay_status` != 0");

        $long = count($mingxi);
        for($i=0;$i<$long;$i++){
            $map["menu_id"] = array("eq",$mingxi[$i]["menu_id"]);
            $product = M("product")->where($map)->select();
            $mingxi[$i]["price"] = count($product)*$product[0]["price"];
        }
        $this->assign("mingxi",$mingxi);
        $this->display();
    }

    public function _request($curl,$https = true,$method='GET',$data = null){
        //echo $curl;exit;
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


    public function news(){//消息
        $login_name = cookie("username");
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $user = $Model->query("SELECT id,name,photo FROM multi_sys_user WHERE login_name = '$login_name' limit 1");
        $user_id = $user[0]['id'];
        $map["user_id"] = $user_id;
        $map["apply_time"] = array("neq",'');
        
        $news = M("order_detail")->where($map)->where('leave_status != 2 AND leave_status != -1 AND retreat_status != 2 AND retreat_status != -1')->select();
        $long = count($news);
        for($i=0;$i<$long;$i++){
            $mmap['id'] = array("eq",$news[$i]["order_id"]);
            $news[$i]["orderid"] = M("order")->where($mmap)->getField("orderid");
            $news[$i]["desc"] = strtotime($news[$i]["time"]);//日期转化为时间戳存入desc
        }
        for($i=0;$i<$long;$i++){//冒泡排序
            for($j=0;$j<$long;$j++){
                if ($news[$j]["desc"] < $news[$j + 1]["desc"]) {
                    $temp = $news[$j];
                    $news[$j] = $news[$j + 1];
                    $news[$j + 1] = $temp;
                }
            }
        }
        $this->assign("news",$news);
        $this->display();
    }


    public function shop_detail(){//店铺详情
        $shop = $this->test(I("get.shop_id"));
        $this->assign("shop",$shop);
        $this->display();
    }

    public function leave(){//请假
        $login_name = cookie("username");
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $user = $Model->query("SELECT id,name,photo FROM multi_sys_user WHERE login_name = '$login_name' limit 1");
        $user_id = $user[0]['id'];

        $map["id"] = I("get.menu_id");
        $menu = M("menu")->where($map)->find();
        $mapp["id"] = I("get.order_id");
        $order = M("order")->where($mapp)->find();
        $mappp["order_id"] = I("get.order_id");
        $order_detail = D("DetailPro")->where($mappp)->select();
        $numm = count($order_detail);

        $mmaa['id'] = array("eq",$menu['shop_id']);
        $deadline = M("shop")->where($mmaa)->getField('deadline');
/*配送周期start*/
        $mmap["menu_id"] = I("get.menu_id");
        $mmap["user_id"] = $user_id;
        $order_id = M("order")->where($mmap)->getField("id");
        $map_zou['order_id'] = $order_id;
        $product_id_all = M("order_detail")->where($map_zou)->field("product_id")->select();
        $pro_count = count($product_id_all);
        for($j=0;$j<$pro_count;$j++){
            $product_id[] = $product_id_all[$j]['product_id'];
        }
        $mmap_zou["id"] = array("in",$product_id);
        $product = M("product")->where($mmap_zou)->field("order_date")->select();
        $num = count($product);
        $range = $product[0]["order_date"]." 至 ".$product[$num-1]["order_date"];
/*配送周期end*/
        // dump($order_detail);
        // die();
        for($i=0;$i<$numm;$i++){
            if($order_detail[$i]["is_day"] == 1){//截止日期是当天的
                if(strtotime($order_detail[$i]["order_date"].$deadline) < time()){
                    $order_detail[$i]["selected"] = 1;//不能请假
                }else{
                    $order_detail[$i]["selected"] = 0;//可以请假
                }
            }else{//截止日期是前一天的
                if(strtotime($order_detail[$i]["order_date"].$deadline) < time()+24*3600){
                    $order_detail[$i]["selected"] = 1;//不能请假
                }else{
                    $order_detail[$i]["selected"] = 0;//可以请假
                }
            }
        }
        
        $this->assign("range",$range);
        $this->assign("order_detail",$order_detail);
        $this->assign("menu",$menu);
        $this->assign("order",$order);
        $this->assign("product",$product);
        $this->display();
    }

    public function test($shop_id){//单纯为了配合店铺详情测试
        $m = D('ShopFile');
        $map['id'] = $shop_id;
        $shop = $m->where($map)->find();
        return $shop;
    }

    public function leave_update(){//请假提交
        $arr = array_values(I("post."));
        //dump($arr);
        if(I("post.") == null){
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo  "<script>alert('您还没有选择！');javascript:history.go(-1);</script>";
        }else{
            /*查看店铺是否允许老师审批*/
            $m = M("order_detail")->getByid($arr[0]);
            $n = M("order")->getByid($m["order_id"]);
            $h = M("shop")->getByid($n["shop_id"]);
            
            $map['id'] = array("in",$arr);

            /*
            if($h["is_examine"] == '0'){
                $data['leave_status'] = -1;
                $data['apply_time'] = date("Y-m-d H:i:s", time());
            }
            if($h["is_examine"] == '1'){
                $data['leave_status'] = 2;
                $data['apply_time'] = date("Y-m-d H:i:s", time());
            }
            if(M("order_detail")->where($map)->save($data)){
                echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                echo  "<script>alert('请假申请已提交，请耐心等待！');window.location.href='/ordering/Phone/User/personal'</script>";
            }else{
                echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                echo  "<script>alert('请勿重复提交！');javascript:history.go(-1);location.reload()</script>";
            }
            2016-12-23*/

            //当无退餐时，可以请假
            if($m['retreat_status'] == 0 && $m['leave_status'] == 0){
                if($h["is_examine"] == '0'){
                    $data['leave_status'] = -1;
                    $data['apply_time'] = date("Y-m-d H:i:s", time());
                }
                if($h["is_examine"] == '1'){
                    $data['leave_status'] = 2;
                    $data['apply_time'] = date("Y-m-d H:i:s", time());
                }
                if(M("order_detail")->where($map)->save($data)){
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                    echo  "<script>alert('请假申请已提交，请耐心等待！');window.location.href='/ordering/Phone/User/personal'</script>";
                }else{
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                    echo  "<script>alert('请勿重复提交！');javascript:history.go(-1);location.reload()</script>";
                }
            }else{
                //有退餐操作，不允许请假
                echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                echo  "<script>alert('有退餐，请不要请假！');javascript:history.go(-1);location.reload()</script>";
            }
            
        }
    }



    public function order_cancel(){//退餐
        $map["id"] = I("get.menu_id");
        $menu = M("menu")->where($map)->find();
        $mapp["id"] = I("get.order_id");
        $order = M("order")->where($mapp)->find();
        $mappp["order_id"] = I("get.order_id");
        $order_detail = D("DetailPro")->where($mappp)->select();
        $numm = count($order_detail);

        $mmaa['id'] = array("eq",$menu['shop_id']);
        $deadline = M("shop")->where($mmaa)->getField('deadline');
        for($i=0;$i<$numm;$i++){
            if($order_detail[$i]["is_day"] == 1){
                if(strtotime($order_detail[$i]["order_date"].$deadline) < time()){//判断是否可以退餐
                    $order_detail[$i]["selected"] = 0;
                }else{
                    $order_detail[$i]["selected"] = 1;
                }
            }else{
                if(strtotime($order_detail[$i]["order_date"].$deadline) < time()+24*3600){//判断是否可以退餐
                    $order_detail[$i]["selected"] = 0;
                }else{
                    $order_detail[$i]["selected"] = 1;
                }
            }
        }
        
        for($j=0;$j<$numm;$j++){
            if($order_detail[$j]["selected"] == 1){//可以退餐
                /*
                if($order_detail[$j]["leave_status"] == 0){//没有请假
                    $arr[] = $order_detail[$j]['id'];
                }
                2016-12-22*/
                //没有请假 没有退餐
                if($order_detail[$j]["leave_status"] == 0 && $order_detail[$j]["retreat_status"] == 0){
                    $arr[] = $order_detail[$j]['id'];
                }
            }
        }
        if(count($arr) == 0){
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo  "<script>alert('已经不能退餐了！');javascript:history.go(-1);</script>";
        }else{
            $m = M("order_detail")->getByid($arr[0]);
            $n = M("order")->getByid($m["order_id"]);
            $h = M("shop")->getByid($n["shop_id"]);

            $map['id'] = array("in",$arr);
            if($h["is_examine"] == '1'){
                $data['retreat_status'] = 2;
                $data['apply_time'] = date("Y-m-d H:i:s", time());
            }

            if($h["is_examine"] == '0'){
                $data['retreat_status'] = -1;
                $data['apply_time'] = date("Y-m-d H:i:s", time());
            }
            
            if(M("order_detail")->where($map)->save($data)){
                echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                echo  "<script>alert('退餐申请已提交，请耐心等待！');window.location.href='/ordering/Phone/User/personal'</script>";
            }else{
                echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                echo  "<script>alert('请勿重复提交！');javascript:history.go(-1);location.reload()</script>";
            }
        }
    }

    public function comment(){//订单评论
        $login_name = cookie("username");
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $user = $Model->query("SELECT id,name,photo FROM multi_sys_user WHERE login_name = '$login_name' limit 1");

        $mapp["order_id"] = I("get.order_id");
        $detail_id = M("order_detail")->where($mapp)->field("id")->select();
        $long = count($detail_id);
        for($i=0;$i<$long;$i++){
            $arr[] = $detail_id[$i]["id"];
        }
        
        $map["user_id"] = $user[0]["id"];
        $map["detail_id"] = array("in",$arr);
        $comment = M("comment")->where($map)->select();
        if(count($comment) == 0){//判断当前是否有评论
            $this->assign("nono",1);//没有评论传1进行判断
        }
        $this->assign("comment",$comment);
        $this->assign("order_id",I("get.order_id"));
        $this->assign("user",$user);
        $this->display();
    }

    public function comment_update(){//订单评论提交更新
        $order_id = I("post.order_id");
        $map["order_id"] = $order_id;
        $map["leave_status"] = 0;
        $map["retreat_status"] = 0;
        $order_detail = D("DetailPro")->where($map)->select();
        $long = count($order_detail);
        for($i=0;$i<$long;$i++){
            $date = strtotime($order_detail[$i]["order_date"])+3600*24;
            if($date < time()){
                $arr[] = $order_detail[$i];//可以评价的
            }
        }

        $kp_num = count($arr);//可以评价的个数
        $maap["order_id"] = $order_id;
        $maap["leave_status"] = 0;
        $maap["retreat_status"] = 0;
        $detail_id = M("order_detail")->where($maap)->field("id")->select();
        $longtwo = count($detail_id);
        for($i=0;$i<$longtwo;$i++){
            $arrtwo[] = $detail_id[$i]["id"];
        }

        if(count($arrtwo) != 0){
            $mapp["detail_id"] = array("in",$arrtwo);
            $comment_num = M("comment")->where($mapp)->count();//已评价个数
        }else{
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo  "<script>alert('现在不能评价！');javascript:self.location=document.referrer;</script>";
        }
        
        $login_name = cookie("username");
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $user = $Model->query("SELECT id,name,photo FROM multi_sys_user WHERE login_name = '$login_name' limit 1");

        if($kp_num > $comment_num){
            $data['name'] = I("post.comment");
            $data['time'] = date('Y-m-d H:i:s',time());
            $data['detail_id'] = $arr[$comment_num]['id'];
            $data['user_id'] = $user[0]['id'];
            $data['shop_id'] = $arr[0]["shop_id"];
            $data['user_name'] = $user[0]['name'];
            if(M("comment")->data($data)->add()){
                echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                echo  "<script>alert('评论成功！');javascript:self.location=document.referrer;</script>";
            }
        }else{
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo  "<script>alert('现在不能评价！');javascript:self.location=document.referrer;</script>";
        }
    }

    public function menu_select(){//进入商店后，选择不同的套餐
        $menu_id = I("post.menu_id");
        $map["menu_id"] = array("eq",$menu_id);
        $m = D("ProFile");
        $products = $m->where($map)->select();


        $pro_count = count($products);
        for($z=0;$z<$pro_count;$z++){//所有的商品配送日期改为时间戳
            $products[$z]["order_date"] = strtotime($products[$z]["order_date"]);
        }
        for($l=0;$l<$pro_count;$l++){//如果配送日期已过，则不显示
            if($products[$l]["order_date"] > time()){
                $products1[]=$products[$l];
            }
        }

        $mun = count($products1);
        for($j=0;$j<$mun;$j++){//商品的配送日期转化为星期
            $time = $products1[$j]["order_date"];
            $lib = date('w',$time);
            $weekarray=array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");
            $products1[$j]["week"] = $weekarray[$lib];
            $products1[$j]["order_date"] = date("Y-m-d",$products1[$j]["order_date"]);//时间戳换回日期格式
        }
        $zhouqi = $products1[0]["order_date"]." 至 ".$products1[$mun-1]["order_date"];
        $string = "";
        for($i=0;$i<$mun;$i++){
            $string = $string.' <ul>
                                    <li>
                                        <div class="liTop">
                                            &nbsp;&nbsp;'.$products1[$i]["order_date"].'  '.$products1[$i]["week"].'
                                        </div>
                                        <div class="liFoot">
                                            <a class="example" href="/ordering/Public/Uploads/'.$products1[$i]["savepath"].$products1[$i]["savename"].'"><img width="75px" height="75px" src="/ordering/Public/Uploads/'.$products1[$i]["savepath"].$products1[$i]["savename"].'"></a>
                                            <div class="tcInfo">
                                                <br>'.$products1[$i]["name"].'
                                            </div>
                                        </div>
                                    </li>
                                </ul>';
        }
        $n = M("menu");
        $mapp["id"] = $menu_id;
        $menu = $n->where($mapp)->find();
        $string = $string."***".$mun."***".$menu['name']."***".$zhouqi;
        $this->ajaxReturn($string);
    }


    public function update(){//判断订单是否已经完成
        ignore_user_abort(); // 函数设置与客户机断开是否会终止脚本的执行
        set_time_limit(0); // 来设置一个脚本的执行时间为无限长
        $interval=10;
        do{
            $login_name = cookie("username");
            $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
            $user = $Model->query("SELECT id FROM multi_sys_user WHERE login_name = '$login_name' limit 1");
            $user_id = $user[0]['id'];
            $m = M("order");
            $map["user_id"] = $user_id;
            $map['pay_status'] = 1;
            $order = $m->where($map)->select();
            //dump($order);
            $long = count($order);
            for($i=0;$i<$long;$i++){
                //echo $order[$i]["menu_id"]."<br/>";
                $n = M("product");
                $mapp["menu_id"] = $order[$i]["menu_id"];
                $final = $n->where($mapp)->order('id desc')->limit(1)->getField("order_date");
                $final = strtotime($final)+3600*13+60*46;
                if(time() > $final){
                    $mma["user_id"] = $user_id;
                    $mma["menu_id"] = $order[$i]["menu_id"];
                    $data["pay_status"] = 2;
                    $m->where($mma)->save($data);
                }
                //echo $final."<br/>";
            }
            sleep($interval); // 函数延迟代码执行若干秒
        }while(true);
    }
    
}