<?php
namespace Phone\Controller;

use Think\Controller;


class BaseController extends Controller
{
    public function _initialize ()
    {
        if(cookie("username")){
        	$map["login_name"] = array("eq",cookie("username"));
        	$login_mark = M("sys_user")->where($map)->getField("login_mark");
        	if($login_mark == session('login_mark')){
        		return true;
        	}else{
        		echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
                echo  "<script>alert('你的账号在另一台设备已登录，请重新登录！');window.location.href='https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx7cd8c8ccc5c9747f&redirect_uri=http://dctest.wanshuyun.com/ordering/Phone/Login/login&response_type=code&scope=snsapi_base&state=123&connect_redirect=1#wechat_redirect'</script>";
        	}
        }else{
            $this->redirect('https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx7cd8c8ccc5c9747f&redirect_uri=http://dctest.wanshuyun.com/ordering/Phone/Login/login&response_type=code&scope=snsapi_base&state=123&connect_redirect=1#wechat_redirect');
        }
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
