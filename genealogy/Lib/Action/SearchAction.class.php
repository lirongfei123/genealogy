<?php
class SearchAction extends Action{
    public function search(){
        $memberModel = new MemberModel();
        if($_GET["name"] == "*") {
            $result = $memberModel->select();
        }else{
            $name = $_GET['name'];
            $result = $memberModel->where("name like '%$name%'")->select();
        }
        echo json_encode(array(
            "code"=>200,
            "data"=>$result
        ));
    }
    public function searchByName(){
        $name =$_GET['name'];
        $memberModel = new MemberModel();
        $exclude = "";
        if(isset($_GET["exclude"])){
            $exclude = " and id!='".$_GET["exclude"]."'";
        }
        $sqlResult = $memberModel->where("name like '%$name%'".$exclude)->select();
        $result = array();
        foreach ($sqlResult as $key => $value) {
            array_push($result,array(
                "id"=>$value['id'],
                "text"=>$value['name'],
                "secondname"=>$value['secondname']
            ));
        }
        echo json_encode($result);
    }
}
?>