<?php
namespace Phone\Model;
use Think\Model\ViewModel;
class DeProModel extends ViewModel {
   public $viewFields = array(
     'order_detail'=>array('id','product_id','user_id','name','leave_status','retreat_status',), 
     'product'=>array('id'=>'product_id','order_date','file_id','menu_id','_on'=>'order_detail.product_id=product.id'),
     'menu'=>array('id'=>'menu_id','name'=>'menu_name','_on'=>'product.menu_id=menu.id'),
     'file'=>array('id'=>'file_id','savename','savepath','_on'=>'product.file_id=file.id'),
    );
}