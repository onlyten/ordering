<?php
namespace Home\Controller;

class ShopController extends BaseController
{
     //导出格式
    public function export()
    {
 
        $arrayt = array('shop_id','menu_id','menu_name','name','price','order_date');
        $shop_id=session("homeShopId");
        $condition = array(
            "shop_id" => $shop_id,
            'status' => '0'
        );
        $menulist = D("Menu")->getMenuList($condition, true);        
        $size = count($menulist);
        $arrayList= array();
        for($i=0;$i<$size;$i++)
        {
           $begin = $menulist[$i]['start_time'];
           $end = $menulist[$i]['end_time'];
           $id=  $menulist[$i]['id'];
           $menu_name = $menulist[$i]['name'];
           $price = $menulist[$i]['price'];
           for($j=0;strtotime($begin.'+'.$j.' days')<=strtotime($end)&&$j<366;$j++){
                    
                    $arrayOne = array();
                    array_push($arrayOne,$shop_id);
                    array_push($arrayOne,$id);
                    array_push($arrayOne,$menu_name);
                    array_push($arrayOne,'');
                    array_push($arrayOne,$price);
                    $time = strtotime($begin.'+'.$j.' days');
                    array_push($arrayOne,date('Y-m-d',$time));
                    array_push($arrayList,$arrayOne);                    
                   
             }                    
            
        }
       
       
         Vendor("PHPExcel.Excel#class");
         \Excel::export($arrayList,$arrayt);  //$tt为保存的时间 年-月/年-月-日
       
    }
   public function  import(){
        Vendor('PHPExcel.Excel','',".class.php");
        $config = array(
            'remove' => true,        //是否上传后删除文件
            'filename' => 'filename', //文件名称
            'rootpath' => './Public', //上传主目录
            'savepath' => '/Uploads/Files/Excel/',//上传子目录
            'filetype' => array('xls', 'xlsx'),//限制上传文件类型
            'fields' => array('shop_id','menu_id','menu_name','name','price','order_date'),//导入/导出文件字段[导入时为数据字段,导出时为字段标题]
            'datefield' => array(),//上传带日期时间格式字段
            'data' => array(), //导出Excel的数组
            'savename' => '',  //导出文件名称
            'title' => '',     //导出文件栏目标题
            'suffix' => 'xlsx',//文件格式
        );
        $excel = new \Excel($config);
        $excel->importPrivata();       
        $this->success("导入成功", cookie("prevUrl"));      
       
    }
    
    public function addShop()
    {
        if (IS_POST) {
            $data = I("post.");

						// dump($data);

            // if( floatval($data["zhekou"]) > 1 || floatval($data["zhekou"]) <= 0){
            //     $this->error("账户支付折扣的值只能设置(  0 < x <= 1 )" , "Home/Shop/modifyShop");
            // }

            if (!$data["id"]) {
                $data["user_id"] = session("homeId");
            }

	            unset($data["wd"]);	            
	               
	              $shop = D("Shop")->getShop(array("id" => $data["id"]), true);        
                if (session("homeShopId")) {   
               	if( $shop["bank"]!=$data["bank"] || $shop["bank_card"] != $data["bank_card"]){                         
							   $url=CONNECT."MerchantBankCardBind/".$data["id"]."/".urlencode($data["bank"])."/".$data["bank_card"];
							   //echo $url;
							   $result = $this->_request($url,true,'POST'); 
	               $result = json_decode($result);    
	               //var_dump($result);          
	               //echo $result[0]->success;
							   if($result[0]->success == '1'){	   
							   	    D("Shop")->addShop($data);                    
	                   $this->success("保存成功", "Home/Shop/modifyShop");                
		             }else{
		                 $this->error($result[0]->msg ,"Home/Shop/modifyShop");  
		            }  
		          }else{
		          	   D("Shop")->addShop($data);  
		          	   $this->success("保存成功", "Home/Shop/modifyShop");  
		          	}                 
              } else {
              	D("Shop")->addShop($data);
                $this->success("创建成功", U("Home/AddShop/shop"));
            }
                                                          
        
             
//            if (session("homeShopId")) {
//                $this->success("保存成功", "Home/Shop/modifyShop");
//            } else {
//                $this->success("创建成功", U("Home/AddShop/shop"));
//            }

        } else {
            $this->display();
        }
    }

