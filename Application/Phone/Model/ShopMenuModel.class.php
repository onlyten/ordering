<?php
namespace Phone\Model;
use Think\Model\ViewModel;
class ShopMenuModel extends ViewModel {
   public $viewFields = array(
     'menu'=>array('id','shop_id','name'), 
     'shop'=>array('id'=>'shop_id','name'=>'shop_name','_on'=>'menu.shop_id=shop.id'),
   );
 }