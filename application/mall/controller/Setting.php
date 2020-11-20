<?php

namespace app\mall\controller;

use app\common\controller\Adminbase;
use app\mall\model\setting\BannerModel;
use app\mall\model\setting\SettingModel;
use app\mall\model\setting\IconModel;
use think\Db;


/**
 * 商城配置
 */
class Setting extends Adminbase
{
    /***
     * 商城轮播
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function img(){
        if ($this->request->isAjax()) {
            $ItemCategory = new BannerModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $where = [];
            $where[] = ['status','eq',1];
            $list = $ItemCategory->where($where)->page($page,$limit)->order('sort asc')->select()->append(['img']);
            $total = $ItemCategory->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    /***
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function img_add(){
        $data = $this ->request ->param();
        if( !empty($data['id']) ){
            $list = Db::name('banner') ->where('id',$data['id'])->find();
            $this ->assign('list',$list);
        }
        return $this->fetch();
    }

    /***
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function img_doPost(){
        $data = $this->request->post();
        $Banner = new BannerModel();
        if( empty($data['images']) ){
            return json(['code'=>2,'msg'=>"请上传banner图"]);
        }
        if( $data['type'] != 1 && empty($data['url']) ){
            return json(['code'=>2,'msg'=>"请输入跳转地址"]);
        }
        $data['thumb'] = $data['images'];
        unset($data['file']);
        unset($data['images']);
        if( empty($data['id']) ){
            $data['user_id'] = session('admin_user_auth')['uid'];
            $result = $Banner->insert($data);
        }else{
            $result = $Banner ->where('id',$data['id'])->update($data);
        }
        if( $result ){
            return json(['code'=>1,'msg'=>"操作成功"]);
        }else{
            return json(['code'=>2,'msg'=>"服务器出错"]);
        }
    }

    /***
     * 禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function img_del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new BannerModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>2]);
        if( $result ){
            $this ->success('禁用成功');
        }else{
            $this ->error('禁用失败');
        }
    }

    /***
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function img_start(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new BannerModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>1]);
        if( $result ){
            $this ->success('启用成功');
        }else{
            $this ->error('启用失败');
        }
    }

    /***
     * 服务协议
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function service_agreement(){
        $Setting = new SettingModel();
        if( $this ->request ->isAjax() ){
            $data = $this ->request->post();
            unset($data['file']);
            if( empty($data['id']) ){
                $info = $Setting ->where('type',$data['type'])->find();
                if( $info ){
                    return json(['code'=>2,'msg'=>"当前已有服务协议，请勿重复添加"]);
                }
                $result = $Setting ->insert($data);
            }else{
                $result = $Setting ->where('id',$data['id'])->update($data);
            }
            if( $result ){
                return json(['code'=>1,'msg'=>"操作成功"]);
            }else{
                return json(['code'=>2,'msg'=>"操作失败"]);
            }
        }else{
            $list = $Setting ->getContent('1');
            $this ->assign('list',$list);
            return $this ->fetch();
        }
    }

    /***
     * 隐私保护政策
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function privacy(){
        $Setting = new SettingModel();
        if( $this ->request ->isAjax() ){
            $data = $this ->request->post();
            unset($data['file']);
            if( empty($data['id']) ){
                $result = $Setting ->insert($data);
            }else{
                $result = $Setting ->where('id',$data['id'])->update($data);
            }
            if( $result ){
                return json(['code'=>1,'msg'=>"操作成功"]);
            }else{
                return json(['code'=>2,'msg'=>"操作失败"]);
            }
        }else{
            $list = $Setting ->getContent('2');
            $this ->assign('list',$list);
            return $this ->fetch();
        }
    }

    /***
     * 热门标签
     */
    public function hot(){
        $list = Db::name('hot')->where(['type'=>1,'status'=>1])->column('title');
        $content = '';
        foreach ($list as $v){
            $content = $content."\n".$v;
        }
        $this ->assign('content',$content);
        return $this ->fetch();
    }

