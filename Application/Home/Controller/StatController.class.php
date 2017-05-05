<?php
namespace Home\Controller;

class StatController extends BaseController{
	public function detail(){
		//商家id
		//班级
		$class = D('XjClass')->field('id,bjmc,bjjc')->where('id="'.I('get.classId').'"')->find();
		$table = '<table class="table table-bordered table-hover"><tbody>';
		if(!empty(I('get.day'))){
			//按天查询
			$table.= "<tr><th>班级</th><th colspan='2'>".$class['bjmc']."</th></tr>";
			$table.="<tr><th>姓名</th><th colspan='2'>".I('get.month')."月".I('get.day')."日</th></tr>";
			//学生
			$students = D('XjStudent')->where('bj="'.I('get.classId').'"')->field('id,xsxm,bj,dyzh')->select();			
			foreach ($students as $sk => $sv) {
				$condition = array();
				array_push($condition, array(
					'orderDate'=>I('get.year').'-'.I('get.month').'-'.I('get.day')
				));
				array_push($condition,array(
					'shopId' => session('homeShopId')
					//'shopId' => '201' //假数据
				));
				array_push($condition,array(
					'classId' => I('get.classId')
				));
				array_push($condition,array(
					'userId' => $sv['dyzh']
				));
				$count = D('Count')->where($condition)->field('shopid,orderid,menuname,menuid,leavestatus,retreatstatus')->find();
				if(empty($count)){
					$students[$sk]['menuname'] = null;
					$students[$sk]['menuid'] = null;
				}
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
			$menus = D('Menu')->where('shop_id='.session('homeShopId'))->field('id,name,shop_id')->order('time asc')->select();
			//$menus = D('Menu')->where('shop_id=201')->field('id,name,shop_id')->order('time asc')->select();
			
			foreach ($students as $key => $value) {
				foreach ($menus as $k => $v) {
					if($value['menuid'] == $v['id']){
						$menus[$k]['num']++;
					}
				}
				$table.="<tr><td>".$value['xsxm']."</td><td>".$value['menuname']."</td><td></td></tr>";
			}
			$table.="</tbody></table>";

			$tabletwo = '<table class="table table-bordered table-hover"><tbody>';
			$tabletwo.= '<tr><th rolspan="2">合计</th></tr>';
			$zj = 0;
			foreach($menus as $mk => $mv){
				$tabletwo.="<tr><td>".$mv['name']."</td><td>".$mv['num']."</td></tr>";
				$zj = $zj + $mv['num'];
			}
			$tabletwo.= '<tr><td>总计</td><td>'.$zj.'</td></tr>';
			$tabletwo.= '</tbody></table>';
			$this->assign('table',$table);
			$this->assign('tabletwo',$tabletwo);

		}elseif(!empty(I('get.month')) && !empty(I('get.year'))){
			//按月查询
		}
		$this->display('Stastics_detail');
	}

	public function export(){
		$class = D('XjClass')->field('id,bjmc,bjjc')->where('id="'.I('get.classId').'"')->find();
		$firstarr = array('title'=>'班级','cont'=>$class['bjmc']);
		$secondarr = array('title'=>'姓名','cont'=>I('get.month')."月".I('get.day')."日");
		//学生
		$students = D('XjStudent')->where('bj="'.I('get.classId').'"')->field('xsxm,dyzh')->select();			
		foreach ($students as $sk => $sv) {
			$condition = array();
			array_push($condition, array(
				'orderDate'=>I('get.year').'-'.I('get.month').'-'.I('get.day')
			));
			array_push($condition,array(
				'shopId' => session('homeShopId')
				//'shopId' => '201' //假数据
			));
			array_push($condition,array(
				'classId' => I('get.classId')
			));
			array_push($condition,array(
				'userId' => $sv['dyzh']
			));
			$count = D('Count')->where($condition)->field('shopid,orderid,menuname,menuid,leavestatus,retreatstatus')->find();
			if(empty($count)){
				$students[$sk]['menuname'] = null;
				$students[$sk]['menuid'] = null;
			}
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
		$menus = D('Menu')->where('shop_id='.session('homeShopId'))->field('id,name')->order('time asc')->select();
		//$menus = D('Menu')->where('shop_id=201')->field('id,name')->order('time asc')->select();
		foreach ($students as $key => $value) {
			foreach ($menus as $k => $v) {
				if($value['menuid'] == $v['id']){
					$menus[$k]['num']++;
				}
			}
		}
		array_push($students,array('title'=>'合计','cont'=>''));
		$zj = 0;
		foreach($menus as $mk => $mv){
			$zj = $zj + $mv['num'];
			unset($menus[$mk]['id']);
			array_push($students,$mv);
		}

		array_unshift($students,$firstarr,$secondarr);
		array_push($students,array('title'=>'总计','cont'=>$zj));
		foreach ($students as $kk => $vv) {
			unset($students[$kk]['dyzh']);
			unset($students[$kk]['menuid']);
			unset($students[$kk]['id']);
		}
		Vendor("PHPExcel.Excel#class");
        \Excel::export($students);
	}
}
