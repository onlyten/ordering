<?php
namespace Phone\Controller;
use Think\Controller;
class TeacController extends Controller
{
    public function check(){//老师请假审核
        $class_id = $this->bj();
        // echo $class_id;
        // die();
        /*$m = M("order_detail");
        $map["leave_status"] = array("eq",-1);
        $order_check = $m->where($map)->group('order_id')->select();*/
        $m = D("DetailPro");
        //$map["leave_status"] = array("eq",-1);
        $map["class_id"] = array("eq",$class_id);
        $map["is_examine"] = array("eq",1);
        $order_check = $m->where($map)->where('retreat_status=2 OR leave_status=2')->group('order_id')->select();
        /*dump($order_check);
        die();*/
        $long = count($order_check);
        for($i=0;$i<$long;$i++){
            $mapp["order_id"] = $order_check[$i]["order_id"];
            //$mapp["leave_status"] = array("eq",-1);
            $product_id = M("order_detail")->where($mapp)->where('retreat_status=2 OR leave_status=2')->field("product_id,id")->select();
            $longone = count($product_id);
            for($j=0;$j<$longone;$j++){
                $arr[$i][] = $product_id[$j]["product_id"];
                $arr1[$i][] = $product_id[$j]["id"];//订单详情id
            }
            //dump($arr[$i]);
            $mmap["id"] = array("in",$arr[$i]);
            $product = M("product")->where($mmap)->field("order_date")->select();
            $longtwo = count($product);
            $order_check[$i]["date"] = $product[0]["order_date"]." 至 ".$product[$longtwo-1]["order_date"];//请假日期

            $maap["dyzh"] = array("eq",$order_check[$i]["user_id"]);
            $student_name = M("xj_student")->where($maap)->field("xsxm")->find();
            //dump($student_name);
            $order_check[$i]["stu_name"] = $student_name["xsxm"];
            $order_check[$i]["detail_id"] = implode(",",$arr1[$i]);
        }
        //dump($arr1);
       /* dump($order_check);
        die();*/
        $this->assign("order_check",$order_check);
        $this->display();
    }


    public function check_update(){//老师请假审核更新
        $detail_id = explode(",",I("get.detail_id"));
        $map["id"] = array("in",$detail_id);

        //拒绝请假
        $data['leave_status'] = 0;
        //$data['remark'] = 0;

        //同意请假
        $dataa['leave_status'] = -1;
        //$dataa['remark'] = 1;

        //同意退餐
        $datb['retreat_status'] = -1;
        //$datb['remark'] = 1;

        //拒绝退餐
        $datbb['retreat_status'] = 0;
        //$datbb['remark'] = 0;
        
        
        if(I("get.agree") == 1 && I("get.leave_status") == 2){//同意请假
            M("order_detail")->where($map)->data($dataa)->save();
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo  "<script>alert('请假审核，已同意！');javascript:self.location=document.referrer;</script>";
        }
        if(I("get.agree") == 0 && I("get.leave_status") == 2){//拒绝请假
            M("order_detail")->where($map)->data($data)->save();
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo  "<script>alert('请假审核，已拒绝！');javascript:self.location=document.referrer;</script>";
        }
        if(I("get.agree") == 1 && I("get.retreat_status") == 2){//同意退餐
            M("order_detail")->where($map)->data($datb)->save();
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo  "<script>alert('退餐审核，已同意！');javascript:self.location=document.referrer;</script>";
        }
        if(I("get.agree") == 0 && I("get.retreat_status") == 2){//拒绝退餐
            M("order_detail")->where($map)->data($datbb)->save();
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            echo  "<script>alert('退餐审核，已拒绝！');javascript:self.location=document.referrer;</script>";
        }
    }

    public function tongji(){
        $class_id = $this->bj();
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $shop = $Model->query("SELECT
            multi_shop.id AS shop_id,
            multi_shop.name AS shop_name
        FROM
            multi_order
        INNER JOIN multi_shop ON multi_order.shop_id = multi_shop.id
        WHERE multi_order.pay_status != 0 AND multi_order.class_id = '$class_id'
        GROUP BY multi_shop.name");
        // dump($shop);
        // echo "jj";
        $num = count($shop);
        $long = count($shop);
        for($i=0;$i<$long;$i++){
            $mmap["shop_id"] = array("eq",$shop[$i]["shop_id"]);
            $hah[$i] = D("ShopMenu")->where($mmap)->select();
        }

        $map["pay_status"] = array("neq",0);
        $map["class_id"] = array("eq",$class_id);
        $m = M("order")->where($map)->field("menu_id")->select();
        $long = count($m);
        for($i=0;$i<$long;$i++){
            $arr[] = $m[$i]["menu_id"];
        }
        $newarr = array_count_values($arr);//统计个数
        $allnum = array_sum($newarr);
       // dump($newarr);
        $key = array_keys($newarr);

        for($x=0;$x<$num;$x++){
            $yy[$x] = 0;
            $lon = count($hah[$x]);
            $long = count($newarr);
            for($i=0;$i<$lon;$i++){
                for($j=0;$j<$long;$j++){
                    if($hah[$x][$i]["id"] == $key[$j]){
                        $hah[$x][$i]["num"] = $newarr[$key[$j]];
                        $yy[$x]+= $newarr[$key[$j]];
                    } 
                }
            }
            //$hah[$x]["total"] = $yy[$x];
        }
       /* dump($hah);
        dump($yy);
        die();*/
        $this->assign("allnum",$allnum);
        $this->assign("one",$yy[0]);
        $this->assign("two",$yy[1]);
        $this->assign("three",$yy[2]);
        $this->assign("shopone",$hah[0]);
        $this->assign("shoptwo",$hah[1]);
        $this->assign("shopthree",$hah[2]);
        $this->display();

    }

    public function bj(){
        $login_name = cookie("username");
        $map["login_name"] = array("eq",$login_name);
        $user_id = M("sys_user")->where($map)->getField("id");
        //echo $user_id."<br/>";
        $mapp["login_id"] = array("eq",$user_id);
        $teacher_id = M("user_teacher")->where($mapp)->getField("id");
        //echo $teacher_id;
        $maap["teacher_id"] = array("eq",$teacher_id);
        $class_id = M("ref_teacher_class")->where($maap)->getField("class_id");
        //echo $class_id;
        return $class_id;
    }
}