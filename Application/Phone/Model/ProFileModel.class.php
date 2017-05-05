<?php
namespace Phone\Model;
use Think\Model\ViewModel;
class ProFileModel extends ViewModel {
   public $viewFields = array(
     'product'=>array('id','file_id','name','menu_id','order_date'), 
     'file'=>array('id'=>'file_id','savename','savepath','_on'=>'product.file_id=file.id'),
   );
 }