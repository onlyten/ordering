<?php
namespace Admin\Controller;


class StasticsController extends BaseController
{
	
	 //导出
    public function export()
    {

        $year = I("get.year");  //年
		$month = I("get.month"); //月
		$temp= $year.'-'.$month;
		$firstday = date('Y-m-d', strtotime("$temp"));  //当月第一天
		$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day")); //当月最后一天
		$time = ' and orderDate between "'.$firstday.'" and "'.$lastday.'"';   //没有选择日则按月查询
		$day = I("get.day");  //日
		$tt= $year.'-'.$month;
		
		if(null!=$day&&''!=$day)
		{
			$tt = $tt.'-'.$day;
			$time=' and orderDate="'.date("Y-m-d",strtotime($tt)).'"';  ///选择了日则按天查询
			$daterange =  " and '".date("Y-m-d",strtotime($tt))."' BETWEEN  start_time AND end_time";
		}else
		{
			$daterange = " and '".date('Y-m', strtotime("$temp"))."' BETWEEN  DATE_FORMAT (start_time,'%Y-%m') AND DATE_FORMAT (end_time,'%Y-%m')";
		}			
		$schoolid =D("Admin")->where(array("id" =>  session("adminId")))->getField("company_id"); //session("schoolId");  //学校id		       
        $shop_id = I("get.shopId"); 
		
		$arrayList = array();
		//$menulist = D("Menu")->where(array("shop_id"=>$shop_id))->select();    //菜单列表
		$menuSql = "select id,name from multi_menu where shop_id=".$shop_id.$daterange;		
		$menulist = M()->query($menuSql);
		//var_dump($menulist);
		$condition = ' where shopId = '.$shop_id;
		$condition = $condition.' and schoolId ="'.$schoolid.'"';	
		$condition = $condition.$time;
		
		//根据学校id 店铺id 查询班级列表
		//$allClass = 'SELECT c.id,c.bjmc from multi_school_class sc,multi_xj_class c where '.
         //           'sc.class_id=c.id and sc.school_id="'.$schoolid.'"';		
					
		//$clist = M()->query($allClass);   //按班级和套餐分组查询的统计
		$clist = D("XjClass")->where(array("school_id"=>$schoolid))->field('id,bjmc')->order('(bh+0) asc')->select();  //根据学校id查出所有班级
		$clazzList = array();    //最后存班级列表
		foreach($clist as $keyc => $valuec) {
			    
			      //循环班级列表获得班级最后以班级id为一行 查询这个班级对应的所订套餐的条数
			
				  $arrayOne = array();
				  $valclassID=$valuec['id'];
				  $valclassName =  $valuec['bjmc'];
				  array_push($arrayOne,$valclassName);
				  if(!in_array("$valclassID", $clazzList)){   //判断数组中是否包含某个值
				     array_push($clazzList,$valclassID);   //将id存到某个数组
					 //$i=0;   //备用
					 $cou=0;   //总计
					 $arr = array();  //将几条套餐定义成一个数组存入arrayList
					 foreach($menulist as $key => $value) {
                              
		                foreach($value as $key2 => $val2) {
							if($key2=='id')//得到菜单id
							{
								$test = ' and menuId="'.$val2.'" and classId="'.$valclassID.'"';		                       
								
								$countSql = 'SELECT c.className,c.classId,count(1) as count,c.userId,c.menuId,c.menuName, '.'c.orderDate FROM multi_count c'.$condition.$test.'and !(c.leaveStatus=1 or c.retreatStatus=1)'; 
		
		                        //查询某个班级某个套餐的总数
		                        $conelist = M()->query($countSql);						
	                            
								if($conelist == null)   ////判断是否有套餐
								{
									 array_push($arrayOne,0);
									// array_push($arr,0);   //如果没有套餐则设置成0
									 $cou = $cou+0;   //计算菜单的总数量
								}else
								{
									
									foreach($conelist as $key6 => $val6)
									{
								
											$cout = $val6['count'];
											
												if($cout==null||$cout=='')
												{
													$cout=0;
												}
												 array_push($arrayOne,$cout);
												 //array_push($arr,$cout);   //主键对应第几个菜单的数量放到数组
												 $cou = $cou+$cout;   //计算菜单的总数量
																					
									}
								}							
								
							}						
			                
						}
						$i++;
						
					 }					 
					 array_push($arrayOne,$cou);					
					 array_push($arrayList,$arrayOne);  //将每个班级的所有套餐为一组放入arrayList
				  }
			}
		
		 $arrayt = array("班级");
		 foreach($menulist as $keyt=>$valuet)
		 {
			 array_push($arrayt,$valuet['name']);
		 }
		 array_push($arrayt,"总计");      
	     
		 
         Vendor("PHPExcel.Excel#class");
         \Excel::export($arrayList,$arrayt,$tt);  //$tt为保存的时间 年-月/年-月-日
       
    }


