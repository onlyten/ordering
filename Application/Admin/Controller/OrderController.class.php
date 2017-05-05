<?php
namespace Admin\Controller;

class OrderController extends BaseController
{
     public function  import(){
        Vendor('PHPExcel.Excel','',".class.php");
        $config = array(
            'remove' => true,        //是否上传后删除文件
            'filename' => 'filename', //文件名称
            'rootpath' => './Public', //上传主目录
            'savepath' => '/Uploads/Files/Excel/',//上传子目录
            'filetype' => array('xls', 'xlsx'),//限制上传文件类型
            'fields' => array('学校名','班级名','学生名','商家名','套餐名','金额'),//导入/导出文件字段[导入时为数据字段,导出时为字段标题]
            'datefield' => array(),//上传带日期时间格式字段
            'data' => array(), //导出Excel的数组
            'savename' => '',  //导出文件名称
            'title' => '',     //导出文件栏目标题
            'suffix' => 'xlsx',//文件格式
        );
        $excel = new \Excel($config);
        $excel->importOrder(); 
        $this->success("导入成功", cookie("prevUrl"));      
       
    }
    public function order()
    {
//        每页显示的记录数
        $num = 25;
        $p = I("get.page") ? I("get.page") : 1;
        cookie("prevUrl", "Admin/Order/order/page/" . $p);

        $condition = array();
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
        $orderList = D("Order")->getList($condition, true, "id desc", $p, $num);
        foreach ($orderList as $k => $val) {
            $where['dyzh'] = $val['user_id'];
            //学生信息 真实姓名
            $student = D('XjStudent')->where($where)->field('dyzh,xsxm')->find();
            $orderList[$k]['student'] = $student; 
        }
        $this->assign("orderList", $orderList);

        $count = D("Order")->getMethod($condition, "count");// 查询满足要求的总记录数
        $Page = new \Think\Page($count, $num);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show();// 分页显示输出
        $this->assign('page', $show);// 赋值分页输出

        $productList = D("Product")->getList(array(), true);
        $this->assign('productList', $productList);// 赋值分页输出

        $this->display();
    }

    public function search()
    {
        $condition = array();
        var_dump(I('post.'));

        if (I("post.id") && !empty(I("post.id"))) {
            array_push($condition, array("id" => I("post.id")));
        }
        if (I("post.orderid") && !empty(I("post.orderid"))) {
            array_push($condition, array("orderid" => I("post.orderid")));
        }
        if (I("post.user_id") && !empty(I("post.user_id"))) {
            array_push($condition, array("user_id" => I("post.user_id")));
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

        $orderList = D("Order")->getList($condition, true, "id desc");
        //echo D('Order')->getLastsql();
        foreach ($orderList as $k => $val) {
            $where['dyzh'] = $val['user_id'];
            //学生信息 真实姓名
            $student = D('XjStudent')->where($where)->field('dyzh,xsxm')->find();
            $orderList[$k]['student'] = $student; 
        }

        /*if (I("post.product_id") != -10) {
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
        }*/

        $productList = D("Product")->getList(array(), true);
        $this->assign('productList', $productList);// 赋值分页输出

        $this->assign("orderPost", I("post."));
        $this->assign("orderList", $orderList);
        $this->display("order");
    }
    //订单详情
    public function productlist(){
        $condition = array(
            "order_id" => I('post.order_id')
        );
        $orderdetail = D('OrderDetail')->getList($condition,true);
        echo json_encode($orderdetail);
    }

    public function update()
    {
        $data = I("get.");
        $data["id"] = array("in", $data["id"]);
        D("Order")->save($data);

        $this->success("操作成功", cookie("prevUrl"));
    }

    public function export()
    {
        $condition = array();
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

        $order = D('Order')->getList($condition, true, "id desc");
        foreach ($order as $key => $value) {
            unset($value["contact"]["id"]);
            unset($value["contact"]["user_id"]);
            unset($value["contact"]["time"]);

            /*foreach ($value["contact"] as $k => $v) {
                $order[$key][$k] = $v;
            }*/
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
                $detail .= $v["name"] . "[属性:" . $v["attr"] . "]" . "[数量:" . $v["num"] . "]" . "[价格:" . $v["price"] . "],";
            }
            $order[$key]["detail"] = $detail;

            $order[$key]['shop'] = $value['shop']['name'];
            $where['dyzh'] = $value['user_id'];
            //学生信息 真实姓名
            $student = D('XjStudent')->where($where)->field('xsxm')->find();
            $order[$key]["sysuser"] = $student['xsxm'];
            unset($order[$key]['menu']);
        }

        Vendor("PHPExcel.Excel#class");
        //\Excel::export($order);

        \Excel::export($order, array('订单ID', '店铺ID', '用户ID', '订单编号', '总价格', '支付方式', '支付状态', '运费', '折扣', '备注', '状态', '时间', '用户','订单详情', '店铺'));
    }

    //订单详情
    public function detail(){
        $num = 25;
        $p = I("get.page") ? I("get.page") : 1;
        cookie("prevUrl", "Home/Order/detail/page/" . $p);

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
        
        $orderdetail = D('OrderDetail')->getList($condition,true);
        
        foreach ($orderdetail as $k => $val) {
            //学生姓名
            $stuwhere['dyzh']=$val['user_id'];
            $student = D('XjStudent')->where($stuwhere)->field('id,dyzh,xsxm')->find();
            $orderdetail[$k]['student'] = $student;
        }
    
        $this->assign('data',$data);
        $this->assign('orderdetailList',$orderdetail);

        $count = count($orderdetail);// 查询满足要求的总记录数
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
        //var_dump($orderDetailList);

        foreach ($orderDetailList as $k => $val) {
            $where['dyzh'] = $val['user_id'];
            //学生信息 真实姓名
            $student = D('XjStudent')->where($where)->field('dyzh,xsxm')->find();
            $orderDetailList[$k]['student'] = $student;
        }

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
            $detail = D('OrderDetail')->get($condition, true);  //订单详情
            $url = CONNECT."leave/".$detail['user_id']."/".$detail['order_id']."/".$detail['price']."/".$detail['product']['order_date']."/".$detail['product']['order_date'];
            $result = $this->_request($url,true,'POST');
            $result = json_decode($result);

            if($result[0]->success == '1'){
                D('OrderDetail')->where('id='.$id)->save($data);
                echo  1;
            }else{

                echo  $result[0]->msg;
            }
            //D('OrderDetail')->where('id='.$id)->save($data);
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
            $detail = D('OrderDetail')->get($condition, true);  //订单详情
            $url = CONNECT."returns/".$detail['user_id']."/".$detail['order_id']."/".$detail['price']."/".$detail['product']['order_date']."/".$detail['product']['order_date'];

            $result = $this->_request($url,true,'POST');
            $result = json_decode($result);
            if($result[0]->success == '1'){
                D('OrderDetail')->where('id='.$id)->save($data);
                echo '1';
            }else{
                echo $result[0]->msg;
            }

            //D('OrderDetail')->where('id='.$id)->save($data);
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

}