<?php
namespace Common\Model;

use Think\Model\RelationModel;

class OrderDetailModel extends RelationModel
{
    protected $_link = array(
        'Product' => array(
            'mapping_type' => self::BELONGS_TO,
            'mapping_name' => 'product',
            'foreign_key' => 'product_id',//关联id
        ),
        'Order' => array(
            'mapping_type' => self::BELONGS_TO,
            'mapping_name' => 'order',
            'foreign_key' => 'order_id',
        ),
        'File' => array(
            'mapping_type' => self::BELONGS_TO,
            'mapping_name' => 'file',
            'foreign_key' => 'file_id',//关联id
            'as_fields' => 'savename:savename,savepath:savepath',
        ),
        'User' => array(
            'mapping_type' => self::BELONGS_TO,
            'mapping_name' => 'user',
            'foreign_key' => 'user_id',//关联id
            'as_fields' => 'username:username',
        ),
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

    public function getMethod($condition = array(), $method, $args)
    {
        $field = isset($args) ? $args : '*';
        $data = $this->where($condition)->getField(strtoupper($method) . '(' . $field . ') AS tp_' . $method);
        return $data;
    }

    public function add($data)
    {
        if ($data["id"] == 0 || !isset($data["id"])) {
            $id = parent::add($data);
            return $id;
        } else {
            $this->save($data);
            return $data["id"];
        }
    }

    public function addAll($data)
    {
        parent::addAll($data);
    }

    public function save($data)
    {
        parent::save($data);
    }

    public function del($condition = array())
    {
        $this->where($condition)->delete();
    }
}