    public function updateShop()
    {
//        $data = I("get.");
        M("Shop")->where(array("id" => array("in", I("get.id"))))->save(array("status" => I("get.status")));

        $this->success("审核成功", cookie("prevUrl"));
    }
    
       public function updateMenu()
    {
//      $data = I("get.");           
//        $sql = 'select count(*) count from multi_product a where a.menu_id="'.I("get.id").'" and a.file_id is not null and a.price is not null and a.name is not null';	
//        $condition['menu_id']=I("get.id");
//        $condition['_string'] = 'file_id is not null and price is not null and name is not null';
//        $count=D('Product')->getProductListCount($condition); 
//        $condition1['menu_id']=I("get.id");       
//       
//		    $sql1='SELECT  TIMESTAMPDIFF(DAY,a.start_time,a.end_time) count from multi_menu a where a.id="'.I("get.id").'"';
//        $count1 = M()->query($sql1);        
//        $count2=$count1[0]['count']; //7       
//        
//        if($count==$count2+1){       

      	   M("Menu")->where(array("id" => array("in", I("get.menu_id"))))->save(array("status" => '1'));
       		 $this->success("发布成功", "Home/Shop/menu");
//       }else{
//       	   $this->error("发布失败，请检查商品数据完整性", "Home/Shop/menu");
//       	}        
    }

    public function delShop()
    {
        D("Shop")->delShop(I("get.id"));

        $this->success("删除成功", cookie("prevUrl"));
    }

    public function modifyShop()
    {
        if (session("homeShopId")) {
            $id = session("homeShopId");
            $shop = D("Shop")->getShop(array("id" => $id), true);

            $username = array();
            $employee = explode(',', $shop["employee"]);
            foreach ($employee as $key => $value) {
                $user = D("User")->get(array("id" => $value));
                array_push($username, $user["username"]);
            }
            $shop["employeeName"] = implode(",", $username);
            $this->assign("shop", $shop);

            $this->display("Shop:addShop");
        } else {
            $this->error("请先选择店铺", "Home/Shop/shop");
        }
    }
    
     public function productlist(){
        //echo I('post');       
        $productlist = M('Product')->where(array("menu_id" => I('post.menu_id')))->field("menu_id,order_date,price,name")->select();;
        //var_dump($productlist);exit;
        echo json_encode($productlist);
    }

    public function switchShop()
    {
        if (I("get.id")) {
            session("homeShopId", I("get.id"));

            $shop = D("Shop")->getShop(array("id" => I("get.id")));
            session("homeShop", $shop);
        } else {
            session("homeShopId", null);
        }
        $this->redirect("Home/Index/index");
    }

//    public function selectEmployee()
//    {
//        $ids = array();
//        $employees = explode(",", I("post.name"));
//
//        if (count($employees)) {
//            foreach ($employees as $key => $value) {
//                $user = D("User")->getUser(array("username" => $value));
//                if ($user) {
//                    array_push($ids, $user["id"]);
//                } else {
//                    unset($employees[$key]);
//                }
//            }
//        } else {
//            $user = D("User")->getUser(array("username" => $employees));
//            array_push($ids, $user["id"]);
//        }
//
//        $this->ajaxReturn(array("id" => $ids, "name" => $employees));
//    }

    public function menu()
    {
        $condition = array(
            "shop_id" => session("homeShopId")
        );

        $menu = D("Menu")->getMenuList($condition, true);
        $menuModel = D("Menu");
        foreach ($menu as $key => $value) {
            $menu[$key]["parent"] = $menuModel->getMenu(array("id" => $value["pid"]));
        }
        $this->assign("menu", $menu);
        $this->display();
    }

    public function product()
    {
        
        $condition = array(
            "shop_id" => session("homeShopId")
        );
       
        $num = 25;
        $p = I("get.page") ? I("get.page") : 1;
        cookie("prevUrl", "Home/Shop/product/page/" . $p);

        $productList = D("Product")->getProductList($condition, true, "id desc", $p, $num);
       
        $this->assign('productList', $productList);// 赋值数据集
       
        $count = D("Product")->getProductListCount($condition);// 查询满足要求的总记录数
       
        $Page = new \Think\Page($count, $num);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show();// 分页显示输出

        $this->assign('page', $show);// 赋值分页输出
        $this->assign('url', "http://" . I("server.HTTP_HOST"));
        $this->display();
    }
    
