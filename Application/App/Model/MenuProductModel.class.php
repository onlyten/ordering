<?php
namespace App\Model;
use Think\Model\ViewModel;
class MenuProductModel extends ViewModel {
   public $viewFields = array(
   	 
     'menu'=>array('id'=>'menu_id','shop_id','name'=>'menu_name'), 
     'product'=>array('id','menu_id','name','price','_on'=>'product.menu_id=menu.id'),

   );
 }



 // 'product'=>array('id','menu_id','name','price'),
 //     'menu'=>array('id'=>'menu_id','shop_id','name'=>'menu_name','_on'=>'product.menu_id=menu.id'), 