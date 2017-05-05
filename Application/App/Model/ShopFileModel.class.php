<?php
namespace App\Model;
use Think\Model\ViewModel;
class ShopFileModel extends ViewModel {
   public $viewFields = array(
     'shop'=>array('id','file_id','name',), 
     'file'=>array('id'=>'file_id','savename','savepath','_on'=>'shop.file_id=file.id'),
   );
 }