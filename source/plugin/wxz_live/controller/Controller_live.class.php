<?php

class Controller_live extends Controller_base {

    public function __construct() {
        parent::__construct();

        //子导航
        $this->navs = array(
            array(
                'name' => '直播间列表',
                'act' => 'index',
            ),
            array(
                'name' => '添加直播间',
                'act' => 'liveSave',
            ),
        );
        $this->title = "直播间管理";
    }

    private function setLiveNav() {
        global $_G;

        $rid = $_GET['rid'];
        if (!$rid) {
            cpmsg('直播间不存在', $this->noRootUrl, 'success');
        }

        //子导航
        $this->navs = array(
            array(
                'name' => '活动设置',
                'act' => "activitySetting&rid={$rid}",
            ),
            array(
                'name' => '播放器设置',
                'act' => "playerSetting&rid={$rid}",
            ),
            array(
                'name' => '导航栏管理',
                'act' => "menuSetting&rid={$rid}",
            ),
            array(
                'name' => '访问限制',
                'act' => "viewLimit&rid={$rid}",
            ),
        );
        $this->title = "直播间设置";

        include_once DISCUZ_ROOT . "./source/plugin/wxz_live/table/table_wxz_live_base.php";

        $this->tableRoom = C::t('#wxz_live#wxz_live_room');
        $this->rid = $rid;
        $this->liveInfo = $this->tableRoom->getById($this->rid);
        $this->liveInfo['url'] = "{$_G['siteurl']}plugin.php?id=wxz_live:index&pmod=index&act=live&roomno={$this->liveInfo['room_no']}";
    }

    /**
     * 导航栏管理
     */
    public function menuSetting() {
        $do = $_GET['do'];

        $this->types = array(
            1 => 'iframe嵌入',
            2 => '图文信息介绍',
            3 => '聊天区',
            4 => '图文直播',
            5 => '榜单',
            6 => '地图',
        );

        if ($do) {
            $this->$do();
            return;
        }

        $this->setLiveNav();

        include_once DISCUZ_ROOT . "./source/plugin/wxz_live/table/table_wxz_live_base.php";
        $tableObj = new table_wxz_live_base(array('table' => 'wxz_live_menu', 'pk' => 'id'));

        if (submitcheck('ordersubmit')) {
            foreach ($_GET['ids'] as $k => $id) {
                $ret = $tableObj->updateById($id, array('sort_order' => $_GET['sort_orders'][$k]));
            }
        }

        $tableLiveSettingObj = new table_wxz_live_base(array('table' => 'wxz_live_menu', 'pk' => 'id'));

        $condition = "room_id={$this->rid}";
        $list = $tableLiveSettingObj->getAll($condition);

        include template('wxz_live:live/menuSettingList');
    }

    /**
     * 新增栏目
     */
    private function menuSettingSave() {
        global $_G;
        include_once DISCUZ_ROOT . "./source/plugin/wxz_live/table/table_wxz_live_base.php";

        $mid = $_GET['mid'];
        $type = $_GET['type'];

        $tableObj = new table_wxz_live_base(array('table' => 'wxz_live_menu', 'pk' => 'id'));

        if ($mid) {
            $info = $tableObj->getById($mid);
            $info['settings'] = $info['settings'] ? unserialize($info['settings']) : [];
            $type = $info['type'];
        }

        $this->setLiveNav();

        if (!$type) {
            include template('wxz_live:live/menuSettingType');
            return;
        }

        if (submitcheck('save')) {
            $saveData = array(
                'type' => $_GET['type'],
                'is_show' => $_GET['is_show'],
                'sort_order' => $_GET['sort_order'],
                'name' => $_GET['name'],
                'settings' => $_GET['settings'] ? serialize($_GET['settings']) : '',
            );

            if ($info) {
                $ret = $tableObj->updateById($info['id'], $saveData);
                if ($ret) {
                    cpmsg('设置成功', $this->noRootUrl . "&act=menuSetting" . "&rid={$this->rid}", 'success');
                }
            } else {
                $saveData['room_id'] = $this->rid;
                $saveData['create_at'] = date('Y-m-d H:i:s');
                $ret = $tableObj->insert($saveData);
                if ($ret) {
                    cpmsg('设置成功', $this->noRootUrl . "&act=menuSetting" . "&rid={$this->rid}", 'success');
                }
            }
        }

        include template('wxz_live:live/menuSettingSave');
    }

