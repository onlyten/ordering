<?php
namespace App\Controller;


class IndexController extends BaseController
{
    public function index()
    {
        // $user = R("App/Public/oauthLogin");
        // $user = json_encode($user);
        // $this ->assign("user",$user);

        // $config = D("Config")->get();
        // $config["delivery_time"] = explode(",", $config["delivery_time"]);
        // $this->assign("config", json_encode($config));

        // $menu = D("Menu")->getList(array(), true, "rank desc,id desc");
        // $menu = list_to_tree($menu, 'id', 'pid', 'sub');
        // $this->assign("menu", json_encode($menu));

        // $product = D("Product")->getList(array("status" => array("neq", -1)), true, "rank desc", 0, 0, 0);
        // $this->assign("product", json_encode($product));

        // $ads = D("Ads")->getList(array(), true);
        // $this->assign("ads", json_encode($ads));
        $user = R("App/Public/oauthLogin");
        $user = json_encode($user);
        $this ->assign("user",$user);
        $shopId = I("get.shopid");
        session("shop_id",$shopId);

        $configs = D("Config")->get();
        $config = D("Shop")->getShop(array('id'=>$shopId));
        $config["delivery_time"] = explode(",", $config["delivery_time"]);
        $config["balance_payment"] = $configs["balance_payment"];
        $config["wechat_payment"] = $configs["wechat_payment"];
        $config["alipay_payment"] = $configs["alipay_payment"];
        $config["cool_payment"] = $configs["cool_payment"];
        $this->assign("config", json_encode($config));

        $menu = D("Menu")->getList(array("shop_id"=>$shopId), true, "rank desc,id desc");
        $menu = list_to_tree($menu, 'id', 'pid', 'sub');
        $this->assign("menu", json_encode($menu));

        $product = D("Product")->getList(array("status" => array("neq", -1),"shop_id"=>$shopId), true, "rank desc", 0, 0, 0);
        $this->assign("product", json_encode($product));

        $ads = D("Ads")->getList(array("shop_id"=>$shopId), true);
        $this->assign("ads", json_encode($ads));
        
        $wxConfig = D("WxConfig")->getJsSign();
        $this->assign("wxConfig",json_encode($wxConfig));

        $this->display();
    }
    //pidong 通过shopid获取当前店铺信息
    public function getThisShop(){
        $this->display();
    }
    //pidong 通过shopid获取当前店铺信息
    public function shop(){
        $user = R("App/Public/oauthLogin");
        $user = json_encode($user);
        $this ->assign("user",$user);

        if(I("get.shopid")){
            $shopId = I("get.shopid");
            session("shop_id",$shopId);           
        }

        $configs = D("Config")->get();
        $config = D("Shop")->getShop(array('id'=>$shopId));
        $config["delivery_time"] = explode(",", $config["delivery_time"]);
        $config["balance_payment"] = $configs["balance_payment"];
        $config["wechat_payment"] = $configs["wechat_payment"];
        $config["alipay_payment"] = $configs["alipay_payment"];
        $config["cool_payment"] = $configs["cool_payment"];
        $this->assign("config", json_encode($config));
        
        $wxConfig = D("WxConfig")->getJsSign();
        $this->assign("wxConfig",json_encode($wxConfig));
        
        $this->display();
    }


    public function init()
    {
        $data = array();
        $config = D("Config")->get();
        $config["delivery_time"] = explode(",", $config["delivery_time"]);
        $data["config"] = $config;

        $data["ads"] = D("Ads")->getList(array(), true);

        $menu = D("Menu")->getList(array(), true, "rank desc,id desc");
        $menu = list_to_tree($menu, 'id', 'pid', 'sub');
        $data["menu"] = $menu;

        $data["product"] = D("Product")->getList(array("status" => array("neq", -1)), true, "rank desc", 0, 0, 0);
        $this->ajaxReturn($data);
    }
}