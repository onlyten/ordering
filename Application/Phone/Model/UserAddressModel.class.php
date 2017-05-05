<?php
namespace Phone\Model;
use Think\Model\ViewModel;
class UserAddressModel extends ViewModel {
   public $viewFields = array(
     'sys_user'=>array('id','login_name'), 
     'xj_school'=>array('id'=>'school_id','xxdz','xxmc','_on'=>'sys_user.company_id=xj_school.id'),
     'xj_student'=>array('id'=>'student_id','xsxm','_on'=>'sys_user.id=xj_student.dyzh'),
     'xj_class'=>array('id'=>'class_id','bjmc','_on'=>'xj_student.bj=xj_class.id'),
   );
 }