    /**
     * 活动配置
     */
    public function activitySetting() {
        global $_G;
        include_once DISCUZ_ROOT . "./source/plugin/wxz_live/table/table_wxz_live_base.php";

        $rid = $_GET['rid'];

        $this->setLiveNav();

        $tableRoom = C::t('#wxz_live#wxz_live_room');
        $tableLiveSettingObj = new table_wxz_live_base(array('table' => 'wxz_live_room_setting', 'pk' => 'id'));

        $liveInfo = $tableRoom->getById($rid);
        $info = $tableRoom->getSettingInfoByRoomId($rid);

        if (!$liveInfo) {
            cpmsg('直播间不存在', $this->noRootUrl, 'error');
        }

        $images = wxz_uploadimg();

        if (submitcheck('save')) {
            $saveData['activity_img'] = $images['activity_img'] ? $images['activity_img'] : $_GET['activity_img'];
            $saveData['follow_qrcode'] = $images['follow_qrcode'] ? $images['follow_qrcode'] : $_GET['follow_qrcode'];
            $saveData['default_avatar'] = $images['default_avatar'] ? $images['default_avatar'] : $_GET['default_avatar'];

            $saveData['activity_title'] = $_GET["activity_title"];
            $saveData['activity_desc'] = $_GET["activity_desc"];
            $saveData['follow_type'] = $_GET["follow_type"];
            $saveData['follow_url'] = $_GET["follow_url"];
            if ($saveData['follow_type'] == 3) {
                $saveData['follow_cache_day'] = $_GET["follow_cache_day"];
            }

            if ($info) {
                $ret = $tableLiveSettingObj->updateById($info['id'], $saveData);
                if ($ret) {
                    cpmsg('设置成功', $this->curNoRootUrlAct . "&rid={$_GET['id']}", 'success');
                }
            } else {
                $saveData['room_id'] = $rid;
                $saveData['create_at'] = date('Y-m-d H:i:s');
                $ret = $tableLiveSettingObj->insert($saveData);
                if ($ret) {
                    cpmsg('设置成功', $this->curNoRootUrlAct . "&rid={$rid}", 'success');
                }
            }
        }

        include template('wxz_live:live/activitySetting');
    }

    /**
     *
     * 访问限制 
     */
    public function viewLimit() {
        $this->setLiveNav();

        $this->liveInfo['limit_data'] = $this->liveInfo['limit_data'] ? unserialize($this->liveInfo['limit_data']) : [];

        if (submitcheck('save')) {
            $updateData['limit'] = $_GET['limit'];
            $limitData = array(
                'password' => $_GET["password{$_GET['limit']}"],
                'amount' => $_GET["amount{$_GET['limit']}"],
                'delayed' => $_GET["delayed{$_GET['limit']}"],
            );

            $updateData['limit_data'] = serialize($limitData);
            $ret = $this->tableRoom->updateById($this->rid, $updateData);

            if ($ret) {
                cpmsg('设置成功', $this->curNoRootUrlAct . "&rid={$this->rid}", 'success');
            }
        }
        include template('wxz_live:live/viewLimit');
    }

    /**
     * 直播间管理
     */
    public function playerSetting() {
        global $_G;
        include_once DISCUZ_ROOT . "./source/plugin/wxz_live/table/table_wxz_live_base.php";

        //子导航
        $this->setLiveNav();

        $tableRoom = C::t('#wxz_live#wxz_live_room');
        $tablePlayerObj = new table_wxz_live_base(array('table' => 'wxz_live_player', 'pk' => 'id'));

        $liveInfo = $tableRoom->getById($this->rid);
        $playerInfo = $tableRoom->getPlayerInfoByRoomId($this->rid);

        $liveInfo['url'] = "{$_G['siteurl']}plugin.php?id=wxz_live:index&pmod=index&act=live&roomno={$liveInfo['room_no']}";

        if (!$liveInfo) {
            cpmsg('直播间不存在', $this->noRootUrl, 'error');
        }

        if (submitcheck('save')) {
            $saveData['video_type'] = $_GET["video_type"];
            $saveData['settings'] = $this->_getPlayerSetting();
            $saveData['player_weight'] = $_GET["player_weight"];
            $saveData['player_height'] = $_GET["player_height"];

            if ($playerInfo) {
                $ret = $tablePlayerObj->updateById($playerInfo['id'], $saveData);
                if ($ret) {
                    cpmsg('设置成功', $this->curNoRootUrlAct . "&rid={$this->rid}", 'success');
                }
            } else {
                $saveData['room_id'] = $this->rid;
                $saveData['create_at'] = date('Y-m-d H:i:s');
                $ret = $tablePlayerObj->insert($saveData);
                if ($ret) {
                    cpmsg('设置成功', $this->curNoRootUrlAct . "&rid={$this->rid}", 'success');
                }
            }
        }

        include template('wxz_live:live/playerSetting');
    }

