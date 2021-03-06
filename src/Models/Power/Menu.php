<?php

/*
 * 菜单项
 */

namespace XiHuan\Crbac\Models\Power;

use XiHuan\Crbac\Models\Model;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class Menu extends Model {

    public static $_validator_rules = [//验证规则
        'name' => 'required|between:3,30|unique:power_menu', // varchar(35) not null comment '菜单名',
        'url' => 'required|between:1,55|unique:power_menu', // varchar(60) not null comment '链接地址',
        'power_item_id' => 'exists:power_item', // int unsigned not null default 0 comment '关联权限项ID',
        'comment' => 'required|between:1,955', //  varchar(1000) not null default '' comment '备注说明',
    ];
    public static $_validator_description = [//验证字段说明
        'name' => '菜单名', // varchar(35) not null comment '菜单名',
        'url' => '链接地址', // varchar(60) not null comment '链接地址',
        'power_item_id' => '权限项', // int unsigned not null default 0 comment '关联权限项ID',
        'comment' => '备注说明', // varchar(1000) not null default '' comment '备注说明',
    ];
    public static $_validator_messages = []; //验证统一说明
    protected $table = 'power_menu'; //表名
    protected $primaryKey = 'power_menu_id'; //主键名
    protected static $validates = ['name']; //允许验证可用字段

    /*
     * 作用：关联权限项
     * 参数：无
     * 返回值：Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function item() {
        return $this->hasOne(Item::class, 'power_item_id', 'power_item_id');
    }
    /*
     * 作用：关联菜单组
     * 参数：无
     * 返回值：Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups() {
        return $this->belongsToMany(MenuGroup::class, 'power_menu_level', $this->primaryKey, 'power_menu_group_id')
                        ->select('power_menu_group.*', 'power_menu_level.parent_id', 'power_menu_level.id as level_id');
    }
    /*
     * 作用：获取指定层级菜单
     * 参数：$group_id int 菜单组ID
     *      $parent_id int 上级ID
     * 返回值：Illuminate\Database\Eloquent\Collection
     */
    public static function level($group_id, $parent_id = 0) {
        return self::leftJoin('power_menu_level', 'power_menu_level.power_menu_id', '=', 'power_menu.power_menu_id')
                        ->where('power_menu_level.power_menu_group_id', '=', $group_id)
                        ->where('power_menu_level.parent_id', '=', $parent_id)
                        ->get(['power_menu.*', 'power_menu_level.id as level_id', 'power_menu_level.parent_id']);
    }
    /*
     * 作用：获取指定人员可用菜单
     * 参数：$admin Illuminate\Contracts\Auth\Authenticatable 当前登录用户Model
     * 返回值：Illuminate\Database\Eloquent\Collection
     */
    public static function menus(UserContract $admin) {
        return self::leftJoin('power_menu_level', 'power_menu_level.power_menu_id', '=', 'power_menu.power_menu_id')
                        ->where('power_menu_level.power_menu_group_id', '=', $admin->power_menu_group_id)
                        ->where(function($query)use($admin) {
                            $query->orWhere('power_menu.power_item_id', '=', '0')
                            ->orWhereIn('power_menu.power_item_id', function($query)use($admin) {
                                Item::addItemWhere($query, $admin);
                            })->orWhereIn('power_menu.power_item_id', function($query) {
                                $query->from('power_item')
                                ->where('status', '!=', 'enable')
                                ->select('power_item_id');
                            });
                        })
                        ->with('item')
                        ->orderBy('power_menu_level.sort', 'desc')
                        ->get(['power_menu.*', 'power_menu_level.id as level_id', 'power_menu_level.parent_id']);
    }
    /*
     * 作用：获取指定菜单组中的菜单列表
     * 参数：$group_id int 菜单组ID
     * 返回值：Illuminate\Database\Eloquent\Collection
     */
    public static function group($group_id) {
        return self::leftJoin('power_menu_level', 'power_menu_level.power_menu_id', '=', 'power_menu.power_menu_id')
                        ->where('power_menu_level.power_menu_group_id', '=', $group_id)
                        ->with('item')
                        ->orderBy('power_menu_level.sort', 'desc')
                        ->get(['power_menu.*', 'power_menu_level.id as level_id', 'power_menu_level.parent_id']);
    }
}