    public function shop()
    {
        $num = 25;
        $p = I("get.page") ? I("get.page") : 1;
        cookie("prevUrl", "Home/Shop/shop/page/" . $p);

        $condition = array("user_id" => session("homeId"));
        $shopList = D("Shop")->getShopList($condition, true, "id desc", $p, $num);
        $this->assign('shopList', $shopList);// 赋值数据集

        $count = D("Shop")->getShopListCount($condition);// 查询满足要求的总记录数
        $Page = new \Think\Page($count, $num);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show();// 分页显示输出

        $this->assign('page', $show);// 赋值分页输出
        $this->assign('url', "http://" . I("server.HTTP_HOST"));
        $this->display();
    }

    public function addMenu()
    {
        if (IS_POST) {
            $data = I("post.");
            if (!$data["id"]) {
                $data["shop_id"] = session("homeShopId") ? session("homeShopId") : 0;
            }
            
            $timeRange = $data['start_time'] ;
            $timeRange1 = explode(" --- ", $timeRange);
            //计算时间差
            $start_time = $timeRange1[0];
            $end_time = $timeRange1[1];
            $data["start_time"]=$start_time;
            $data["end_time"]=$end_time;  
            
             $shopId=session("homeShopId");
             $name=$data["name"];        
         	   $price=$data["price"];
        		 $sql = 'Select	* FROM	multi_menu a WHERE	(a.name ="'.$name.'" and  a.price="'.$price.'" ) AND a.shop_id = "'.$shopId.'" and (a.end_time >= "'.$start_time.'" OR a.end_time >= "'.$end_time.'" )AND (	a.start_time <= "'.$start_time.'"	OR a.start_time <= "'.$end_time.'")';			            
        		//var_dump($sql);
		    		 $menulist = M()->query($sql);  // 查询套餐列表		     
				    if($menulist == null){   ////判断是否有套餐		 
				     D("Menu")->addMenu($data);            
             $price = $data["price"];
             $datap = array(
                       "price" => $price
                    );         
             $id = $data["id"];
             D("Product")->where('menu_id='.$id)->save($datap);             
              $this->success("保存成功", "Home/Shop/menu"); 			    
						}else{
							$this->error("套餐已存在,请重新输入" ,"Home/Shop/addMenu");  		
						}            
   
        } else {
        	  $menuList = D("Menu")->getMenuList(array("pid" => 0, "shop_id" => session("homeShopId") ? session("homeShopId") : 0));
            $this->assign("menuList", $menuList);
            $this->display();
        }
    }

    public function modifyMenu()
    {
        $menu = D("Menu")->getMenu(array("id" => I("get.id")), true);
        $time=$menu["start_time"].' --- '.$menu["end_time"];
        $menu{"start_time"}=$time;
      
        $this->assign("menu", $menu);

        $menuList = D("Menu")->getMenuList(array("pid" => 0));
        $this->assign("menuList", $menuList);

        $this->display("Shop:addMenu");
    }

