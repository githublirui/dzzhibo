<?php

/**
 * 运行 controller
 * @author lirui <649037629@qq.com> 
 */
function runController() {
    $controller = $_GET['pmod'];
    $action = $_GET['act'] ? $_GET['act'] : 'index';

    $path = DISCUZ_ROOT . "./source/plugin/wxz_live/controller/Controller_base.class.php";
    include_once $path;

    $path = DISCUZ_ROOT . "./source/plugin/wxz_live/controller/Controller_{$controller}.class.php";

    if (!file_exists($path)) {
        cpheader();
        cpmsg('页面不存在', '', 'error');
    }

    include_once $path;

    $class = "Controller_{$controller}";

    $controller = new $class();

    if (!method_exists($controller, $action)) {
        cpheader();
        cpmsg('页面不存在', '', 'error');
    }

    $controller->init();
    //运行
    $controller->$action();
}

/**
 * 上传图片
 * @param type $savedir
 * @param image $thumb
 * @param type $width
 * @param type $height
 * @param type $iskeybili
 * @return string
 */
function wxz_uploadimg($savedir = 'wxz_live/', $thumb = false, $width = '220', $height = '220', $iskeybili = '1') {

    //上传图片
    $images = array();
    foreach ($_FILES as $upfile_name => $value) {
        if ($_FILES[$upfile_name]['error'] == 0) {
            require_once('source/class/discuz/discuz_upload.php');
            $upload = new discuz_upload();
            if ($r1 = $upload->init($_FILES[$upfile_name], 'common') && $r2 = $upload->save(1)) {
                $pic = $_G['setting']['attachurl'] . 'common/' . $upload->attach['attachment'];
            }

            if ($thumb) {
                //缩略图
                require_once('source/class/class_image.php');
                //随机名称
                $ext = addslashes(strtolower(substr(strrchr($_FILES[$upfile_name]['name'], '.'), 1, 10)));
                $thumb = new image;
                $img_path = $savedir . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 8) . rand(10000, 99999) . '.' . $ext;

                $thumb->THumb($upload->attach['target'], $img_path, $width, $height, $iskeybili);
            }

            $img_path = $thumb ? $img_path : $pic;
            $images[$upfile_name] = 'data/attachment/' . $img_path;
        }
    }
    return $images;
}

?>