    /**
     * 获取播放器设置配置
     */
    private function _getPlayerSetting() {
        $result = array();

        $videoType = $_GET["video_type"];

        $images = wxz_uploadimg();

        $result["pic{$videoType}"] = $images["pic{$videoType}"] ? $images["pic{$videoType}"] : $_GET["pic{$videoType}"];
        $result["live_type{$videoType}"] = $_GET["live_type{$videoType}"];

        switch ($videoType) {
            case 1:
                break;
            case 2:
                $result['leshi_id'] = $_GET["leshi_id"];
                $result['leshi_uu'] = $_GET["leshi_uu"];
                $result['leshi_vu'] = $_GET["leshi_vu"];
                $result['leshi_pu'] = $_GET["leshi_pu"];
                break;
            case 3:
                $result["lrtmp{$videoType}"] = $_GET["lrtmp{$videoType}"];
                $result["lhls{$videoType}"] = $_GET["lhls{$videoType}"];
                break;
            case 4:
                $result["lrtmp{$videoType}"] = $_GET["lrtmp{$videoType}"];
                $result["lhls{$videoType}"] = $_GET["lhls{$videoType}"];
                break;
            case 5:
                $result['xsdroomid'] = $_GET["xsdroomid"];
                break;
            case 6:
                $result['video_code'] = $_GET["video_code"];
                break;
            case 7:
                $result['xmroomid'] = $_GET["xmroomid"];
                break;
            case 8:
                $result['sid'] = $_GET["sid"];
                $result['ssid'] = $_GET["tpl"];
                $result['tpl'] = $_GET["tpl"];
                break;
        }
        return serialize($result);
    }

    /**
     * 直播间列表页 
     */
    public function index() {
        $query['perpage'] = $_GET['perpage'] ? $_GET['perpage'] : 10;
        $query['room_no'] = trim($_GET['room_no']);
        $query['category_name'] = trim($_GET['category_name']);
        $query['start_time'] = $_GET['start_time'];
        $query['end_time'] = $_GET['end_time'];
        $query['orderby'] = $_GET['orderby'] ? $_GET['orderby'] : 'sort_order';
        $query['ordersc'] = $_GET['ordersc'] ? $_GET['ordersc'] : 'desc';
        $page = (int) $_GET['page'];
        $page = $page <= 0 ? 1 : $page;

        $tableObj = C::t('#wxz_live#wxz_live_room');

        if (submitcheck('ordersubmit')) {
            foreach ($_GET['ids'] as $k => $id) {
                $ret = $tableObj->updateById($id, array('sort_order' => $_GET['sort_orders'][$k]));
            }
        }

        $condition = "1=1";
        if ($query['category_name']) {
            $conditionCat = "`name` like '%{$query['category_name']}%'";
            $categorys = C::t('#wxz_live#wxz_live_category')->getAll($conditionCat, 'id');
            if (!$categorys) {
                $condition .= " AND 1!=1";
            } else {
                $categoryIds = array_column($categorys, 'id');
                $condition .= " AND category_id in(" . implode(',', $categoryIds) . ")";
            }
        }

        if ($query['start_time']) {
            $condition .= " AND `start_time` >= '{$query['start_time']}'";
        }

        if ($query['end_time']) {
            $condition .= " AND `end_time` <= '{$query['end_time']} 23:59:59'";
        }

        if ($query['room_no']) {
            $condition .= " AND room_no='{$query['room_no']}'";
        }

        $totalCount = $tableObj->count($condition);

        $mpurl = $this->baseUrl . "&" . http_build_query($query);

        $order = $query['orderby'] . ' ' . $query['ordersc'];
        $maxPage = ceil($totalCount / $query['perpage']);
        $page = $maxPage > 0 && $page >= $maxPage ? $maxPage : $page;

        $currentLimit = $query['perpage'] * ($page - 1);

        $limit = $currentLimit . ',' . $query['perpage'];

        $list = $tableObj->getAll($condition, '*', $order, $limit);

        foreach ($list as $k => $row) {
            $list[$k]['category'] = C::t('#wxz_live#wxz_live_category')->getById($row['category_id']);
        }

        $pageHtml = helper_page::multi($totalCount, $query['perpage'], $page, $mpurl);

        include template('wxz_live:live/index');
    }