    public function  statistics()
    {
		
		///$shopId = 201;    //获取店铺
		$num =25;
        $p = I("get.page") ? I("get.page") : 1;
        cookie("prevUrl", "Home/Stastics/statistics/page/" . $p);
		$schoolid = D("Admin")->where(array("id" =>  session("adminId")))->getField("company_id");// session("schoolId") ;
	    //查询班级对应的店铺列表
		$sqlshop = 'SELECT s.id,s.name from multi_school_shop ss,multi_shop s  '.
                  'where ss.shop_id=s.id and ss.company_id="'.$schoolid.'"';  
		$vlist = M()->query($sqlshop);  // 查询学校列表
	
		if (IS_GET) {
			
			$data = I("get.");
			if (I("get.shopId")) {
				$shopId = $data['shopId'];
				
			}else
			{
				foreach($vlist as $keyv =>$valv)
				{
					
					if(null!=$vlist)
					{
						$shopId = $valv['id'];
						break;
					}
				}
			    
			}
			if(!I("get.year")&&!I("get.month")&&!I("get.day"))
			{
				$time=' and orderDate="'.date("Y-m-d",time()).'"';  ///临时屏蔽
				$daterange =  " and '".date("Y-m-d",time())."' BETWEEN  start_time AND end_time";
			}
			
			if (I("get.year")) {
				$year = $data['year'];
				
			}
			if (I("get.month")) {
				$month = $data['month'];
				
				$temp= $year.'-'.$month;
				$firstday = date('Y-m-d', strtotime("$temp"));  //当月第一天
				$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day")); //当月最后一天
				$time = ' and orderDate between "'.$firstday.'" and "'.$lastday.'"';
				$daterange = " and '".date('Y-m', strtotime("$temp"))."' BETWEEN  DATE_FORMAT (start_time,'%Y-%m') AND DATE_FORMAT (end_time,'%Y-%m')";
				$this->assign("productPost", I("get."));
			}
			if (null!=I("get.day")&&''!=I("get.day")) {
				$day = $data['day'];
				$tt = $year.'-'.$month.'-'.$day;
				$time=' and orderDate="'.date("Y-m-d",strtotime($tt)).'"';  ///转换成标准格式
				$daterange =  " and '".date("Y-m-d",strtotime($tt))."' BETWEEN  start_time AND end_time";
				$this->assign("productPost", I("get."));
			}
			
			
			
		}else if (IS_POST) {
			$data = I("post.");
			if (I("post.shopId")) {
				$shopId = $data['shopId'];
				
			}
			
			if (I("post.year")) {
				$year = $data['year'];
				
			}
			if (I("post.month")) {
				$month = $data['month'];
				
				$temp= $year.'-'.$month;
				$firstday = date('Y-m-d', strtotime("$temp"));  //当月第一天
				$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day")); //当月最后一天
				$time = ' and orderDate between "'.$firstday.'" and "'.$lastday.'"';
				$daterange = " and '".date('Y-m', strtotime("$temp"))."' BETWEEN  DATE_FORMAT (start_time,'%Y-%m') AND DATE_FORMAT (end_time,'%Y-%m')";
			}
			if (null!=I("post.day")&&''!=I("post.day")) {
				$day = $data['day'];
				$tt = $year.'-'.$month.'-'.$day;
				$time=' and orderDate="'.date("Y-m-d",strtotime($tt)).'"';  ///转换成标准格式
				$daterange =  " and '".date("Y-m-d",strtotime($tt))."' BETWEEN  start_time AND end_time";
			}
			
			
			$this->assign("productPost", I("post."));
		}else
		{
			
				//查询班级对应的店铺列表
		    //$sqlshop = 'SELECT s.id,s.name from multi_school_shop ss,multi_shop s  '.
            //      'where ss.shop_id=s.id and ss.company_id="'.$schoolid.'"';  
			//$vlist = M()->query($sql);  // 查询学校列表
			foreach($vlist as $keyv =>$valv)
			{
				
				if(null!=$vlist)
				{
					$shopId = $valv['id'];
					break;
				}
			}
			$time=' and orderDate="'.date("Y-m-d",time()).'"';  ///临时屏蔽
			$daterange =  " and '".date("Y-m-d",time())."' BETWEEN  start_time AND end_time";
							
		}
		$arrayList = array();
		//$menulist = D("Menu")->where(array("shop_id"=>$shopId))->select();    //菜单列表
		$menuSql = "select id,name from multi_menu where shop_id=".$shopId.$daterange;		
		$menulist = M()->query($menuSql);

		$condition = ' where shopId = '.$shopId;
		$condition = $condition.' and schoolId ="'.$schoolid.'"';
		$condition = $condition.$time;
		
		//$allClass = 'SELECT c.id,c.bjmc from multi_school_class sc,multi_xj_class c where '.
        //           'sc.class_id=c.id and sc.school_id="'.$schoolid.'"';
		
		//$clist = M()->query($allClass);  //查询所有班级
		//$clist = D("XjClass")->where(array("school_id"=>$schoolid))->field('id,bjmc')->select();  //根据学校id查出所有班级
		$clist = D("XjClass")->where(array("school_id"=>$schoolid))->field('id,bjmc')->limit(($p-1)*$num,$num)->order('(bh+0) asc')->select();  //根据学校id 查询所有班级列表
		$clazzList = array();    //最后存班级列表
		foreach($clist as $keyc => $valuec) {
			    
			//   if($keyc2=='classid')   //循环班级列表获得班级最后以班级id为一行 查询这个班级对应的所订套餐的条数
			//   {
				  $arrayOne = array();
				
				  $valclassID=$valuec['id'];
				  $valclassName =  $valuec['bjmc'];
				  if(!in_array("$valclassID", $clazzList)){   //判断数组中是否包含某个值
				     array_push($clazzList,$valclassID);   //将id存到某个数组
					 $cou=0;   //总计
					 $arr = array();  //将几条套餐定义成一个数组存入arrayList
					 foreach($menulist as $key => $value) {
                              
		                foreach($value as $key2 => $val2) {
							if($key2=='id')//得到菜单id
							{
								$test = ' and menuId="'.$val2.'" and classId="'.$valclassID.'"';		                       
								
								$countSql = 'SELECT c.className,c.classId,count(1) as count,c.userId,c.menuId,c.menuName, '.'c.orderDate FROM multi_count c'.$condition.$test.'and !(c.leaveStatus=1 or c.retreatStatus=1)';///.'  GROUP BY c.classId';		 
		
		                        //查询某个班级某个套餐的总数
		                        $conelist = M()->query($countSql);						
	                           
								if($conelist == null)   ////判断是否有套餐
								{
									
									 array_push($arr,0);   //如果没有套餐则设置成0
									 $cou = $cou+0;   //计算菜单的总数量
								}else
								{
									
									foreach($conelist as $key6 => $val6)
									{
								
											$cout = $val6['count'];
											
												if($cout==null||$cout=='')
												{
													$cout=0;
												}
												 array_push($arr,$cout);   //主键对应第几个菜单的数量放到数组
												 $cou = $cou+$cout;   //计算菜单的总数量
												
										
										
									}
								}
								
								
							}
							
			                
						}
						//$i++;
						
					 }
					 //$arrayOne['sum'] = $i;
					 $arrayOne['num'] = $cou;  //将套餐总计放入数组
					 $arrayOne['list'] = $arr;  //将几条套餐定义成一个数组存入arrayList
					
					 $arrayOne['classname'] = $valclassName;
					 $arrayOne['classid'] = $valclassID;
					  
					 array_push($arrayList,$arrayOne);  //将每个班级的所有套餐为一组放入arrayList
				  }
				 
			  // }
			   
		  // }
		  
		}
		$count= D("XjClass")->where(array("school_id"=>$schoolid))->field('id,bjmc')->count(); 
		$Page = new \Think\Page($count, $num);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show();// 分页显示输出

        $this->assign('page', $show);// 赋值分页输出
		$this->assign("shop_id",$shop_id);		
		$this->assign("vlist",$vlist);		
		$this->assign("arrayList",$arrayList);		
		$this->assign("menulist",$menulist);
        $this->display();
    }
	public function detail(){
		if(!empty(I('get.day'))){
			//按天查询
			$this->detailone();
			$this->display('Stastics_detail');
		}elseif(!empty(I('get.month')) && !empty(I('get.year'))){
			//按月查询
			$this->statisClass();
			$this->display('Stastics_statisClass');
		}
	}
	//私有化方法
	private function detailone(){
		$shop_id = I('get.shopId');
		//班级  商家id
		$class = D('XjClass')->field('id,bjmc,bjjc,school_id')->where('id="'.I('get.classid').'"')->find();
		$table = '<table class="table table-bordered table-hover"><tbody>';
		//按天查询
		$table.= "<tr><th>班级</th><th colspan='2'>".$class['bjmc']."</th></tr>";
		$table.="<tr><th>姓名</th><th colspan='2'>".I('get.month')."月".I('get.day')."日</th></tr>";
		//学生
		$students = D('XjStudent')->where('bj="'.I('get.classid').'"')->field('id,xsxm,bj,dyzh')->select();			
		foreach ($students as $sk => $sv) {
			$condition = array();
			array_push($condition,array(
				'_string' => 'leavestatus != 1 AND retreatstatus != 1'
			));
			array_push($condition, array(
				'orderDate'=>I('get.year').'-'.I('get.month').'-'.I('get.day')
			));
			array_push($condition,array(
				'shopId' => $shop_id
			));
			array_push($condition,array(
				'classId' => I('get.classid')
			));
			array_push($condition,array(
				'userId' => $sv['dyzh']
			));
			
			$count = D('Count')->where($condition)->field('shopid,orderid,menuname,menuid,leavestatus,retreatstatus')->select();
			
			/*if(empty($count)){
				$students[$sk]['menuname'] = null;
				$students[$sk]['menuid'] = null;
			}*/
			$ncount = array();
			foreach($count as $ck => $cv){
				//$icount[] = $cv['menuid'];
				$ncount[] = $cv['menuname'];

			}
			$tj = array_count_values($ncount);
			
			$shuoming = '';
			foreach($tj as $tjk => $tjv){
				$shuoming = $shuoming.$tjk.'×'.$tjv."&nbsp;&nbsp;&nbsp;";
			}

			$students[$sk]['shuoming'] = $shuoming;


			//判断请假，退餐两个字段为1，套餐数目为0
			if($count['leavestatus'] != 1 && $count['retreatstatus'] != 1){
				$students[$sk]['menuname'] = $count['menuname'];
				$students[$sk]['menuid'] = $count['menuid'];
			}else{
				if($count['leavestatus'] == 1){
					$students[$sk]['menuname'] = '已请假';
				}
				if($count['retreatstatus'] == 1){
					$students[$sk]['menuname'] = '已退餐';
				}
			}
		}
	
		$menus = D('Menu')->where('shop_id='.$shop_id)->field('id,name,shop_id')->order('time asc')->select();
		
		foreach ($students as $key => $value) {
			foreach ($menus as $k => $v) {

				if($value['menuid'] == $v['id']){
					$menus[$k]['num']++;
				}
			}
			$table.="<tr><td>".$value['xsxm']."</td><td>".$value['shuoming']."</td><td></td></tr>";
		}
		$table.="</tbody></table>";

		$tabletwo = '<table class="table table-bordered table-hover"><tbody>';
		$tabletwo.= '<tr><th rolspan="2">合计</th></tr>';


		$time=' and orderDate="'.I('get.year').'-'.I('get.month').'-'.I('get.day').'"'; 
		$arrayOne = array();
	  	$valclassID=$class['id'];
	  	$valclassName = $class['bjmc'];

	  	$condition = ' where shopId = '.$shop_id;
		$condition = $condition.' and schoolId ="'.$class['school_id'].'"';;
		$condition = $condition.$time;
	  	$menulist = D('Menu')->where('shop_id='.$shop_id)->field('id,name,shop_id')->order('time asc')->select();
	    
		 $cou=0;   //总计
		 $arr = array();  //将几条套餐定义成一个数组存入arrayList
		
		 foreach($menulist as $key => $value) {
            foreach($value as $key2 => $val2) {
				if($key2=='id')//得到菜单id
				{
					//var_dump($value['name']);
					$test = ' and menuId="'.$val2.'" and classId="'.$valclassID.'"';
					$countSql = 'SELECT c.className as nn,c.classId,count(1) as count,c.userId,c.menuId,c.menuName as mn, '.'c.orderDate FROM multi_count c'.$condition.$test.'and !(c.leaveStatus=1 or c.retreatStatus=1)';
                    //查询某个班级某个套餐的总数
                    $conelist = M()->query($countSql);

					if($conelist == null)   ////判断是否有套餐
					{									
						 array_push($arr,0);   //如果没有套餐则设置成0
						 $cou = $cou+0;   //计算菜单的总数量
					}else{									
						foreach($conelist as $key6 => $val6){								
							$cout = $val6['count'];	
							if($cout==null||$cout==''){
								$cout=0;
							}

							array_push($arr,array($value['name']=>$cout));   //主键对应第几个菜单的数量放到数组
							$cou = $cou+$cout;   //计算菜单的总数量		
						}
					}
				}
			}
			
		 }

		 $arrayOne['list'] = $arr;  //将几条套餐定义成一个数组存入arrayList
		 
		 foreach($arr as $mk => $mv){
			foreach($mv as $aok=>$aov){
				$tabletwo.="<tr><td>".$aok."</td><td>".$aov."</td></tr>";
			}
			
		}

		$tabletwo.= '<tr><td>总计</td><td>'.$cou.'</td></tr>';
		$tabletwo.= '</tbody></table>';

		$this->assign('table',$table);
		$this->assign('tabletwo',$tabletwo);
	}


