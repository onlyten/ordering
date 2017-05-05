<?php
namespace Home\Controller;

use Think\Controller;

class BaseController extends Controller
{
    public function _initialize()
    {
//      url中的pjax参数影响数据分页
        if (I("get._pjax")) {
            unset($_GET["_pjax"]);
        }

        if (!$this->is_login()) {
            $this->redirect('Home/Public/login');
        }

        $this->shopauth();
        
        // if (!session("homeShopId") && !(MODULE_NAME == "Home" && CONTROLLER_NAME == "Shop" && ACTION_NAME == "shop"
        //         || ACTION_NAME == "switchShop" || ACTION_NAME == "addShop" || ACTION_NAME == "modifyShop" || ACTION_NAME == "delShop")
        // ) {
        //     $this->error('请先选择店铺', 'Home/Shop/shop');
        // }
    }

    public function is_pjax()
    {
        return array_key_exists('HTTP_X_PJAX', $_SERVER) && $_SERVER['HTTP_X_PJAX'];
    }

    public function display($templateFile = '', $toggle = true)
    {
        if ($toggle) {
            if ($this->is_pjax()) {
                layout(false);
            } else {
                //切换店铺
                $shopBarList = D("Shop")->getShopList(array("user_id" => session("homeId")));
                $this->assign("shopBarList", $shopBarList);

                $this->assign("shopBar", session("homeShop"));

//                if (session("homeShopId")) {
//                    $shopBar = D("Shop")->getShop(array("id" => session("homeShopId")));
//                    $this->assign("shopBar", $shopBar);
//
//                    $member = D("UserMember")->where(array("user_id" => session("homeId"), "status" => 1))->order("id desc")->find();
//                    $this->assign("member", $member);
//                }

                layout('layout');
            }
        } else {
            layout(false);
        }

        return parent::display($templateFile);
    }

    public function is_login()
    {
        if (session("homeId")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * GET 请求
     * @param string $url
     */
    private function http_get($url)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    /**
     *  左侧菜单权限
    */
    private function shopauth(){
        //判断是否为商家子管理员
        $homeuser = D("User")->get(array('id'=>session("homeId")));
        $this->assign("type",$homeuser['type']);
        //子管理员 无设置子管理员权限
        if($homeuser['type'] == 3 && !empty($homeuser['pid'])){
            $result = array();
            $rules = D("UserGroupAccess")->getRuls(session("homeId"));
            foreach($rules as $rk=>$rv){
                $result[$rv['type']][]=$rv;
            }
            $this->assign("rules",$result);
        }
    }

    /**
     * 实现curl的post和get访问方式
     * @param string $url
     * @param booleam $https
     * @param string $method
     * @param array $data
     */
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