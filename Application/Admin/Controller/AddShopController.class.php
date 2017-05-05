<?php
namespace Admin\Controller;

use Org\Net\Http;
use ZipArchive;

class AddShopController extends BaseController
{
   

   public function addAuthShop()
    {
        if (IS_POST) {
			
			$data = I("post.");
             if (!I("post.shop_id")) {
				
                $this->error("商家不能为空", "Admin/AddShop/addAuthShop");
            }
			if(!I("post.company_id"))
			{
				
				$this->error("学校不能为空", "Admin/AddShop/addAuthShop");
			}
           else
			{
				
				$result = D("SchoolShop")->where(array("company_id" => $data['company_id']))->delete();
				
				$array = I("post.shop_id");
				$size = sizeof(I("post.shop_id"));
				
				for($i=0;$i<$size;$i++)
				{
					$data['shop_id'] = $array[$i];
					D("SchoolShop")->add($data);
				}                    
                $this->success("添加成功", "Admin/AddShop/addAuthShop");
			}
			
        } else {
			
			$commpany_id = D("Admin")->where(array("id" =>  session("adminId")))->getField("company_id");  //查询账号关联的学校id
            $conditon = array("id" => $commpany_id);
           
            $adminInfo = D("XjSchool")->where($conditon)->find();    //根据学校id查询学校信息
           
			if(null!=$commpany_id &&''!=$commpany_id)		
            {			
			    $schoolShop = D("SchoolShop")->where(array("company_id" => $commpany_id))->select();    //学校店铺列表
            }else
            {
				$schoolShop = null;
			}				
			
			$shopList = D("Shop")->getShopList();
			
            $this->assign('shopList', $shopList);// 赋值数据集
			$this->assign('xjschool', $adminInfo);
			$this->assign('schoolShop', $schoolShop);
			
            $this->display();
        }
    }
  
}