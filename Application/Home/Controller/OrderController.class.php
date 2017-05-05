<?php
namespace Home\Controller;
set_time_limit(0);

class OrderController extends BaseController
{
    public function order()
    {
        $num = 20;
        $p = I("get.page") ? I("get.page") : 1;
        cookie("prevUrl", "Home/Order/order/page/" . $p);


        $condition = array(
            "shop_id" => session("homeShopId")
        );

        $data = I("get.");
        if ($data["status"] != "") {
            array_push($condition, array(
                "status" => $data["status"]
            ));
        }
        if ($data["pay_status"] != "") {
            array_push($condition, array(
                "pay_status" => $data["pay_status"]
            ));
        }
        if ($data["day"] != "") {
            array_push($condition, array(
                "time" => array("like", $data["day"] . "%")
            ));
        }

        $orderList = D("Order")->getOrderList($condition, true, "id desc", $p, $num);
        foreach ($orderList as $k => $val) {
            $where['dyzh'] = $val['user_id'];
            //学生信息 真实姓名
            $student = D('XjStudent')->where($where)->field('dyzh,xsxm,bj')->find();
            $orderList[$k]['student'] = $student;

            //班级
            $cwh['id'] = $student['bj'];
            $cwh['del_flag'] = '0';
            $class = M('XjClass')->where($cwh)->field('id,del_flag,bjmc,bh,master_id,school_id')->find();
            $orderList[$k]['class'] = $class;
        }

        $this->assign("orderList", $orderList);

        $count = D("Order")->getOrderListCount($condition);// 查询满足要求的总记录数
        $Page = new \Think\Page($count, $num);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show();// 分页显示输出
        $this->assign('page', $show);// 赋值分页输出

        $productList = D("Product")->getProductList(array("shop_id" => session("homeShopId")), true);
        $this->assign('productList', $productList);// 赋值分页输出

        $this->display();
    }

    public function search()
    {
        $condition = array(
            "shop_id" => session("homeShopId")
        );

        if (I("post.id") && !empty(I("post.id"))) {
            array_push($condition, array("id" => I("post.id")));
        }
        if (I("post.orderid") && !empty(I("post.orderid"))) {
            array_push($condition, array("orderid" => I("post.orderid")));
        }
        if (I("post.student_xsxm") && I("post.student_xsxm") != null) {
            $where['xsxm'] = I("post.student_xsxm");
            //学生信息 真实姓名
            $student = D('XjStudent')->where($where)->field('dyzh,xsxm')->select();

            $dyzh = '';
            foreach($student as $sk=>$sv){
                $dyzh.=$sv['dyzh'].",";
            }
            $dyzh = rtrim($dyzh,',');
            array_push($condition, array("user_id" => array('in',$dyzh)));
        }
        if(I('post.remark') != -10 && I("post.remark") != null){
            if(I('post.remark') == 1){
                array_push($condition, array('_string'=>'remark is not null and `remark` <> ""'));  //备注不为空
            }else if(I('post.remark') == 0){
                array_push($condition, array('_string'=>'remark is null'));  //备注为空
            }
        }
        if (I("post.payment") != -10 && I("post.payment") != null) {
            array_push($condition, array("payment" => I("post.payment")));
        }
        if (I("post.pay_status") != -10 && I("post.pay_status") != null) {
            array_push($condition, array("pay_status" => I("post.pay_status")));
        }
        if (I("post.status") != -10 && I("post.status") != null) {
            array_push($condition, array("status" => I("post.status")));
        }

        if (I("post.timeRange") && !empty(I("post.timeRange"))) {
            $timeRange = I("post.timeRange");
            $timeRange = explode(" --- ", $timeRange);
            array_push($condition, array("time" => array('between', array($timeRange[0], $timeRange[1]))));
        }
       

        $orderList = D("Order")->getOrderList($condition, true, "class_id asc,id desc");
        foreach ($orderList as $k => $val) {
            $where['dyzh'] = $val['user_id'];
            //学生信息 真实姓名
            $student = D('XjStudent')->where($where)->field('dyzh,xsxm,bj')->find();
            $orderList[$k]['student'] = $student; 

            //班级
            $cwh['id'] = $student['bj'];
            $cwh['del_flag'] = '0';
            $class = M('XjClass')->where($cwh)->field('bjmc,bh,master_id,school_id')->find();
            $orderList[$k]['class'] = $class;
        }
        
        if (I("post.product_id") != -10 && !empty(I("post.product_id"))) {
            foreach ($orderList as $key => $value) {
                $flag = true;
                foreach ($value["detail"] as $k => $v) {
                    if ($v["product_id"] == I("post.product_id")) {
                        $flag = false;
                        break;
                    }
                }

                if ($flag) {
                    unset($orderList[$key]);
                }
            }
        }

        $productList = D("Product")->getProductList(array(), true);
        $this->assign('productList', $productList);// 赋值分页输出

        $this->assign("orderPost", I("post."));
        $this->assign("orderList", $orderList);
        $this->display("order");
    }