	//每个班级按月查询
	private function  statisClass()
    {	
		
		$shop_id=I('get.shopId');   //session("homeShopId");
		$arrayDate = array();  // 日期数组
		$arrDate = array();  // 日期数组循环使用
		if (I("get.classid")) {
			$classid = I("get.classid");
				
		}
		if (I("get.year")) {
			$year = I("get.year");
			
		}
		if (I("get.month")) {
			$month = I("get.month");				
			$temp= $year.'-'.$month;
			
			$firstday = date('Y-m-d', strtotime("$temp"));  //当月第一天
			$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day")); //当月最后一天
				
			for($i=0;strtotime($firstday.'+'.$i.' days')<=strtotime($lastday)&&$i<35;$i++)
		    {             
				$time = strtotime($firstday.'+'.$i.' days');	
                if(date('w',date('Y-m-d',$time))!=6 && date('w',date('Y-m-d',$time)) != 0)	
                {				
					array_push($arrayDate,date('Y-m-d',$time));
					array_push($arrDate,date('m-d',$time));	
                }					
					
			}
		}
		
		$arrayList = array();  //存储最后的list循环列表
		//查询班级所有名字
		$allStudent = 'SELECT s.xsxm,s.id,s.bj,s.dyzh from multi_xj_class c,multi_xj_student s where '.
                    's.bj = c.id and c.id="'.$classid.'"';
		$stulist = M()->query($allStudent);  //查询所有学生
		//根据学生dyzh和classid以及日期查询当天的套餐
		foreach($stulist as $keys=>$values)
		{
			$arrayOne = array();
			$arr = array();
			$stuName = $values['xsxm'];  //学生姓名
			$userId = $values['dyzh'];   //对应账户 对应count表userId
			$arrayOne['stuName'] = $stuName;  //学生姓名
			foreach($arrayDate as $key=>$datevalue)
		    {
								
				$stuFoodsql = 'SELECT c.userId,c.userName,c.menuId,c.menuName,c.leaveStatus,c.retreatStatus ,'.
				               'c.orderDate from multi_count c  ,multi_menu m  where  m.id = c.menuId and m.shop_id='.$shop_id. 
                               ' and c.classId="'.$classid.'" and c.userId="'.$userId.'" and c.orderDate="'.$datevalue.'"';
				$stuFoodList = M()->query($stuFoodsql);  //查询学生某天的套餐
				$tempt='';
				$ctto = count($stuFoodList);   //每个用户每天的总条数
				if(null!=$stuFoodList)
				{
					$qingjia=0; //请假数量
					$tuican=0;  //退餐数量
					//$mingc=0;  //套餐数量
					$tancArr = array();   //存所有套餐和数量
					//echo $stuFoodList[0]['menuname'];
					for($e=0;$e<$ctto;$e++)
					{
						if($stuFoodList[$e]['leavestatus']==1)
						{
							$qingjia++;
						}
						else if($stuFoodList[$e]['retreatstatus']==1)
						{
							$tuican++;
						}
						else
						{
							//$mingc++;   //当天套餐总量
							//array_push($tancArr,array($stuFoodList[$e]['menuname']=>1));
							/*********************************************************************/
							$tmp=0;
							foreach($tancArr as $key1=>$value1)
								{
									
									foreach($value1 as $oldkey=>$oldvalue)
									{
										
										if($oldkey==$stuFoodList[$e]['menuname'])
										{
											
											$oldvalue = $oldvalue+'1';											
											$tancArr[$key1][$oldkey]=$oldvalue ;  //如果有此套餐则数量加1
											$tmp++;
											
										}
										
									}
									
								}
								if($tmp<=0)
								{
									array_push($tancArr,array($stuFoodList[$e]['menuname']=>1));  //如果没有此套餐则加一份
								}
							/*****************************************************************/
	
						}
					}
					$tto = '';    //存储套餐字符串
					foreach($tancArr as $key1=>$value1)
					{
						foreach($value1 as $oldkey=>$oldvalue)
						{
							$tto = $tto.$oldkey.'×'.$oldvalue.'; ';
						}
					}
					
					$tempt = $tto;
					if($qingjia>0)
					{
						$tempt = $tempt.' 请假×'.$qingjia;
					}
					if($tuican>0)
					{
						$tuican = $tuican.' 退餐×'.$tuican;
					}
					
					/*
					foreach($stuFoodList as $stuKey=>$stuValue)
					{
						if($stuValue['leavestatus']==1)
						{
							array_push($arr,"请假");
						}else if($stuValue['retreatstatus']==1)
						{
							array_push($arr,"退餐");
						}else
						{
							//$tempt =$tempt.'-'.$stuValue['menuname'];
							
						}
						
					}
					*/
					array_push($arr,$tempt);
				}else
				{
					
					array_push($arr,' ');
				}
				
				$arrayOne['list'] = $arr;
				
		    }
			
			array_push($arrayList,$arrayOne);
		
		}
		$this->assign("arrayList",$arrayList);
		$this->assign("year",$year);
		$this->assign("month",$month);
		$this->assign("classid",$classid);
		$this->assign("shopId",$shop_id);
		$this->assign("arrDate",$arrDate);	
    }

