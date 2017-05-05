<?php
namespace Common\Model;

use Think\Model;

class AuthGroupModel extends Model
{
    public function get($id)
    {
        $data = $this->where(array("id" => $id))->find();
        $data["rules"] = "," . $data["rules"] . ",";
        return $data;
    }
    public function getAuthGroup($id)
    {
        $group = $this->where(array("id" => $id))->find();
        $group["rules"] = "," . $group["rules"] . ",";
        return $group;
    }
    //获取权限详情
    public function getAuthRule($id){
        $group = $this->getAuthGroup($id);
        $rule = ltrim($group['rules'],',');  //去掉左侧逗号
        $rule = rtrim($rule,',');  //去掉右侧逗号
        $rule = explode(',',$rule);  //分割成权限id的数组
        foreach($rule as $rk=>$rv){
            $authrule[] = D('AuthRule')->field('id,name,title,type')->where('id='.$rv)->find();   
        }
        return $authrule;
    }
    public function getList($condition = array(), $relation = false, $order = "id desc", $p = 0, $num = 0, $limit = 0)
    {
        $data = $this->where($condition);
        if ($relation) {
            $data = $data->relation($relation);
        }
        if ($p && $num) {
            $data = $data->page($p . ',' . $num . '');
        }
        if ($limit) {
            $data = $data->limit($limit);
        }

        $data = $data->order($order)->select();

        return $data;
    }
    public function getAuthGroupList()
    {
        $list = $this->select();
        return $list;
    }
    public function getMethod($condition = array(), $method, $args)
    {
        $field = isset($args) ? $args : '*';
        $data = $this->where($condition)->getField(strtoupper($method) . '(' . $field . ') AS tp_' . $method);
        return $data;
    }

    public function add($data)
    {
        $group = array();
        if ($data["id"] == 0 || !isset($data["id"])) {
            $group["title"] = $data["title"];
            $group["rules"] = implode(",", $data["rules"]);
            parent::add($group);
        } else {
            $group["title"] = $data["title"];
            $group["id"] = $data["id"];
            $group["rules"] = implode(",", $data["rules"]);
            parent::save($group);
        }

    }

    public function del($condition = array())
    {
        $this->where($condition)->delete();
    }

}