    public function hot_doPost(){
        $data = $this ->request ->post();
        $list = explode("\n",$data['content']);
        $tt = [];
        foreach ($list as $k=>$v){
            $v = preg_replace("/ /","",$v);
            $v = preg_replace("/&nbsp;/","",$v);
            $v = preg_replace("/　/","",$v);
            $v = preg_replace("/\r\n/","",$v);
            $v = str_replace(chr(13),"",$v);
            $v = str_replace(chr(10),"",$v);
            $v = str_replace(chr(9),"",$v);
            if( $v !='' ){
                array_push($tt,$v);
            }
        }
        $info = [];     //二级
        foreach ( $tt as $k=>$v ){
            if( !in_array($v,$info) &&  $v !='' ){
                array_push($info,$v);
            }
        }
        if( count($info)>15 ){
            return json(['code'=>100,'msg'=>'最多只能添加15个']);
        }
        $old = Db::name('hot')->where(['type'=>1,'status'=>1])->select();
        $saveArr = [];  //修改的数据
        $newArr = [];   //新增或减少的数据
        if( count($old) == count($info) ){
            foreach ($old as $key=>$val){
                foreach ($info as $k=>$v) {
                    if( $key == $k ){
                        $arr = $val;
                        $arr['title'] = $v;
                        array_push($saveArr,$arr);
                    }
                }
            }
        }
        if( count($old) > count($info) ){
            //减少了shanchu
            foreach ($old as $key=>$val){
                foreach ($info as $k=>$v) {
                    if( $key == $k ){
                        $arr = $val;
                        $arr['title'] = $v;
                        array_push($saveArr,$arr);
                    }
                }
            }
            foreach ($old as $k=>$v){
                if( $k >=count($info) ){
                    array_push($newArr,$v);
                }
            }
        }
        if( count($old) < count($info) ){
            foreach ($old as $key=>$val){
                foreach ($info as $k=>$v) {
                    if( $key == $k ){
                        $arr = $val;
                        $arr['title'] = $v;
                        array_push($saveArr,$arr);
                    }
                }
            }
            foreach ($info as $k=>$v){
                if( $k >=count($old) ){
                    $arr = [];
                    $arr['title'] = $v;
                    array_push($newArr,$arr);
                }
            }
        }
        foreach ($saveArr as $k=>$v){
            Db::name('hot')->where('id',$v['id'])->update($v);
        }
        if( count($old) > count($info) ){
            $ids = [];
            foreach ($newArr as $k=>$v){
                $ids[] = $v['id'];
            }
            $ids = implode(',',$ids);
            $map[] = ['id','in',$ids];
            Db::name('hot')->where('id',$v['id'])->delete();
        }
        if( count($old) < count($info) ){
            Db::name('hot') ->insertAll($newArr);
        }
        return json(['code'=>1,'msg'=>'修改成功']);
    }

    /***
     * 图标管理
     */
    public function icon(){
        if ($this->request->isAjax()) {
            $ItemCategory = new IconModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $where = [];
            $list = $ItemCategory->where($where)->page($page,$limit)->order('sort asc')->select()->append(['img']);
            $total = $ItemCategory->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    /***
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function icon_add(){
        $data = $this ->request ->param();
        if( !empty($data['id']) ){
            $list = Db::name('icon') ->where('id',$data['id'])->find();
            $this ->assign('list',$list);
        }
        return $this->fetch();
    }

    /***
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function icon_doPost(){
        $data = $this->request->post();
        $Banner = new IconModel();

        if( empty($data['images']) ){
            return json(['code'=>2,'msg'=>"请上传图标"]);
        }

        if ( $data['type'] == 2 && empty($data['url']) )
        {
            return json(['code'=>2,'msg'=>"请输入页面地址"]);
        }
        if ( $data['type'] == 3 )
        {
            if ( empty($data['url']) )
            {
                return json(['code'=>2,'msg'=>"请输入页面地址"]);
            }

            if ( empty($data['value']) )
            {
                return json(['code'=>2,'msg'=>"请输入json参数"]);
            }
        }

        $data['thumb'] = $data['images'];
        unset($data['file']);
        unset($data['images']);

        if( empty($data['id']) ){
            $result = $Banner->insert($data);
        }else{
            $result = $Banner ->where('id',$data['id'])->update($data);
        }
        if( $result ){
            return json(['code'=>1,'msg'=>"操作成功"]);
        }else{
            return json(['code'=>2,'msg'=>"服务器出错"]);
        }
    }

    /***
     * 禁用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function icon_del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new IconModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>2]);
        if( $result ){
            $this ->success('禁用成功');
        }else{
            $this ->error('禁用失败');
        }
    }

    /***
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function icon_start(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new IconModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>1]);
        if( $result ){
            $this ->success('启用成功');
        }else{
            $this ->error('启用失败');
        }
    }

    /***
     * 分销
     */
    public function distribution(){
        $Setting = new SettingModel();
        if( $this ->request ->isAjax() ){
            $data = $this ->request->post();
            if( empty($data['id']) ){
                $info = $Setting ->where('type',$data['type'])->find();
                if( $info ){
                    return json(['code'=>2,'msg'=>"当前已有提成比例，请勿重复添加"]);
                }
                $result = $Setting ->insert($data);
            }else{
                $result = $Setting ->where('id',$data['id'])->update($data);
            }
            if( $result ){
                return json(['code'=>1,'msg'=>"操作成功"]);
            }else{
                return json(['code'=>2,'msg'=>"操作失败"]);
            }
        }else{
            $list = $Setting ->getContent('3');
            $this ->assign('list',$list);
            return $this ->fetch();
        }
    }

}