	 //导出班级套餐详情
    public function exportClass()
    {
		
		$shop_id=I("get.shopId");  //session("homeShopId");		
		$arrayDate = array();  // 日期数组
		$arrayt = array("班级");
		if (I("get.classid")) {
			$classid = I("get.classid");
				
		}
		if (I("get.year")) {
			$year = I("get.year");
			
		}
		if (I("get.month")) {
			$month = I("get.month");				
			$temp= $year.'-'.$month;			
			$firstday = date('Y-m-d', strtotime("$temp"));  //当月第一天
			$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day")); //当月最后一天		
				
			for($i=0;strtotime($firstday.'+'.$i.' days')<=strtotime($lastday)&&$i<35;$i++){
             
				$time = strtotime($firstday.'+'.$i.' days');			
				array_push($arrayDate,date('Y-m-d',$time));
				array_push($arrayt,date('m-d',$time));
			
					
			}
		}
		
		
		$arrayList = array();  //存储最后的list循环列表
	
		//查询班级所有名字
		$allStudent = 'SELECT s.xsxm,s.id,s.bj,s.dyzh from multi_xj_class c,multi_xj_student s where '.
                    's.bj = c.id and c.id="'.$classid.'"';
		$stulist = M()->query($allStudent);  //查询所有学生
		//根据学生dyzh和classid以及日期查询当天的套餐
		foreach($stulist as $keys=>$values)
		{
			
			$arr = array();
			$stuName = $values['xsxm'];  //学生姓名
			$userId = $values['dyzh'];   //对应账户 对应count表userId			
			array_push($arr,$stuName);
			foreach($arrayDate as $key=>$datevalue)
		    {
				
				$stuFoodsql = 'SELECT c.userId,c.userName,c.menuId,c.menuName,c.leaveStatus,c.retreatStatus ,'.
				               'c.orderDate from multi_count c  ,multi_menu m  where  m.id = c.menuId and m.shop_id='.$shop_id. 
                               ' and c.classId="'.$classid.'" and c.userId="'.$userId.'" and c.orderDate="'.$datevalue.'"';
				$stuFoodList = M()->query($stuFoodsql);  //查询学生某天的套餐
			
				$tempt='';
				$ctto = count($stuFoodList);   //每个用户每天的总条数
				if(null!=$stuFoodList)
				{
					$qingjia=0; //请假数量
					$tuican=0;  //退餐数量
					//$mingc=0;  //套餐数量
					$tancArr = array();   //存所有套餐和数量
					//echo $stuFoodList[0]['menuname'];
					for($e=0;$e<$ctto;$e++)
					{
						if($stuFoodList[$e]['leavestatus']==1)
						{
							$qingjia++;
						}
						else if($stuFoodList[$e]['retreatstatus']==1)
						{
							$tuican++;
						}
						else
						{
							//$mingc++;   //当天套餐总量
							//array_push($tancArr,array($stuFoodList[$e]['menuname']=>1));
							/*********************************************************************/
							$tmp=0;
							foreach($tancArr as $key1=>$value1)
								{
									
									foreach($value1 as $oldkey=>$oldvalue)
									{
										
										if($oldkey==$stuFoodList[$e]['menuname'])
										{
											
											$oldvalue = $oldvalue+'1';											
											$tancArr[$key1][$oldkey]=$oldvalue ;  //如果有此套餐则数量加1
											$tmp++;
											
										}
										
									}
									
								}
								if($tmp<=0)
								{
									array_push($tancArr,array($stuFoodList[$e]['menuname']=>1));  //如果没有此套餐则加一份
								}
							/*****************************************************************/
	
						}
					}
					$tto = '';    //存储套餐字符串
					foreach($tancArr as $key1=>$value1)
					{
						foreach($value1 as $oldkey=>$oldvalue)
						{
							$tto = $tto.$oldkey.'×'.$oldvalue.'; ';
						}
					}
					
					$tempt = $tto;
					if($qingjia>0)
					{
						$tempt = $tempt.' 请假×'.$qingjia;
					}
					if($tuican>0)
					{
						$tuican = $tuican.' 退餐×'.$tuican;
					}
					
					/*
					foreach($stuFoodList as $stuKey=>$stuValue)
					{
						if($stuValue['leavestatus']==1)
						{
							array_push($arr,"请假");
						}else if($stuValue['retreatstatus']==1)
						{
							array_push($arr,"退餐");
						}else
						{
							//$tempt =$tempt.'-'.$stuValue['menuname'];
							
						}
						
					}
					*/
					array_push($arr,$tempt);
				}else
				{
					
					array_push($arr,' ');
				}	
				
		    }
			
			array_push($arrayList,$arr);
		
		}
		
          Vendor("PHPExcel.Excel#class");
         \Excel::export($arrayList,$arrayt,"");  //$tt为保存的时间 年-月/年-月-日
       
    }
	