    /**
     * 直播间保存页 
     */
    public function liveSave() {
        global $_G;
        $id = $_GET['id'];
        $tableObj = C::t('#wxz_live#wxz_live_room');
        if ($id) {
            $info = $tableObj->fetch($id);
            $info['url'] = "{$_G['siteurl']}plugin.php?id=wxz_live:index&pmod=index&act=live&roomno={$info['room_no']}";
            $encodeUrl = urlencode($info['url']);
            $info['qrcode'] = "?frame=no&action=plugins&operation=config&identifier=wxz_live&pmod=common&act=qrcode&data={$encodeUrl}";
            $info['style_extend'] = $info['style_extend'] ? unserialize($info['style_extend']) : [];
            $info['countdown_style'] = $info['countdown_style'] ? unserialize($info['countdown_style']) : [];
            $info['station_caption'] = $info['station_caption'] ? unserialize($info['station_caption']) : [];
            $info['online_user_config'] = $info['online_user_config'] ? unserialize($info['online_user_config']) : [];
        }

        //获取所有分类
        $categorys = C::t('#wxz_live#wxz_live_category')->getShowCategorys();

        if (submitcheck('save')) {
            $images = wxz_uploadimg();
            $saveData = array(
                'category_id' => $_GET['category_id'],
                'title' => $_GET['title'],
                'style' => $_GET['style'],
                'check_comment' => $_GET['check_comment'],
                'enable_redpacket' => $_GET['enable_redpacket'],
                'style_extend' => $_GET['style_extend'] ? serialize($_GET['style_extend']) : '',
                'countdown_style' => $_GET['countdown_style'] ? serialize($_GET['countdown_style']) : '',
                'station_caption' => $_GET['station_caption'] ? serialize($_GET['station_caption']) : '',
                'online_user_config' => $_GET['online_user_config'] ? serialize($_GET['online_user_config']) : '',
                'start_time' => $_GET['start_time'],
                'end_time' => $_GET['end_time'],
                'live_status' => $_GET['live_status'],
                'is_show' => $_GET['is_show'],
                'is_show_list' => $_GET['is_show_list'],
                'is_show_copyright' => $_GET['is_show_copyright'],
                'copyright' => $_GET['copyright'],
                'rule' => $_GET['rule'],
                'islinkurl' => $_GET['islinkurl'],
                'linkurl' => $_GET['linkurl'],
                'publisher' => $_GET['publisher'],
                'sort_order' => $_GET['sort_order'],
            );
            $saveData['theme_pic'] = $images['theme_pic'] ? $images['theme_pic'] : $_GET['theme_pic'];
            $saveData['cover_pic'] = $images['cover_pic'] ? $images['cover_pic'] : $_GET['cover_pic'];
            $saveData['publisher_avatar'] = $images['publisher_avatar'] ? $images['publisher_avatar'] : $_GET['publisher_avatar'];
            if ($id) {
                //更新直播分类
                $ret = $tableObj->update([$id], $saveData);
                if ($ret) {
                    cpmsg('更新成功', $this->noRootUrl, 'success');
                }
            } else {
                $roomNo = random(10); //房间号
                //添加直播分类
                $saveData['room_no'] = $roomNo;
                $saveData['create_at'] = date('Y-m-d H:i:s');
                $ret = $tableObj->insert($saveData);
                if ($ret) {
                    cpmsg('添加成功', $this->noRootUrl, 'success');
                }
            }
        }
        include template('wxz_live:live/liveSave');
    }

}

?>