    public function delMenu()
    {
        D("Menu")->delMenu(I("get.id"));

        $this->success("删除成功", "Home/Shop/menu");
    }
    
    
    //判断商品表是否存在订单 201607018
    public function getExistOrder()
    {
        $timeRange = I("post.timeRange");
		$menuId = I("post.menu_id");
		$map['menu_id'] = $menuId;
        $timeRange1 = explode(" --- ", $timeRange);
        //计算时间差
        $begin = $timeRange1[0];  
        $end = $timeRange1[1];
        $map['shop_id'] = session("homeShopId");
        $sql='SELECT count(1) as count,start_time as startDate,end_time as endDate  from multi_menu m where  m.status=0 and m.id="'.$menuId.'" and m.start_time<="'.$begin.'" and m.end_time>="'.$end.'"';

        
        $mcount = M()->query($sql);
        
        $total = $mcount[0]['count'];
       
        $count=0;
        for($i=0;strtotime($begin.'+'.$i.' days')<=strtotime($end)&&$i<365;$i++){
             
            $time = strtotime($begin.'+'.$i.' days');
			
            $map['order_date'] = date('Y-m-d',$time);
            $cou = D("Product")->where($map)->count();
            
            if($cou>0)
            {
                $count++;
            }
        }
    
        $this->ajaxReturn(
            array(
                "count" => $count,
                "shopId" => session("homeShopId"),
                "total" => $total,
                //"end"  => $endDate,
                //"begin"  =>  $startDate,
            )
        );
    }
    
    
     public function getCheckTime()
    {
        $timeRange = I("post.timeRange");
				$menuId = I("post.menu_id");
				$map['menu_id'] = $menuId;
        $timeRange1 = explode(" --- ", $timeRange);
        //计算时间差
        $begin = $timeRange1[0];  
        $end = $timeRange1[1];
        $map['shop_id'] = session("homeShopId");
        
        $shopId=session("homeShopId");
        
         $count=0;
         $sql = 'Select	* FROM	multi_menu a WHERE	a.id ="'.$menuId.'" AND a.shop_id = "'.$shopId.'" AND (	a.end_time >= "'.$begin.'" 	OR a.end_time >= "'.$end.'" )AND (	a.start_time <= "'.$begin.'"	OR a.start_time <= "'.$end.'")';			            
		     $menulist = M()->query($sql);  // 查询学校列表
		     
			    if($menulist == null){   ////判断是否有套餐		     
			      $count=0;
					}else{
						 $count=1;					
					}  
        $sql = D("Menu")->getLastSql();
		//var_dump($sql);
		//exit;
            $this->ajaxReturn(
            array(
                "count" => $count,
                "shopId" => session("homeShopId"),
               // "sql" => $sql,
               // "end"  => $timeRange1[1],
               // "begin"  =>  $timeRange1[0],
            )
        );
    }
	//根据菜单id查询套餐的价格
    public function getPrice()
	{
		
		$menuId = I("post.menuId");
		$menu = D("Menu")->where(array("id" => $menuId))->field("price,start_time,end_time")->find();
        
        $this->ajaxReturn(
            array(
                "price" => $menu['price'],
                "startDate" => $menu['start_time'],
                "endDate" => $menu['end_time'],
               
            )
        );
	}

    //拼接商品名称  过滤+
    public function var_replace($s){
        $tmp = preg_replace("/\\+{2,}/","+",$s);//替换所有 两个以上的+号
        $tmp = preg_replace("/^\\+/","",$tmp);//替换开头的+号
        $tmp = preg_replace("/\\+$/","",$tmp);//替换结尾的+号
        return $tmp;
    }
    public function addProduct()
    {
        $condition = array(
            "shop_id" => session("homeShopId"),
			"status"=>"0"
        );
      
        if (IS_POST) { 
            
            $data = I("post.");
            $datanew['shop_id'] = session("homeShopId");
            $datanew['detail'] = I("post.detail", '', '');
            $datanew['label'] = implode(',', I("post.label"));
            $datanew['file_id'] = I("post.file_id");
            $datanew['menu_id'] = I("post.menu_id");
            $datanew['remark'] = I("post.remark");
            $datanew['price'] = I("post.price");
           // $datanew['remark'] = I("post.remark");
            //$data['albums'] = implode(',', I("post.albums"));
            $timeRange = $data['order_date'] ;
            $timeRange1 = explode(" --- ", $timeRange);
            $name1 = $data['name1'];
            $name2 = $data['name2'];
            $name3 = $data['name3'];
            $name4 = $data['name4'];
            $name5 = $data['name5'];
            $name6 = $data['name6'];
           /* $name;
            if($name1!=null && $name1!='')
            {
                 $name=$name1;
            }*/
            $name = $name1.'+'.$name2.'+'.$name3.'+'.$name4.'+'.$name5.'+'.$name6;
            $tmpname = $this->var_replace($name);
          
            //计算时间差
            $begin = $timeRange1[0];
            $end = $timeRange1[1];
            $datanew['name'] = $tmpname;
            
          

            
            for($i=0;strtotime($begin.'+'.$i.' days')<=strtotime($end)&&$i<365;$i++){
                 
                $time = strtotime($begin.'+'.$i.' days');
                $datanew['order_date'] = date('Y-m-d',$time);
               
                D("Product")->add($datanew);
               // var_dump("-------sql-->".D("Product")->getLastSql());
            }
            
         
             $this->success("保存成功", cookie("prevUrl"));
        } else {
            $menuList= D("Menu")->getList($condition);
            $this->assign("menuList", $menuList);

            $labelList = D("ProductLabel")->getList();
            $this->assign("labelList", $labelList);
            
            $this->assign("flag", "modify");

            $this->display();
        }
    }

