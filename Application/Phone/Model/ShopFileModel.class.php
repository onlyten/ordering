<?php
namespace Phone\Model;
use Think\Model\ViewModel;
class ShopFileModel extends ViewModel {
   public $viewFields = array(
     'shop'=>array('id','file_id','name','tel','address','notification','status'), 
     'file'=>array('id'=>'file_id','savename','savepath','_on'=>'shop.file_id=file.id'),
   );
 }