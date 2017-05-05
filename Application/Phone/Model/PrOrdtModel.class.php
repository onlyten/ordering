<?php
namespace Phone\Model;
use Think\Model\ViewModel;
class PrOrdtModel extends ViewModel {
   public $viewFields = array(
     'order_detail'=>array('id','order_id','product_id','price','name'), 
     'product'=>array('id'=>'product_id','order_date','_on'=>'order_detail.product_id=product.id'),
   );
 }