    public function update()
    {
        $data = I("get.");
        $id = $data["id"];
        unset($data["id"]);
        D("Order")->updateAllOrder($id, $data);

        //发货通知
        if (I("get.status") == 1) {
            $ids = explode(",", I("get.id"));
            
            $orderModel = D("Order");
            foreach ($ids as $key => $value) {
                $order = $orderModel->getOrder(array("id" => $value));
                
                $getUrl = "http://" . I("server.HTTP_HOST") . U("Admin/Wechat/sendTplMsgDeliver", array("order_id" => $value, "shopId" => $order["shop_id"]));
                // 先暂时注销
                // http_get($getUrl);
            }
        } elseif (I("get.status") == 2) {
            $orders = D("Order")->getOrderList(array("id" => array("in", $id)));
            foreach ($orders as $key => $value) {
                if ($value["payment"] == 3) {
                    D("Order")->where(array("id" => $value["id"]))->save(array("pay_status" => 1));
                }
            }
        }

        $this->success("操作成功", cookie("prevUrl"));
    }

    public function export()
    {
        $condition = array(
            "shop_id" => session("homeShopId")
        );

        $data = I("get.");
        if ($data["status"] != "") {
            array_push($condition, array(
                "status" => $data["status"]
            ));
        }
        if ($data["get.pay_status"] != "") {
            array_push($condition, array(
                "pay_status" => $data["pay_status"]
            ));
        }
        if ($data["day"] != "") {
            array_push($condition, array(
                "time" => array("like", $data["day"] . "%")
            ));
        }
        if ($data["id"] != "") {
            array_push($condition, array(
                "id" => array("in", $data["id"])
            ));
        }

        $order = D('Order')->getOrderList($condition, true, "id desc");
        foreach ($order as $key => $value) {
            unset($order[$key]["contact"]);
            unset($order[$key]['menu_id']);
            unset($order[$key]['delivery_time']);
            //支付状态
            switch ($order[$key]['pay_status']) {
                case '0':
                    $order[$key]['pay_status'] = "未付款";
                    break;
                case '1':
                    $order[$key]['pay_status'] = "已付款";
                    break;
                case '2':
                    $order[$key]['pay_status'] = "已完成";
                    break;
                default:
                    break;
            }

            $detail = '';
            foreach ($value["detail"] as $k => $v) {
                $detail .= $v["name"] . "[数量:" . $v["num"] . "]" . "[价格:" . $v["price"] . "],";
            }
            $order[$key]["detail"] = rtrim($detail,',');
            
            $order[$key]['shop'] = $value['shop']['name'];

            $where['dyzh'] = $value['user_id'];
            //学生信息 真实姓名
            $student = D('XjStudent')->where($where)->field('xsxm')->find();
            $order[$key]["sysuser"] = $student['xsxm'];

            unset($order[$key]['user_id']);
            unset($order[$key]['payment']);
            unset($order[$key]['status']);
            unset($order[$key]['class_id']);
            unset($order[$key]['menu']);    
        }

        Vendor("PHPExcel.Excel#class");
        \Excel::export($order, array('订单ID', '店铺ID', '订单编号', '总价格', '支付状态', '运费', '折扣', '备注', '时间', '用户','订单详情', '店铺'));
    }

    public function wxPrint()
    {
        $ids = explode(",", I("get.id"));
        foreach ($ids as $key => $value) {
            wxPrint($value);
        }

        $this->success("操作成功", cookie("prevUrl"));
    }

