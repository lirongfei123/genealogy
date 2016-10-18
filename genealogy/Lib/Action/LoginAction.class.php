<?php
class LoginAction extends Action{
	public function register(){
		$token = $_COOKIE["ssoToken"];
        $userModel = new UserModel();
        $selectResult = $userModel -> where("userid='$token'")->select();
        if(count($selectResult)==0){//如果没有就创建
            $saveResult = $userModel->save(array(
                "userid" => $token,
                "username" => $_POST["username"],
                "email" => $_POST["email"]
            ));
        }else{//如果有就更新
            $saveResult = $userModel-> where("userid='$token'")->update(array(
                "username" => $_POST["username"],
                "email" => $_POST["email"]
            ));
        }
        echo json_encode(array(
            "status"=>200,
            "info"=>"ok"
        ));
	}
}
?>