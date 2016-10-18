<?php
function applyIdToSpouse($spouses,$id){
    if(trim($spouses)=="") return;
    $spouseArr = explode(",", $spouses);
    $memberModel = new MemberModel();
    foreach ($spouseArr as $key => $value) {
        $sqlResult = $memberModel->where("id='$value'")->select();
        $tempSpouses = explode(",",$sqlResult[0]['spouses']);
        array_push($tempSpouses,$id);
        $tempSpousesStr = join(",",array_unique($tempSpouses));
        $memberModel->where("id='$value'")->update(array(
            "spouses"=>$tempSpousesStr
        ));
    }
}
//otherid是数组
//添加新的关系到特定id,就是把ownid更新到otherid
function addRelation($otherid, $ownid,$relation){
    //初始化成员模型
    $memberModel = new MemberModel();
    foreach ($otherid as $key => $value) {
        // 查询成员之前的关系
        $sqlResult = $memberModel->where("id='$value'")->select();
        if(trim($sqlResult[0][$relation])==""){
            $tempRelation = array();
        }else{
            $tempRelation = explode(",",$sqlResult[0][$relation]);
        }
        // 添加到临时变量当中
        array_push($tempRelation,$ownid);
        // 对变量进行去重去重
        $tempRelationStr = join(",",array_unique($tempRelation));
        // 重新写进数据库
        $memberModel->where("id='$value'")->update(array(
            $relation=>$tempRelationStr
        ));
    }
}
//删除特定id的关系,就是otherid的ownid关系删掉
function removeRelation($otherid, $ownid,$relation){
    $memberModel = new MemberModel();
    foreach ($otherid as $key => $value) {
        $sqlResult = $memberModel->where("id='$value'")->select();
        if(trim($sqlResult[0][$relation])==""){
            $tempRelation = array();
        }else{
            $tempRelation = explode(",",$sqlResult[0][$relation]);
        }
        foreach ($tempRelation as $key => $tempvalue) {
            if($tempvalue==$ownid){
                array_splice($tempRelation,$key,1);
                break;
            }
        }
        $tempRelationStr = join(",",array_unique($tempRelation));
        $memberModel->where("id='$value'")->update(array(
            $relation=>$tempRelationStr
        ));
    }
}
function createId(){
    return md5(time().mt_rand(1,1000000));
}
//分析两个id，把新加的元素，和删除的元素提取出来
function diffIds($oldids,$newids,$ownid,$type){
    if(trim($oldids)==""){
        $oldidArr = array();
    }else{
        $oldidArr = explode(",",$oldids);
    }
    if(trim($newids)==""){
        $newidArr = array();
    }else{
        $newidArr = explode(",",$newids);
    }
    $newRelation = array();
    $delRelation = array();
    //新的关系如果没有在就得关系中，那就是添加
    foreach ($newidArr as $key => $value) {
        if(!in_array($value,$oldidArr)){
            array_push($newRelation,$value);
        }
    }
    //旧的关系，如果没有在新的关系中，那说明删除了
    foreach ($oldidArr as $key => $value) {
        if(!in_array($value,$newidArr)){
            array_push($delRelation,$value);
        }
    }
    addRelation($newRelation,$ownid,$type);
    removeRelation($delRelation,$ownid,$type);
}
class AddAction extends Action{
    public function add(){
        $memberModel = new MemberModel();
        $id = $_POST['id'];
        $result = $memberModel->save(array(
            "id" =>$_POST['id'],
            "name" => $_POST['name'],
            "parents" => $_POST['parents'],
            "mothers" => $_POST['mothers'],
            "spouses" => $_POST['spouses'],
            "realparent" => $_POST['realparent'],
            "realmother" => $_POST['realmother'],
            "realspouse" => $_POST['realspouse'],
            "secondname" => $_POST['secondname'],
            "birthday" => $_POST['birthday'],
            "hometown" => $_POST['hometown'],
            "currenthome" => $_POST['currenthome'],
            "idcard" => $_POST['idcard']
        ));
        diffIds("",$_POST['parents'],$id,"childrens");
        diffIds("",$_POST['mothers'],$id,"childrens");
        diffIds("",$_POST['spouses'],$id,"spouses");
        if(count($result)==1){
            echo json_encode(array(
                "code" => 200,
                "data" => "success"
            ));
        } else {
            echo json_encode(array(
                "code" => 200,
                "data" => "fail"
            ));
        }
    }
    public function update(){
        $memberModel = new MemberModel();
        $id = $_POST['id'];
        //先获取旧的数据
        $memberOld = $memberModel->where("id='$id'")->select();
        $result = $memberModel->where("id='$id'")->update(array(
            "name" => $_POST['name'],
            "parents" => $_POST['parents'],
            "mothers" => $_POST['mothers'],
            "spouses" => $_POST['spouses'],
            "realparent" => $_POST['realparent'],
            "realmother" => $_POST['realmother'],
            "realspouse" => $_POST['realspouse'],
            "secondname" => $_POST['secondname'],
            "birthday" => $_POST['birthday'],
            "hometown" => $_POST['hometown'],
            "currenthome" => $_POST['currenthome'],
            "idcard" => $_POST['idcard']
        ));
        //applyIdToSpouse($_POST['spouses'],$id);
        diffIds($memberOld[0]['parents'],$_POST['parents'],$id,"childrens");
        diffIds($memberOld[0]['mothers'],$_POST['mothers'],$id,"childrens");
        diffIds($memberOld[0]['spouses'],$_POST['spouses'],$id,"spouses");
        if(count($result)==1){
            echo json_encode(array(
                "code" => 200,
                "data" => "success"
            ));
        } else {
            echo json_encode(array(
                "code" => 200,
                "data" => "fail"
            ));
        }
    }
    public function addChild(){
        $parentid = $_POST["parentid"];
        $childid = $_POST["childid"];
        $memberModel = new MemberModel();
        $sqlResult = $memberModel->where("id='$parentid'")->select();
        if(trim($sqlResult[0]['childrens'])==""){
            $tempChildren = array();
        }else{
            $tempChildren = explode(",",$sqlResult[0]['childrens']);
        }
        array_push($tempChildren,$childid);
        $tempChildrenStr = join(",",array_unique($tempChildren));
        $memberModel->where("id='$parentid'")->update(array(
            "childrens"=>$tempChildrenStr
        ));
        echo json_encode(array(
            "code"=>200,
            "data"=>"success"
        ));
    }
    public function removeChild(){
        $parentid = $_POST["parentid"];
        $childid = $_POST["childid"];
        $memberModel = new MemberModel();
        $sqlResult = $memberModel->where("id='$parentid'")->select();
        if(trim($sqlResult[0]['childrens'])!=""){
            $tempChildren = explode(",",$sqlResult[0]['childrens']);
            foreach ($tempChildren as $key => $value) {
                if($value==$childid){
                    array_splice($tempChildren,$key,1);
                    break;
                }
            }
            $tempChildrenStr = join(",",array_unique($tempChildren));
            $memberModel->where("id='$parentid'")->update(array(
                "childrens"=>$tempChildrenStr
            ));
        }

        echo json_encode(array(
            "code"=>200,
            "data"=>"success"
        ));
    }
    public function addSpouse(){

    }
    public function removeSpouse(){

    }
}
?>