	public function exportone(){
		$shop_id = I('get.shopId');
		$class = D('XjClass')->field('id,bjmc,bjjc,school_id')->where('id="'.I('get.classId').'"')->find();
		$firstarr = array('title'=>'班级','cont'=>$class['bjmc']);
		$secondarr = array('title'=>'姓名','cont'=>I('get.month')."月".I('get.day')."日");
		//学生
		$students = D('XjStudent')->where('bj="'.I('get.classId').'"')->field('xsxm,dyzh')->select();
		foreach ($students as $sk => $sv) {
			$condition = array();
			array_push($condition,array(
				'_string' => 'leavestatus != 1 AND retreatstatus != 1'
			));
			array_push($condition, array(
				'orderDate'=>I('get.year').'-'.I('get.month').'-'.I('get.day')
			));
			array_push($condition,array(
				'shopId' => $shop_id
			));
			array_push($condition,array(
				'classId' => I('get.classId')
			));
			array_push($condition,array(
				'userId' => $sv['dyzh']
			));
			$count = D('Count')->where($condition)->field('shopid,orderid,menuname,menuid,leavestatus,retreatstatus')->select();
			if(empty($count)){
				$students[$sk]['menuname'] = null;
				$students[$sk]['menuid'] = null;
			}

			$ncount = array();
			foreach($count as $ck => $cv){
				//$icount[] = $cv['menuid'];
				$ncount[] = $cv['menuname'];

			}
			$tj = array_count_values($ncount);
			$shuoming = '';
			foreach($tj as $tjk => $tjv){
				$shuoming = $shuoming.$tjk.'×'.$tjv."   ";
			}
			$students[$sk]['shuoming'] = $shuoming;

			//判断请假，退餐两个字段为1，套餐数目为0
			if($count['leavestatus'] != 1 && $count['retreatstatus'] != 1){
				$students[$sk]['menuname'] = $count['menuname'];
				$students[$sk]['menuid'] = $count['menuid'];
			}else{
				if($count['leavestatus'] == 1){
					$students[$sk]['menuname'] = '已请假';
				}
				if($count['retreatstatus'] == 1){
					$students[$sk]['menuname'] = '已退餐';
				}
			}
		}
		$menus = D('Menu')->where('shop_id='.$shop_id)->field('id,name')->order('time asc')->select();
		foreach ($students as $key => $value) {
			foreach ($menus as $k => $v) {
				if($value['menuid'] == $v['id']){
					$menus[$k]['num']++;
				}
			}
		}
		array_push($students,array('title'=>'合计','cont'=>''));

		$time=' and orderDate="'.I('get.year').'-'.I('get.month').'-'.I('get.day').'"'; 
		$arrayOne = array();
	  	$valclassID=$class['id'];
	  	$valclassName = $class['bjmc'];

	  	$condition = ' where shopId = '.$shop_id;
		$condition = $condition.' and schoolId ="'.$class['school_id'].'"';;
		$condition = $condition.$time;
	  	$menulist = D('Menu')->where('shop_id='.$shop_id)->field('id,name,shop_id')->order('time asc')->select();
	    
		 $cou=0;   //总计
		 $arr = array();  //将几条套餐定义成一个数组存入arrayList
		
		 foreach($menulist as $key => $value) {
            foreach($value as $key2 => $val2) {
				if($key2=='id')//得到菜单id
				{
					$test = ' and menuId="'.$val2.'" and classId="'.$valclassID.'"';
					$countSql = 'SELECT c.className as nn,c.classId,count(1) as count,c.userId,c.menuId,c.menuName as mn, '.'c.orderDate FROM multi_count c'.$condition.$test.'and !(c.leaveStatus=1 or c.retreatStatus=1)';///.'  GROUP BY c.classId';		
                    //查询某个班级某个套餐的总数
                    $conelist = M()->query($countSql);
					if($conelist == null)   ////判断是否有套餐
					{									
						 array_push($arr,0);   //如果没有套餐则设置成0
						 $cou = $cou+0;   //计算菜单的总数量
					}else{									
						foreach($conelist as $key6 => $val6){								
							$cout = $val6['count'];	
							if($cout==null||$cout==''){
								$cout=0;
							}

							array_push($arr,array($value['name']=>$cout));   //主键对应第几个菜单的数量放到数组
							$cou = $cou+$cout;   //计算菜单的总数量		
						}
					}
				}
			}			
		}
		
		foreach($menus as $mk => $mv){
			foreach($arr as $amk => $amv){
				foreach($amv as $aok=>$aov){
					if($aok == $mv['name']){
						$menus[$mk]['price'] = $aov;
					}					
				}			
			}
			unset($menus[$mk]['id']);			
		}
		foreach ($menus as $key => $value) {
			array_push($students,$value);
		}
		
		array_unshift($students,$firstarr,$secondarr);
		array_push($students,array('title'=>'总计','cont'=>$cou));
		foreach ($students as $kk => $vv) {
			unset($students[$kk]['dyzh']);
			unset($students[$kk]['menuid']);
			unset($students[$kk]['id']);
		}
		Vendor("PHPExcel.Excel#class");
        \Excel::export($students);
	}   
    
    
    
}