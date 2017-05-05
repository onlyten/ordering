<?php
namespace Common\Model;

use Think\Model\RelationModel;

class RmealsModel extends RelationModel
{
    protected $_link = array(
        'XjSchool' => array(
            'mapping_type' => self::BELONGS_TO,
            'mapping_name' => 'school',
            'foreign_key' => 'school_id',//关联id
        ),
        'XjClass' => array(
            'mapping_type' => self::BELONGS_TO,
            'mapping_name' => 'class',
            'foreign_key' => 'class_id',//关联id
        )
    );

    public function get($condition = array(), $relation = false)
    {
        $data = $this->where($condition);
        if ($relation) {
            $data = $data->relation($relation);
        }
        $data = $data->find();

        return $data;
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

     public function getListCount($condition = array())
    {
        $count = $this->where($condition)->count();
        return $count;
    }

    public function getListSum($condition)
    {
        $sum = $this->where($condition)->sum("totalprice");
        return $sum;
    }
}