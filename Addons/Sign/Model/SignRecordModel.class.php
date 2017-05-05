<?php
namespace Addons\Sign\Model;

use Think\Model\RelationModel;

class SignRecordModel extends RelationModel
{
    protected $_link = array(
        'User' => array(
            'mapping_type' => self::BELONGS_TO,
            'mapping_name' => 'user',
            'foreign_key' => 'user_id',//关联id
            'as_fields' => 'username:username',
        ),
    );
}