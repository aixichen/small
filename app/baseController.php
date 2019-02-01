<?php
namespace App;

class BaseController {

    private function ajaxReturn($data='',$message = '',$status=0)
    {
        $result=[];
        $result['status']=$status;
        $result['data']=$data;
        $result['message']=$message;
        header('Content-Type:application/json; charset=utf-8');
        echo json_encode($result);
    }
    protected function success($data,$message='请求成功',$status=200)
    {
        $this->ajaxReturn($data,$message,$status);
    }

    protected function error($message='请求失败',$status=500)
    {
        $this->ajaxReturn($data,$message,$status);
    }

}