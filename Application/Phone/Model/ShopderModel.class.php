<?php
namespace Phone\Model;
use Think\Model\ViewModel;
class ShopderModel extends ViewModel {
   public $viewFields = array(
     'order'=>array('id','totalprice','user_id','time','pay_status',),
	  'shop'=>array('id'=>'shop_id', 'name','_on'=>'order.shop_id=shop.id'),
		// 'file'=>array('id'=>'file_id', 'savename','savepath','_on'=>'shop.file_id=file.id'),
   );
 }
