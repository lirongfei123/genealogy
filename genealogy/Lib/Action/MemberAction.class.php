<?php
function getParentViewData($parentid, $startIndex, $endIndex, $endid=-1, $endtype=-1){
    $returnValue = array();
    $memberModel = new MemberModel();
    $sqlResult = $memberModel->where("id='$parentid'")->select();
    $returnValue["id"]=$parentid;
    $returnValue["name"]=$sqlResult[0]["name"];
    $returnValue["hometown"]=$sqlResult[0]["hometown"];
    $returnValue["birthday"]=$sqlResult[0]["birthday"];
    //获取配偶
    if($sqlResult[0]["realspouse"] == "0"){
        $returnValue["spouse"]="无数据";
    }else{
        $spouseid = $sqlResult[0]["realspouse"];
        $spouseResult = $memberModel->where("id='$spouseid'")->select();
        $returnValue["spouse"]=$spouseResult[0]['name'];
    }
    $children = $sqlResult[0]['childrens'];
    if(trim($children) == ""){
        $childrenArr = array();
    }else{
        $childrenArr = explode(",", $children);
    }
    if($parentid!=$endid){
        if((count($childrenArr) > 0) && ($startIndex<$endIndex) && $parentid != $endid){
            $childrens = array();
            $startIndex++;
            $hasendchildren = false;
            foreach ($childrenArr as $key => $children) {
                if($children == $endid){
                    $hasendchildren = true;
                }
                array_push($childrens,getParentViewData($children,$startIndex,$endIndex,$endid,$endtype));
            }
            if($hasendchildren){
                foreach ($childrens as $key => $value) {
                    if($value['id'] == $endid) {
                        $temp = $value;
                    }
                    array_splice($childrens,$key,1);
                    break;
                }
                $temp["disableMerge"] = true;
                if($endtype==1){
                    array_unshift($childrens, $temp);
                }else{
                    array_push($childrens, $temp);
                }
            }
            $returnValue["childrens"] = $childrens;
        }
    }

    return $returnValue;
}
class MemberAction extends Action{
    public function getUserInfo(){
        $userModel = new UserModel();
        $token = $_COOKIE["ssoToken"];
        $selectResult = $userModel -> where("userid='$token'")->select();
        $ownid = $selectResult[0]['myselfid'];
        $parentid = $selectResult[0]['parentid'];
        echo json_encode(array(
            "code"=>200,
            "data"=>array(
                "myselfid"=>$ownid,
                "parentid"=>$parentid
            )
        ));
    }
    public function getSaidaiView(){
        $ownid = $_GET['id'];
        $memberModel = new MemberModel();
        // 先获取本身的父母
        $sqlResult = $memberModel->where("id='$ownid'")->select();
        $parentid = $sqlResult[0]["realparent"];
        $motherid = $sqlResult[0]["realmother"];
        if($parentid!="0"){
            $sqlResult = $memberModel->where("id='$parentid'")->select();
            $greatParentId = $sqlResult[0]["realparent"];
            if($greatParentId=="0"){
                $greatParentId = null;
            }
            $parentname = $sqlResult[0]["name"];
        }else{
            //如果没有父亲，那么爷爷就是空的
            $greatParentId = null;
            $parentid = null;
            $parentname = "无数据";
        }
        if($motherid!="0"){
            $sqlResult = $memberModel->where("id='$motherid'")->select();
            $greatMotherId = $sqlResult[0]["realparent"];
            if($greatMotherId=="0"){
                $greatMotherId = null;
            }
            $mothername = $sqlResult[0]["name"];
        }else{
            //如果母亲是空的，那么奶奶也为null
            $greatMotherId = null;
            $motherid = null;
            $mothername = "无数据";
        }
        //如果父亲是空的，那么就获取当前的树结构，然后加上空的父亲
        if($parentid==null){
            $ownTree = array(
                "name"=>$parentname,
                "spouse"=>$mothername,
                "childrens"=>array(getParentViewData($ownid,0,13))
            );
        }else{
            $ownTree = getParentViewData($parentid,0,13);
        }
        if($greatParentId == null) {
            $greatParentTree = array(
                "name"=>"无数据",
                "spouse"=>"无数据",
                "childrens"=>array(
                    array(
                        "name"=>"无数据",
                        "spouse"=>"无数据"
                    ),
                    array(
                        "name"=>$parentname,
                        "spouse"=>$mothername,
                        "disableMerge"=>true
                    )
                )
            );
        }else{
            $greatParentTree = getParentViewData($greatParentId,0,2,$parentid,-1);;
        }
        if($greatMotherId == null) {
            $greatMotherTree = array(
                "name"=>"无数据",
                "spouse"=>"无数据",
                "childrens"=>array(
                    array(
                        "name"=>$parentname,
                        "spouse"=>$mothername,
                        "disableMerge"=>true
                    ),
                    array(
                        "name"=>"无数据",
                        "spouse"=>"无数据"
                    )
                )
            );
        }else{
            $greatMotherTree = getParentViewData($greatMotherId,0,2,$motherid,1);;
        }
        echo json_encode(array(
            "code"=>200,
            "data"=>array(
                "parent"=>$greatParentTree,
                "mother"=>$greatMotherTree,
                "own"=>$ownTree
            )
        ));
    }
    public function getParentView(){
        $parentid = $_GET['id'];
        echo json_encode(array(
            "code"=>200,
            "data"=>getParentViewData($parentid,0,15)
        ));
    }
    public function delMember(){
        $id = $_GET['id'];
        $memberModel = new MemberModel();
        $ownResult = $memberModel->where("id='$id'")->select()[0];
        $parentids = $ownResult['parents'];
        $motherids = $ownResult['mothers'];
        $spouseids = $ownResult['spouses'];
        $childrenids = $ownResult['childrens'];
        if(trim($parentids)==""){
            $parentids =array();
        }else{
            $parentids = explode(",", $parentids);
        }
        if(trim($motherids)==""){
            $motherids =array();
        }else{
            $motherids = explode(",", $motherids);
        }
        if(trim($spouseids)==""){
            $spouseids =array();
        }else{
            $spouseids = explode(",", $spouseids);
        }
        if(trim($childrenids)==""){
            $childrenids =array();
        }else{
            $childrenids = explode(",", $childrenids);
        }
        function delId($baseid,$delid){
            if(trim($baseid)==""){
                $baseid =array();
            }else{
                $baseid = explode(",", $baseid);
            }
            foreach($baseid as $key=>$value) {
                if($value==$delid){
                    array_splice($baseid, $key, 1);
                    break;
                }
            }
            return join(",", $baseid);
        }
        function delRelate($relateids,$field,$delid,$otherField=-1){
            $memberModel = new MemberModel();
            $id = $_GET['id'];
            foreach($relateids as $key=>$member){
                $updateData = array();
                $tempResult = $memberModel->where("id='$member'")->select()[0];
                $tempField = $tempResult[$field];
                $newField = delId($tempField,$delid);
                $updateData[$field] = $newField;
                //如果otherid不等于-1 ，也要删除
                if($otherField!=-1){
                    $tempField = $tempResult[$otherField];
                    if($tempField ==$delid){
                        $newField = "";
                        $updateData[$otherField] = $newField;
                    }
                }
                $memberModel->where("id='$member'")->update($updateData);
            }
        }
        //修改其父亲里面的孩子列表
        delRelate($parentids,"childrens",$id);
        //修改其母亲里面的孩子列表
        delRelate($motherids,"childrens",$id);
        //修改其配偶里面的配偶列表
        delRelate($spouseids,"spouses",$id,"realspouse");
        //修改其孩子里面的父亲列表
        delRelate($childrenids,"parents",$id,"realparent");
        //修改其孩子里面的母亲列表
        delRelate($childrenids,"mothers",$id,"realmother");
        //删除本身
        $memberModel->where("id='$id'")->delete();
        echo json_encode(array(
            "code"=>200,
            "data"=>"ok"
        ));
    }
    public function getById(){
        $memberModel = new MemberModel();
        $ids = explode(",", urldecode($_GET['id']));
        $where = array();
        foreach ($ids as $key => $value) {
            array_push($where,"id='$value'");
        }
        $whereStr = join(" or ", $where);
        $sqlResult = $memberModel->where($whereStr)->select();
        echo json_encode(array(
            "code"=>200,
            "data"=>$sqlResult
        ));
    }
    public function getUserSetting(){
        $token = $_COOKIE["ssoToken"];
        $userModel = new UserModel();
        $selectResult = $userModel -> where("userid='$token'")->select();
        echo json_encode(array(
            "code"=>200,
            "data"=>array(
                "parentid"=>$selectResult[0]["parentid"],
                "myselfid"=>$selectResult[0]["myselfid"],
                "parentname"=>$selectResult[0]["parentname"],
                "myselfname"=>$selectResult[0]["myselfname"]
            )
        ));
    }
    public function userSetting(){
        $token = $_COOKIE["ssoToken"];
        $userModel = new UserModel();
        $selectResult = $userModel -> where("userid='$token'")->update(array(
            "parentid"=>$_POST['parentid'],
            "myselfid"=>$_POST['myselfid'],
            "parentname"=>$_POST['parentname'],
            "myselfname"=>$_POST['myselfname']
        ));
        echo json_encode(array(
            "code"=>200,
            "data"=>"success"
        ));
    }
}
?>