    public function productlist(){
        $condition = array(
            "order_id" => I('post.order_id')
        );
        $orderdetail = D('OrderDetail')->getList($condition,true);
        echo json_encode($orderdetail);
    }

    //订单详情
    public function detail(){
        $num = 20;
        $p = I("get.page") ? I("get.page") : 1;
        cookie("prevUrl", "Home/Order/detail/page/" . $p);

        $condition = array();
        $wh = '';
        $wh = "multi_order.shop_id = '".session("homeShopId")."' and ";

        $data = I("get.");
        if ($data["leave_status"] != "") {
            array_push($condition, array(
                "leave_status" => $data["leave_status"]
            ));
            $wh.=" multi_order_detail.leave_status = '".$data["leave_status"]."'";
        }
        if ($data["retreat_status"] != "") {
            array_push($condition, array(
                "retreat_status" => $data["retreat_status"]
            ));
             $wh.=" multi_order_detail.retreat_status = '".$data["retreat_status"]."'";
        }
        //订单详情分页
        $orderdetail = M('OrderDetail')->join(' left join multi_order on multi_order.id = multi_order_detail.order_id')->where($wh)->order('multi_order_detail.id desc,multi_order_detail.apply_time desc')->page($p . ',' . $num . '')->field(array('multi_order_detail.id'=>'detail_id','multi_order.orderid','multi_order_detail.time','multi_order_detail.name','multi_order_detail.apply_time','multi_order_detail.user_id','multi_order_detail.product_id','multi_order_detail.leave_status','multi_order_detail.retreat_status'))->select();

        foreach ($orderdetail as $k => $val) {
            //学生姓名
            $stuwhere['dyzh']=$val['user_id'];
            $student = D('XjStudent')->where($stuwhere)->field('id,dyzh,xsxm,bj')->find();
            $orderdetail[$k]['student'] = $student;
            //班级
            $cwh['id'] = $student['bj'];
            $cwh['del_flag'] = '0';
            $class = M('XjClass')->where($cwh)->field('id,del_flag,bjmc,bh,master_id,school_id')->find();
            $orderdetail[$k]['class'] = $class;
            //商品
            $prwhere['shop_id']=session("homeShopId");
            $prwhere['id']=$val['product_id'];
            $product = D('Product')->where($prwhere)->field('order_date,price')->find();
            $orderdetail[$k]['product'] = $product;
            
        }
    
        $this->assign('data',$data);
        $this->assign('orderdetailList',$orderdetail);

        $count = M('OrderDetail')->join(' left join multi_order on multi_order.id = multi_order_detail.order_id')->where($wh)->count(); // 查询满足要求的总记录数
        $Page = new \Think\Page($count, $num);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show();// 分页显示输出
        $this->assign('page', $show);// 赋值分页输出

        $this->display();
    }

    public function detailSearch(){
        $condition = array();
        $data = I("get.");
        if ($data["leave_status"] != "") {
            array_push($condition, array(
                "leave_status" => $data["leave_status"]
            ));
        }
        if ($data["retreat_status"] != "") {
            array_push($condition, array(
                "retreat_status" => $data["retreat_status"]
            ));
        }
        if (I("post.student_xsxm") && !empty(I("post.student_xsxm"))) {
            $where['xsxm'] = I("post.student_xsxm");
            //学生信息 真实姓名
            $student = D('XjStudent')->where($where)->field('dyzh,xsxm')->select();
            $dyzh = '';
            foreach($student as $sk=>$sv){
                $dyzh.=$sv['dyzh'].",";
            }
            $dyzh = rtrim($dyzh,',');
            array_push($condition, array("user_id" => array('in',$dyzh)));
        }
        $orderDetailList = D("OrderDetail")->getList($condition, true, "id desc");      

        foreach ($orderDetailList as $k => $val) {
            $where['dyzh'] = $val['user_id'];
            //学生信息 真实姓名
            $student = D('XjStudent')->where($where)->field('dyzh,xsxm,bj')->find();
            $orderDetailList[$k]['student'] = $student;
            //班级
            $cwh['id'] = $student['bj'];
            $cwh['del_flag'] = '0';
            $class = M('XjClass')->where($cwh)->field('id,bjmc,bh,master_id,school_id')->find();
            $orderDetailList[$k]['class'] = $class;

           if($val['order']['shop_id'] != session("homeShopId")){
                unset($orderDetailList[$k]);
           } 
        }
        
        $this->assign('data',$data);
        $this->assign("orderPost", I("post."));
        $this->assign("orderdetailList", $orderDetailList);
        $this->display("Order_detail");
    }