    public function changeProduct()
    {
        $condition = array(
            "shop_id" => session("homeShopId"),
			"status"=>"0"
        );
    
        if (IS_POST) {
    
            $data = I("post.");
            /*$data['shop_id'] = session("homeShopId");
            $data['detail'] = I("post.detail", '', '');
            $data['label'] = implode(',', I("post.label"));*/
           // $data['albums'] = implode(',', I("post.albums"));
            $datanew['shop_id'] = session("homeShopId");
            $datanew['detail'] = I("post.detail", '', '');
            $datanew['order_date'] = I("post.order_date");
            $datanew['file_id'] = I("post.file_id");
            $datanew['menu_id'] = I("post.menu_id");
            $datanew['remark'] = I("post.remark");
            $datanew['price'] = I("post.price");
            $datanew['id'] = I("post.id");

            $name1 = $data['name1'];
            $name2 = $data['name2'];
            $name3 = $data['name3'];
            $name4 = $data['name4'];
            $name5 = $data['name5'];
            $name6 = $data['name6'];
          
            $name = $name1.'+'.$name2.'+'.$name3.'+'.$name4.'+'.$name5.'+'.$name6;
            $tmpname = $this->var_replace($name);
            $datanew['name'] = $tmpname;
           
         
            D("Product")->add($datanew);
            $this->success("保存成功", cookie("prevUrl"));
            
          }else{
            $menuList = D("Menu")->getList($condition);
            $this->assign("menuList", $menuList);
    
            $labelList = D("ProductLabel")->getList();
            $this->assign("labelList", $labelList);
            //$this->assign("flag", "1");
            $this->display();
        }
    }
    
    public function modifyProduct()
    {
        $condition = array(
            "shop_id" => session("homeShopId"),
			"status"=>"0"
        );
        
        $product = D("Product")->get(array("id" => I("get.id")), array('menu', 'file'));
        $product["label"] = explode(",", $product["label"]);

        $albums = explode(",", $product["albums"]);
        //$product["albums"] = $albums ? D("File")->getList(array("id" => array("in", $albums))) : "";
        $this->assign("product", $product);
/*
        $own_menu = M("Menu")->where("id=".$product['menu_id'])->find();
        $menuList = D("Menu")->getList($condition);
        
        if(empty($menuList)){
            $menuList = array();
            $menuList[] = $own_menu;
        }
        $this->assign("menuList", $menuList);*/

        $labelList = D("ProductLabel")->getList();
        $this->assign("labelList", $labelList);

        // dump($product);

        $this->display("Shop:changeProduct");        
    }

    public function updateProduct()
    {
        $data = I("get.");
        $id = $data["id"];
        unset($data["id"]);
        D("Product")->updateProduct($id, $data);

        $this->success("保存成功", cookie("prevUrl"));
    }


    public function delProduct()
    {
        D("Product")->delProduct(I("get.id"));

        $this->success("删除成功", cookie("prevUrl"));
    }

    public function ads()
    {
        $condition = array(
            "shop_id" => session("homeShopId")
        );

        $num = 25;
        $p = I("get.page") ? I("get.page") : 1;
        cookie("prevUrl", "Home/Shop/ads/page/" . $p);

        $adsList = D("Ads")->getAdsList($condition, true, "id desc", $p, $num);
        $this->assign('ads', $adsList);// 赋值数据集

        $count = D("Ads")->getAdsListCount($condition);// 查询满足要求的总记录数
        $Page = new \Think\Page($count, $num);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show();// 分页显示输出

        $this->assign('page', $show);// 赋值分页输出
        $this->display();
    }

    public function addAds()
    {
        if (IS_POST) {
            $data = I("post.");

            if (!$data["id"]) {
                $data["shop_id"] = session("homeShopId") ? session("homeShopId") : 0;
            }

            D("Ads")->addAds($data);

            $this->success("保存成功", cookie("prevUrl"));
        } else {
            $this->display();
        }
    }

    public function modifyAds()
    {
        $ads = D("Ads")->getAds(array("id" => I("get.id")), true);
        $this->assign("ads", $ads);

        $this->display("Shop:addAds");
    }

