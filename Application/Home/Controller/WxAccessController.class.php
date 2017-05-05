<?php
namespace Home\Controller;

class WxAccessController extends BaseController
{
    
    public function template()
    {
       //$data =array();
        
        $shop_id=session("homeShopId");
        $sql="SELECT s.id AS schoolId,xxmc FROM multi_school_shop shop ,multi_xj_school s WHERE shop.company_id=s.id AND shop.shop_id=".$shop_id;
        
        $schoolList = M()->query($sql);
        $this->assign("schoolList",$schoolList);
        //var_dump($schoolList);
        $this->display();

          
    
    }
    //根据学校查询班级列表
    public function getClassBySchoolId()
    {
         $school_id = I("post.school_id");
         $where['school_id'] = $school_id;
         $classList = D("XjClass")->where($where)->field('id,bjmc')->select();
         //var_dump($sql);
          $this->ajaxReturn(
            array(
                "list" => $classList,
               
            )
        );
    }
    public function getAccessToken()
    {
       $school =  I('post.school');  // 学校id
       $banji =  I('post.banji');    //班级id

       $data['appid']=wx7cd8c8ccc5c9747f;     //appid
       $data['secret'] = ccefbfd403952a118bc1c65153022c44;  //密码

       ///$token = cookie("access_token");  //cookie中的微信token用来发消息
       
        $newTime = date('y-m-d h:i:s',time());
        $where['appid'] =$data['appid'];
        $expTime = D('WxAccessToken')->where($where)->field('id,time')->find();   //根据tokenid查询是否存在词条记录
       
       
        
        if(empty($expTime))           //如果不存在则调用一次接口
        {
          
           
            $this->getToken($data);       

        }else
        {
            $str1=strtotime($newTime);  //当前时间
            $str2=strtotime($expTime['time']);  //数据库时间             
            //求时间差             
            $diff= $str1-$str2;            
            if($diff>=7000)  //预留200 如果token存在一条记录且时间超过7200
            {      
                  
                $data['id'] = $expTime['id'];
                $this->getToken($data);

            }
            

        }
       $tokenNew = cookie("access_token");   //token
       //echo '----------------';
       //var_dump($tokenNew);
       if(empty($banji))    //如果班级为空
       {
        
         $where['school_id'] = $school;
         $openidList =  D('WxInfo')->where($where)->field('openid')->select();
         foreach ($openidList as $key => $value) {  
         var_dump($value['openid']);      
            if(!empty($value['openid']))
            {
                $tpl =  array(
                       "touser"=>$value['openid'],
                       "template_id"=>"H4Mv0NxKSNZ2LBgCEOZsYiwQ6p9uE6fZ4ID51O3xC2E",
                       "url"=>"http://weixin.qq.com/download",            
                       "data"=>array(
                               "first"=> array(
                                   "value"=> urldecode("尊敬的各位家长，配餐商家本月的营养菜单已出，您可以为孩子订餐啦！")
                                   
                               ),
                               "keyword1"=> array(
                                   "value"=>urldecode("订餐提醒")
                                   
                               ),
                               "keyword2"=> array(
                                   "value"=>urldecode("正在进行中")
                                   
                               ),
                              
                               "remark"=> array(
                                   "value"=>urldecode("丰富营养的膳食，助力孩子健康成长！")
                               )
                       )
                   );

                $this->send_template_message(urldecode(json_encode($tpl)),$tokenNew);  //调用发送信息模板

            }

         }
        
       }else
       {
       
         $sql=" SELECT openid FROM multi_wx_info info WHERE info.user_id in (SELECT s.dyzh dyzh ".
              " FROM multi_xj_class c ,multi_xj_student s WHERE c.id=s.bj AND c.id=".$banji.")";   //查询某个班级所有学生的openid
         
         $banjOpen = M()->query($sql);
         foreach ($banjOpen as $key => $value) {
           var_dump($value['openid']);   
            if(!empty($value['openid']))
            {
                $tpl =  array(
                       "touser"=>$value['openid'],
                       "template_id"=>"H4Mv0NxKSNZ2LBgCEOZsYiwQ6p9uE6fZ4ID51O3xC2E",
                       "url"=>"http://weixin.qq.com/download",            
                       "data"=>array(
                                "first"=> array(
                                   "value"=> urldecode("尊敬的各位家长，配餐商家本月的营养菜单已出，您可以为孩子订餐啦！")
                                   
                               ),
                               "keyword1"=> array(
                                   "value"=>urldecode("订餐提醒")
                                   
                               ),
                               "keyword2"=> array(
                                   "value"=>urldecode("正在进行中")
                                   
                               ),
                              
                               "remark"=> array(
                                   "value"=>urldecode("丰富营养的膳食，助力孩子健康成长！")
                               )
                       )
                   );

                $this->send_template_message(urldecode(json_encode($tpl)),$tokenNew);  //调用发送信息模板

            }
           
         }
         

       }
       //var_dump("ok!!!!!!!!!!!!!!!!!!!!!!");
        $this->ajaxReturn(array("errcode" => 1, "errmsg" => "发送完成"));
      
    }
    private function getToken($data)
    {
      //var_dump($data);
       
        $urlt='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$data['appid'].'&secret='.  $data['secret'];
        $result = $this->_request($urlt,true,'POST');
        $result = json_decode($result,true);
        $data['access_token']=$result['access_token'];
        $data['expires_in']=$result['expires_in'];
        $t = D('WxAccessToken')->save($data);   //存入数据库
        //echo "///////////////////////token//////////////////////";
        //var_dump($t);
        cookie("access_token", $result['access_token']);  //存到cookie       
            
    }
    private function send_template_message($data,$token)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$token;
        $ty = $this->_request($url,true,'POST',$data);
        //var_dump($ty);
    }
   
}