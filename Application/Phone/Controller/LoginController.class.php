<?php
namespace Phone\Controller;
use Think\Controller;
class LoginController extends Controller
{
    public function login(){
        $code = $_GET['code'];//获取code
        $weixin =  file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx7cd8c8ccc5c9747f&secret=ccefbfd403952a118bc1c65153022c44&code=".$code."&grant_type=authorization_code");//通过code换取网页授权access_token
        $jsondecode = json_decode($weixin); //对JSON格式的字符串进行编码
        $array = get_object_vars($jsondecode);//转换成数组
        $openid = $array['openid'];//输出openid
        //echo $code."****".$openid;
        cookie("openid",$openid,1296000);
        $this->display();
    }

    public function login_update(){
        $login_name = I("post.username");
        $password = I("post.password");
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $login = $Model->query("SELECT * FROM multi_sys_user WHERE login_name = '$login_name' AND password = '$password'");
        // $login = $m->where($map)->count();
        if($login){
            if($login[0]["user_type"] == '2'){//学生登录
                /****************将openid、user_id和school_id 存入wx_info表中***********start**************/
                $wx["openid"] = array("eq",cookie('openid'));
                $wx_num = M("wx_info")->where($wx)->count();
                if($wx_num == 0){//wx_info 表中没有此账号信息
                    $mao["user_id"] = array("eq",$login[0]["id"]);
                    $mao["pay_status"] = array("neq",0);
                    $mao_num = M("order")->where($mao)->count();
                    if($mao_num != 0){//判断此账号是否订过餐
                        $wx_data["school_id"] = $login[0]["company_id"];
                        $wx_data["openid"] = cookie('openid');
                        $wx_data["user_id"] = $login[0]["id"];
                        M("wx_info")->data($wx_data)->add();
                    }
                }
                /****************将openid、user_id和school_id 存入wx_info表中***********end**************/

                cookie("username",I("post.username"),25920000);
                $suiji = $this->getRandChar(15);//单点登录
                session("login_mark",$suiji,25920000);
                $data['login_mark'] = $suiji;
                $map['login_name'] = array("eq",cookie('username'));
                M("sys_user")->where($map)->save($data);
                $userinfo = M("sys_user")->where($map)->find();
                //开户
                $url="http://172.168.8.25:8080/jeecg/rest/CustAccount/".$userinfo["id"]."/".$userinfo["company_id"]."/5".$userinfo["dept_id"]."/".cookie("openid")."/".urlencode($userinfo["name"]);//开户
                //echo $url;
                $result = $this->_request($url,true,'POST'); 
                $result = json_decode($result);
                //dump($result);
                //echo $result[0]->success;
                //die();
                //if(I("post.wechat")){
                if($result[0]->success == 1){
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                    echo  "<script>alert('开户成功！');window.location.href='".__ROOT__."/Phone/User/getList'</script>";
                }else{
                    $this->redirect('User/getList');
                    // echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                    // echo  "<script>alert('登录成功！');window.location.href='".__ROOT__."/Phone/User/getList'</script>";
                }
                //$this->redirect('User/getList');
            }
            // if($login[0]["ref_id"] == '3'){
            //     cookie("username",I("post.username"),25920000);
            //     echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            //     echo  "<script>alert('登录成功！');window.location.href='".__ROOT__."/Phone/Teac/check'</script>";
            // }
            
        }else{
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            //echo  "<script>alert('用户名或密码错误');window.location.href='/share/ordering/index.php/Phone/Login/login'</script>";
            echo  "<script>alert('用户名或密码错误');window.location.href='https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx7cd8c8ccc5c9747f&redirect_uri=http://dctest.wanshuyun.com/ordering/Phone/Login/login&response_type=code&scope=snsapi_base&state=123&connect_redirect=1#wechat_redirect'</script>";
        }
    }

    public function login_out(){
        cookie("username",null);
        Header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx7cd8c8ccc5c9747f&redirect_uri=http://dctest.wanshuyun.com/ordering/Phone/Login/login&response_type=code&scope=snsapi_base&state=123&connect_redirect=1#wechat_redirect");
        //exit;
        //$this->redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx7cd8c8ccc5c9747f&redirect_uri=http://dctest.wanshuyun.com/ordering/Phone/Login/login&response_type=code&scope=snsapi_base&state=123&connect_redirect=1#wechat_redirect");
    }


    function getRandChar($length){//产生随机字符串
       $str = null;
       $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
       $max = strlen($strPol)-1;

       for($i=0;$i<$length;$i++){
        $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
       }

       return $str;
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

   
    
    
    
}