    public function delAds()
    {
        D("Ads")->delAds(I("get.id"));

        $this->success("删除成功", cookie("prevUrl"));
    }
    
    
     public function modifyComment()
    {
        $comment = D("Comment")->getComment(array("id" => I("get.id")), true);
        $this->assign("comment", $comment);       
        $this->display("Shop:addComment");
    }

    public function comment()
    {
        $condition = array(
            "shop_id" => session("homeShopId")
        );

        $num = 25;
        $p = I("get.page") ? I("get.page") : 1;
        cookie("prevUrl", "Home/Shop/comment/page/" . $p);


        $comment = D("Comment")->getCommentList($condition, true, "id desc", $p, $num);
           foreach ($comment as $k => $val) {
            $where['dyzh'] = $val['user_id'];
            //学生信息 真实姓名
            $student = D('XjStudent')->where($where)->field('dyzh,xsxm')->find();
            $comment[$k]['student'] = $student;           
        }
        
         foreach ($comment as $k => $val) {
            $where['id'] = $val['detail_id'];
            //学生信息 真实姓名
            $orderDetail = D('OrderDetail')->where($where)->field('id,name')->find();
            //var_dump($orderDetail);
            $comment[$k]['orderDetail'] = $orderDetail;       
        }      
        
       
        $this->assign("comment", $comment);

        $count = D("Comment")->getCommentListCount($condition);// 查询满足要求的总记录数
        $Page = new \Think\Page($count, $num);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show();// 分页显示输出

        $this->assign('page', $show);// 赋值分页输出
        $this->display();
    }
    
      public function addComment()
    {
       if (IS_POST) {
            $data = I("post.");                    
            $where['id']=$data['id'];
            D("Comment")->where($where)->save($data);
            $this->success("保存成功", "Home/Shop/comment");
        } else {
        	  $this->display();
        }
    }
    

    public function delComment()
    {
        D("Comment")->delComment(I("get.id"));

        $this->success("删除成功", cookie("prevUrl"));
    }


    public function productSearch()
    {
        $condition = array(
            "shop_id" => session("homeShopId")
        );

        if (I("post.id")) {
            array_push($condition, array("id" => I("post.id")));
        }
       /* if (I("post.shop_id") || session("homeShopId")) {
            array_push($condition, array("shop_id" => I("post.shop_id") ? I("post.shop_id") : session("homeShopId")));
        }*/
        if (I("post.name")) {
            array_push($condition, array("name" => array("like", array("%" . I("post.name") . "%", "%" . I("post.name"), I("post.name") . "%"), 'OR')));
        }
        /*if (I("post.recommend") != -10) {
            array_push($condition, array("recommend" => I("post.recommend")));
        }*/
        /*if (I("post.status") != -10) {
            array_push($condition, array("status" => I("post.status")));
        }*/
        if (I("post.timeRange")) {
            $timeRange = I("post.timeRange");
            $timeRange = explode(" --- ", $timeRange);
            array_push($condition, array("order_date" => array('between', array($timeRange[0], $timeRange[1]))));
        }
       
        $productList = D("Product")->getProductList($condition, true);


        $this->assign("productPost", I("post."));
        $this->assign("productList", $productList);
        $this->display("product");
    }
    public function commentSearch()
    {
        
          $condition = array(
            "shop_id" => session("homeShopId")
        );
        if (I("post.timeRange")) {
            $timeRange = I("post.timeRange");
            $timeRange = explode(" --- ", $timeRange);
             array_push($condition, array("time" => array('between', array($timeRange[0], $timeRange[1]))));
           
        }


        $num = 25;
        $p = I("get.page") ? I("get.page") : 1;
        cookie("prevUrl", "Home/Shop/comment/page/" . $p);
       
        if(I("post.username"))
        {
           
             $studentList = D('XjStudent')->where("xsxm='".I("post.username")."'")->field('dyzh,xsxm')->select();
        }else
        {
           
             $studentList = D('XjStudent')->field('dyzh,xsxm')->select();
        }
      

        $comm = D("Comment")->getCommentList($condition, true, "id desc");
        
        $comment = array();
           foreach ($comm as $k => $val) {
            
            foreach ($studentList as $key => $value) {
                if($studentList[$key]['dyzh']==$comm[$k]['user_id'])
                {
                    //var_dump($comment[$k]['user_id']);
                    $comm[$k]['student'] =$studentList[$key];
                    array_push($comment , $comm[$k]);
                   
                }
                
            }
                  
        }
       
        
         foreach ($comment as $k => $val) {
            $where['id'] = $val['detail_id'];
            //学生信息 真实姓名
            $orderDetail = D('OrderDetail')->where($where)->field('id,name')->find();
            //var_dump($orderDetail);
            $comment[$k]['orderDetail'] = $orderDetail;       
        }      
        
       
        

        $count =count($comment);// D("Comment")->getCommentListCount($condition);// 查询满足要求的总记录数
        $commentList = array_slice($comment,0,24);

        $Page = new \Think\Page($count, $num);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show();// 分页显示输出

        $this->assign('page', $show);// 赋值分页输出
        $this->assign("comment", $commentList);
        $this->assign("commentPost", I("post."));
        $this->assign("comment", $comment);
        $this->display("comment");
    }

