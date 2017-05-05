<?php
namespace Common\Model;

use Think\Model;

class UserGroupAccessModel extends Model
{
    public function add($user_id, $group_id)
    {
        $data = $this->where(array("uid" => $user_id))->find();
        if ($data) {
            $data["group_id"] = $group_id;
            parent::save($data);
        } else {
            parent::add(array("uid" => $user_id, "group_id" => $group_id));
        }
    }

    public function del($user_id)
    {
        $this->where(array("uid" => $user_id))->delete();
    }

    //删除多条
    public function delmany($condition = array()){
        $this->where($condition)->delete();

    }

    public function getgroup($user_id){
        $data = $this->where(array("uid" => $user_id))->find();
        if($data){
            $userGroupModel = D("UserGroup");
            $group = $userGroupModel->where(array("id"=>$data['group_id']))->find();
            return $group;
        }
    }

    public function getRuls($user_id){
        $data = $this->getgroup($user_id);
        $rules = D("UserRule")->where(array("id" => array('in',$data['rules'])))->select();
        return $rules;

    }
}