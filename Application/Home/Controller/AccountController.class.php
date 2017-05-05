<?php
namespace Home\Controller;

class AccountController extends BaseController{
	public function userGroup(){
        $userGroupList = D("UserGroup")->getList();
        $this->assign('userGroupList',$userGroupList);
        $this->display();
	}

	public function addUserGroup(){
		if (IS_POST) {
            if (!I("post.rules")) {
                $this->error("权限不能为空", "Home/Account/addUserGroup");
            }

            D("UserGroup")->add(I("post."));
            $this->success("添加成功", "Home/Account/userGroup");
        } else {
            $userRuleList = D("UserRule")->getList();
            $this->assign("userRuleList", $userRuleList);
            $this->display();
        }
	}

	public function modUserGroup(){
		$userGroup = D("UserGroup")->get(I("get.id"));
        $this->assign("userGroup", $userGroup);

        $userRuleList = D("UserRule")->getList();
        $this->assign("userRuleList", $userRuleList);

        $this->display("Account:addUserGroup");
	}

	public function delUserGroup(){
		D("UserGroup")->del(array("id" => array("in", I("get.id"))));
        $this->success("删除成功", "Home/Account/userGroup");
	}

	public function admin(){
        $where=array();
        $where['type']="3";
        $where['pid']=session('homeId');
        $user = D('User')->where($where)->select();
        foreach ($user as $k => $val) {
            $group = D('UserGroupAccess')->getgroup($val['id']);
            $user[$k]['group_title']=$group['title'];
        }
        $this->assign("adminList",$user);
        $this->display();
    }

    public function addAdmin(){
    	if (IS_POST) {
            $data = I("post.");
            if (!$data["group_id"]) {
                $this->error("操作失败", "Home/Account/addAdmin");
            }

            if ($data["password"] != "") {
                $data["password"] = md5($data["password"]);
            } else {
                unset($data["password"]);
            }

            $groupId = $data["group_id"];
            unset($data["group_id"]);

            $data['contact_id']="0";
            $data['pid']=session('homeId'); //父id:商家主账号id(type=2)
            $data['type']="3";  //类型：商家子账号类型
            $data['ctime']=date("Y-m-d H:i:s",time());  //创建时间
            $data['time']=date("Y-m-d H:i:s",time());  //修改时间

            $userId = D("User")->add($data);


            if ($groupId > 0) {
                D("UserGroupAccess")->add($userId, $groupId);
            }

            $this->success("添加成功", "Home/Account/admin");
        } else {
            $userGroupList = D("UserGroup")->getList(array(), false, "id asc");
			unset($userGroupList[0]);  //删除超级管理员一项
            $this->assign("userGroupList", $userGroupList);
            $this->display();
        }
    }   

    public function modAdmin(){
        $user = D("User")->get(array("id" => I("get.id")), true);
        $this->assign("adminer", $user);

        $userGroupList = D("UserGroup")->getList(array(), false, "id asc");
        unset($userGroupList[0]);  //删除超级管理员一项
        $this->assign("userGroupList", $userGroupList);

        $this->display("Account:addAdmin");
    }

    public function delAdmin(){
        D("User")->del(array("id" => array("in", I("get.id"))));
        D("UserGroupAccess")->delmany(array("uid" => array("in", I("get.id"))));
        $this->success("删除成功", "Home/Account/admin");
    }

}