    public function exportProduct()
    {
        $condition = array(
            "shop_id" => session("homeShopId")
        );

        $product = D('Product')->getProductList($condition, true);
        foreach ($product as $key => $value) {
            unset($product[$key]["comment"]);
        }
        Vendor("PHPExcel.Excel#class");
        \Excel::export($product);
    }

    public function feedback()
    {
        $condition = array(
            "shop_id" => session("homeShopId")
        );

        $num = 25;
        $p = I("get.page") ? I("get.page") : 1;
        cookie("prevUrl", "Home/Shop/feedback/page/" . $p);

        $feedbackList = D("Feedback")->getFeedbackList($condition, false, "id desc", $p, $num);
        $this->assign('feedback', $feedbackList);// 赋值数据集

        $count = D("Feedback")->getFeedbackListCount();// 查询满足要求的总记录数
        $Page = new \Think\Page($count, $num);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show();// 分页显示输出

        $this->assign('page', $show);// 赋值分页输出
        $this->display();
    }

    public function exportFeedback()
    {
        if (I("get.id")) {
            $feedback = D("Feedback")->getFeedbackList(array("id" => array("in", I("get.id"))));
        } else {
            $condition = array(
                "shop_id" => session("homeShopId")
            );

            $feedback = D("Feedback")->getFeedbackList($condition);
        }

        Vendor("PHPExcel.Excel#class");
        \Excel::export($feedback);
    }

//崔
    public function label()
    {
        $condition = array(
            "shop_id" => session("homeShopId")
        );
        
        $label = D("ProductLabel")->getList($condition, false);
        $this->assign("label", $label);
        $this->display();
    }
    public function modLabel()
    {
        $label = D("ProductLabel")->get(array("id" => I("get.id")), false);
        $this->assign("label", $label);

        $this->display("Shop:addLabel");
    }

    public function addLabel()
    {
        if (IS_POST) {
            $data = I("post.");
            if (!$data["id"]) {
                $data["shop_id"] = session("homeShopId") ? session("homeShopId") : 0;
            }
            D("ProductLabel")->add($data);

            $this->success("保存成功", "Home/Shop/label");
        } else {
            $this->display();
        }
        
    }

    public function delLabel()
    {
        D("ProductLabel")->del(array("id" => array("in", I("get.id"))));

        $this->success("删除成功", "Home/Shop/label");
    }

    public function sku()
    {
        cookie("prevUrl", "Home/Shop/sku/id/" . I("get.id"));

        $condition = array(
            "shop_id" => session("homeShopId"),
            "product_id" => I("get.id")
        );

        $sku = D("ProductSku")->getList($condition);
        $this->assign("sku", $sku);

        $this->display();
    }

    public function addSku()
    {
        $new = I("post.new");
        $old = I("post.old");

        $skuModel = D("ProductSku");
        foreach ($new as $key => $value) {
            $new[$key]["product_id"] = I("post.product_id");
            $new[$key]["shop_id"] = session("homeShopId"); 
            $skuModel->add($new[$key]);
        }

        foreach ($old as $key => $value) {
            $old[$key]["product_id"] = I("post.product_id");
            $new[$key]["shop_id"] = session("homeShopId"); 
            $skuModel->save($old[$key]);
        }

        $this->success("操作成功", cookie("prevUrl"));
    }

    public function delSku()
    {
        D("ProductSku")->del(array("id" => I("get.id")));

        $this->success("删除成功", cookie("prevUrl"));
    }

}