    //确认请假
    public function leaveyes(){
        $id = I('post.id');
        if(!empty($id)){
            $data = array();
            //已请假
            $data['leave_status'] = '1';
            $condition = array();
            $condition['id'] = $id;

            $cont = M('OrderDetail')->where('id='.$id)->find();
            //状态为请假申请中并且无退餐
            if(($cont['leave_status'] == -1 || $cont['leave_status'] == 2) && $cont['retreat_status'] == 0){
                $re = D('OrderDetail')->where('id='.$id)->save($data);

                $dsql = "select 
                        od.user_id,od.order_id,od.price,oo.orderid,pp.order_date,pp.id
                        from __PREFIX__order_detail as od 
                        left join __PREFIX__order as oo on oo.id = od.order_id
                        left join __PREFIX__product as pp on pp.id = od.product_id
                        where od.id=".$id."
                        limit 1";
                $detail = M()->query($dsql);
                $detail = $detail[0];

                $url = CONNECT."leave/".$detail['user_id']."/".$detail['orderid']."/".$detail['price']."/".$detail['order_date']."/".$detail['order_date'];
                $result = $this->_request($url,true,'POST');
                $result = json_decode($result);

                //请假接口返回数据
                $rdw['orderdetail_id'] = $id;
                $rdw['orderid'] =  $detail['orderid'];
                $rdw['price'] = $detail['price'];
                $rdw['order_date'] = $detail['order_date'];
                $rdw['product_id'] = $detail['id'];
                $rdw['user_id'] = $detail['user_id'];
                $rdw['url'] = $url;
                $rdw['result']= $result[0]->msg;
                $rdw['time'] = date('Y-m-d H:i:s',time());
                M('LeaveDetail')->add($rdw);

                if($result[0]->success == '1'){
                    echo  1;
                }else{
                    if($re){
                        $fd = array();
                        $fd['leave_status'] = '-1';
                        M('OrderDetail')->where('id='.$id)->save($fd);
                    }

                    echo  $result[0]->msg;
                }
            }else{
                echo "不符合请假条件！";
            }
            
        }
    }

    //拒绝请假
    public function leaveno(){
        if(!empty(I('post.'))){
            $remark = trim(I('post.remark'));
            $id = I('post.id');
            //拒绝
            $data['leave_status'] = '0';
            $data['remark'] = date('Y-m-d H:i:s',time()).' '.$remark;
            D('OrderDetail')->where('id='.$id)->save($data);
        }
    }

    //确认退餐
    public function retreatyes(){
        $id = I('post.id');
        if(!empty($id)){
            $data = array();
            //已退餐
            $data['retreat_status'] = '1';

            $condition = array();
            $condition['id'] = $id;

            $cont = M('OrderDetail')->where('id='.$id)->find();
            //状态为退餐申请中并且无请假
            if($cont['leave_status'] == 0 && ($cont['retreat_status'] == -1 || $cont['retreat_status'] == 2)){
                $dsql = "select 
                        od.user_id,od.order_id,od.price,oo.orderid,pp.order_date,pp.id
                        from __PREFIX__order_detail as od 
                        left join __PREFIX__order as oo on oo.id = od.order_id
                        left join __PREFIX__product as pp on pp.id = od.product_id
                        where od.id=".$id."
                        limit 1";
                $detail = M()->query($dsql);
                $detail = $detail[0];

                $sql = "select count(*) as counter from  multi_order_detail a where a.order_id='".$detail['order_id']."' and a.retreat_status=-1";
                $num = M()->query($sql);

                $re = M('OrderDetail')->where('id='.$id)->save($data);
                if($re == 1){
                    if($num[0]['counter'] == 1){
                        $odata['pay_status'] = '2';  //订单已完成
                        M('Order')->where('id='.$detail['order_id'])->save($odata);
                    }
                }

                $url = CONNECT."returns/".$detail['user_id']."/".$detail['orderid']."/".$detail['price']."/".$detail['order_date']."/".$detail['order_date'];
                $result = $this->_request($url,true,'POST');
                $result = json_decode($result);

                $rdw['rmeals_id'] = -1; //单独退餐
                $rdw['orderdetail_id'] = $id;
                $rdw['orderid'] =  $detail['orderid'];
                $rdw['price'] = $detail['price'];
                $rdw['order_date'] = $detail['order_date'];
                $rdw['product_id'] = $detail['id'];
                $rdw['user_id'] = $detail['user_id'];
                $rdw['url'] = $url;
                $rdw['result']= $result[0]->msg;
                $rdw['time'] = date('Y-m-d H:i:s',time());
                M('RmealsDetail')->add($rdw);   

                if($result[0]->success == '1'){               
                    echo '1';
                }else{
                    if($re == 1){
                        $fd = array();
                        $fd['retreat_status'] = '-1';
                        $re = M('OrderDetail')->where('id='.$id)->save($fd);
                    }
                    echo $result[0]->msg;
                }
            }else{
                echo "不符合退餐条件！";
            }

            
       
        }
    }

