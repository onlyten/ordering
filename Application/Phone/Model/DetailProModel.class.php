<?php
namespace Phone\Model;
use Think\Model\ViewModel;
class DetailProModel extends ViewModel {
   public $viewFields = array(
     'order_detail'=>array('id','order_id','product_id','user_id','class_id','name','num','price','leave_status','retreat_status','remark','name','apply_time'), 
     'product'=>array('id'=>'product_id','shop_id','order_date','_on'=>'order_detail.product_id=product.id'),
     'shop'=>array('id'=>'shop_id','deadline','is_examine','is_day','_on'=>'product.shop_id=shop.id'),
   );
 }