    //拒绝请假
    public function retreatno(){
        if(!empty(I('post.'))){
            $remark = trim(I('post.remark'));
            $id = I('post.id');
            //拒绝
            $data['retreat_status'] = '0';
            $data['remark'] = date('Y-m-d H:i:s',time()).' '.$remark;
            D('OrderDetail')->where('id='.$id)->save($data);
        }
    }


    //批量退餐
    public function rmeals(){
        $num = 25;
        $p = I("get.page") ? I("get.page") : 1;
        cookie("prevUrl", "Home/Order/rmeals/page/" . $p);

        $shop_id = session("homeShopId");
        $sql = 'SELECT ss.company_id,ss.shop_id,s.xxmc FROM multi_school_shop ss,multi_xj_school s '. 
                        'WHERE ss.company_id=s.id AND ss.shop_id= '.$shop_id;
        //学校列表
        $schools = M()->query($sql);

        if(!empty(I('post.'))){
            $condition=array();
            $condition['shop_id']=$shop_id;
            if(!empty(I('post.school'))){
                $condition['school_id']=I('post.school');
            }
            if(!empty(I('post.class'))){
                $condition['class_id']=I('post.class');
            }
            if(!empty(I('post.time'))){
                $ptime=explode(' --- ',I('post.time'));
                $ptime[0]=$ptime[0]." 00:00:00";  //起始时间设为零点
                $ptime[1]=$ptime[1]." 23:59:59";  //结尾时间设为那天的最后1s
                $time=$ptime[0].",".$ptime[1];
                $condition['time']=array('between',$time);
            }
            $rmeals = D('Rmeals')->getList($condition,true,"id desc", $p, $num);
            $this->assign('rmeals',$rmeals);

            $count = D("Rmeals")->getListCount($condition);// 查询满足要求的总记录数
            $Page = new \Think\Page($count, $num);// 实例化分页类 传入总记录数和每页显示的记录数
            $Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
            $show = $Page->show();// 分页显示输出
            $this->assign('page', $show);// 赋值分页输出
        }else{
            $condition=array();
            $condition['shop_id']=$shop_id;
            $rmeals = D('Rmeals')->getList($condition,true,"id desc", $p, $num);
            $this->assign('rmeals',$rmeals);

            $count = D("Rmeals")->getListCount($condition);// 查询满足要求的总记录数
            $Page = new \Think\Page($count, $num);// 实例化分页类 传入总记录数和每页显示的记录数
            $Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
            $show = $Page->show();// 分页显示输出
            $this->assign('page', $show);// 赋值分页输出
        }
        
        $this->assign('schools',$schools);
        $this->display();
    }

   
    public function allrmeals(){
        $shop_id = session("homeShopId");
        if(!empty(I('post.'))){
            if(!empty('post.class')){
                $shop = M('Shop')->field('id,name,deadline,is_day')->where('id='.$shop_id)->find();
                //退全部
                if(I('post.mode') == 2){
                    $now = date('Y-m-d',time());
                    $rmeals_time = $now;
                    //$rmeals_time = "2015-09-01";
                    //班级id用order表里的class_id
                    $sql = "select 
                                od.*,oo.orderid,pp.order_date,pp.price,oo.shop_id
                                from __PREFIX__order_detail as od 
                                left join __PREFIX__order as oo on oo.id = od.order_id
                                left join __PREFIX__product as pp on pp.id = od.product_id
                                where oo.shop_id=".$shop_id." and oo.pay_status=1 and oo.class_id=".I('post.class')." and pp.order_date>='".$rmeals_time."'
                                ";
                    $detail = M()->query($sql);
                    //当前时间不在限定时间内，当天不能退货
                    if(($shop['is_day'] == 1) && (date('H:i:s')>$shop['deadline'])){
                        foreach($detail as $dk=>$dv){
                            if($dv['order_date']==$now){
                                unset($detail[$dk]);
                            }
                        }
                    }
                }
                //退某一天
                if(I('post.mode') == 1){
                    $rmeals_time = I('post.time');
                    //$rmeals_time = "2016-09-16";
                    //班级id用order表里的class_id
                    $sql = "select 
                                od.*,oo.orderid,pp.order_date,pp.price,oo.shop_id
                                from __PREFIX__order_detail as od 
                                left join __PREFIX__order as oo on oo.id = od.order_id
                                left join __PREFIX__product as pp on pp.id = od.product_id
                                where oo.shop_id=".$shop_id." and oo.pay_status=1 and oo.class_id=".I('post.class')." and pp.order_date='".$rmeals_time."'
                                ";
                    $detail = M()->query($sql);                  
                    $now=date('Y-m-d',time());
                    //当前时间不在限定时间内，当天不能退货
                    if($rmeals_time == $now && ($shop['is_day'] == 1) && (date('H:i:s')>$shop['deadline'])){
                        foreach($detail as $dk=>$dv){
                            if($dv['order_date']==$now){
                                unset($detail[$dk]);
                            }
                        }
                    }                                      
                }
                if(!empty($detail)){
                    $data=array();
                    $data['shop_id']=$shop_id;
                    $data['school_id']=I('post.school');
                    $data['class_id']=I('post.class');
                    $data['type']=I('post.mode');
                    if(I('post.mode')==2){
                        $data['order_date']='';
                    }else if(I('post.mode')==1){
                        $data['order_date']=$rmeals_time;
                    }
                    
                    $data['time']=date('Y-m-d H:i:s',time());
                    //退餐操作主表
                    $remeals_id = M('Rmeals')->add($data);
                    $xx_status = array();
                    foreach ($detail as $dlk => $dlv) {
                        $rd = array();
                        $url = NULL;
                        $result = NULL;
                        $rd_data = array();

                        //判断详情表里的退餐和请假状态
                        if(($dlv['leave_status'] == -1 || $dlv['leave_status'] == 2) && $dlv['retreat_status'] == -1){
                            //请假恢复成0
                            $rd['leave_status'] = '0';
                            //已退餐
                            $rd['retreat_status'] = '1';
                        }
                        if($dlv['leave_status'] == -1 && $dlv['retreat_status'] == 2){
                            //请假恢复成0
                            $rd['leave_status'] = '0';
                            //已退餐
                            $rd['retreat_status'] = '1';
                        }
                        if($dlv['leave_status'] == 2 && $dlv['retreat_status'] == 2){
                            //请假恢复成0
                            $rd['leave_status'] = '0';
                            //已退餐
                            $rd['retreat_status'] = '1';
                        }
                        if($dlv['leave_status'] == 0 && $dlv['retreat_status'] == 0){
                            //请假恢复成0
                            $rd['leave_status'] = '0';
                            //已退餐
                            $rd['retreat_status'] = '1';
                        }
                        if(($dlv['leave_status'] == -1 || $dlv['leave_status'] == 2) && $dlv['retreat_status'] == 0){
                            //请假恢复成0
                            $rd['leave_status'] = '0';
                            //已退餐
                            $rd['retreat_status'] = '1';
                        }
                        if($dlv['leave_status'] == 0 && ($dlv['retreat_status'] == -1 || $dlv['retreat_status'] == 2)){
                            //请假恢复成0
                            $rd['leave_status'] = '0';
                            //已退餐
                            $rd['retreat_status'] = '1';
                        }
                        if($dlv['leave_status'] == 1 && $dlv['retreat_status'] == 1){
                            //请假恢复成0
                            $rd['leave_status'] = '0';
                            //已退餐
                            $rd['retreat_status'] = '1';
                        }
                        if($dlv['leave_status'] == 0 && $dlv['retreat_status'] == 1){
                            continue; //不作处理，跳出本次循环，继续下次循环
                        }
                        if($dlv['leave_status'] == 1 && $dlv['retreat_status'] == 0){
                            continue; //不作处理，跳出本次循环，继续下次循环
                        }

                        
                        $url = CONNECT."returns/".$dlv['user_id']."/".$dlv['orderid']."/".$dlv['price']."/".$dlv['order_date']."/".$dlv['order_date'];
                        $result = $this->_request($url,true,'POST');
                        $result = json_decode($result);
                        if($result[0]->success == '1'){
                            $re = M('OrderDetail')->where('id='.$dlv['id'])->save($rd);

                            //查询订单详情表中是否都已退单或请假
                            $odwh['order_id'] = $dlv['order_id'];
                            $odwh['_string'] = 'retreat_status = 1 or leave_status = 1';
                            $num = M('OrderDetail')->where($odwh)->count();

                            //全部订单详情数量
                            $allnum = M('OrderDetail')->where('order_id='.$dlv['order_id'])->count();
                            
                            if($re == 1){
                                if($num == $allnum){
                                    $odata['pay_status'] = '2';  //订单已完成
                                    M('Order')->where('id='.$dlv['order_id'])->save($odata);
                                }
                            }

                            //调接口状态汇总数组
                            $xx_status[]='1';
                            $rd_data['result']=$result[0]->success;
                        }else{
                            $xx_status[]='0'; //失败
                            $rd_data['result']=$result[0]->msg;
                        }

                        $rd_data['rmeals_id']=$remeals_id;
                        $rd_data['orderdetail_id']=$dlv['id'];
                        $rd_data['orderid']=$dlv['orderid'];
                        $rd_data['price']=$dlv['price'];
                        $rd_data['order_date']=$dlv['order_date'];
                        $rd_data['product_id']=$dlv['product_id'];
                        $rd_data['user_id']=$dlv['user_id'];
                        $rd_data['url']=$url;
                        $rd_data['time']=date('Y-m-d H:i:s',time());
                        M('RmealsDetail')->add($rd_data);
                    }
                    $tag=1;
                    foreach ($xx_status as $xk => $xv) {
                        if($xv == 0){
                            $tag=0;
                        }
                    }
                    if($tag == 0){
                        $rda['status']=0;
                    }else{
                        $rda['status']=1;
                    }
                    $resv = M('Rmeals')->where('id='.$remeals_id)->save($rda);
                    echo $resv;
                }
            }
            
        }
    }

    //获得班级
    public function getclass(){
        $class = M('XjClass')->field('id,bjmc,nj')->where('school_id='.$_POST['schoolid'])->order('bh asc')->select();
        echo json_encode($class);
    }

    public function allrmeal(){
        $shop_id = session("homeShopId");
        $sql = 'SELECT ss.company_id,ss.shop_id,s.xxmc FROM multi_school_shop ss,multi_xj_school s '. 
                        'WHERE ss.company_id=s.id AND ss.shop_id= '.$shop_id;
        //学校列表
        $schools = M()->query($sql);
        $this->assign('schools',$schools);
        $this->display();
    }  

    //批量退餐详情
    public function rmealsdetail(){
        $rmealsdetail=array();
        if(!empty(I('post.rmeals_id'))){
            $sql = "select 
                rd.*,pp.name
                from __PREFIX__rmeals_detail as rd
                left join __PREFIX__rmeals as rr on  rr.id = rd.rmeals_id
                left join __PREFIX__product as pp on pp.id = rd.product_id
                where rd.rmeals_id = ".I('post.rmeals_id');
            $rmealsdetail = M()->query($sql);
        }
        echo json_encode($rmealsdetail);
        
    }  

}