<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

/**
 * 后台管理系统API路由
 */
Route::group('/admin', function () {

    /* 获取后台菜单列表 */
    Route::get('/index/getNavbarList', 'Index/getNavbarList');

    /* 权限管理 */
    Route::group('/permission', function () {
        Route::get('/getList', 'Permission/getList')->name('permission.getList');
        Route::delete('/deleteAll', 'Permission/deleteAll')->name('permission.deleteAll');
        Route::delete('/delete', 'Permission/delete')->name('permission.delete');
    });

    /* 角色管理 */
    Route::group('/role', function () {
        Route::get('/getList', 'Role/getList')->name('role.getList');
        Route::delete('/deleteAll', 'Role/deleteAll')->name('role.deleteAll');
        Route::delete('/delete', 'Role/delete')->name('role.delete');
    });

    /* 用户管理 */
    Route::group('/admin', function () {
        Route::get('/getList', 'Admin/getList')->name('admin.getList');
        Route::delete('/deleteAll', 'Admin/deleteAll')->name('admin.deleteAll');
        Route::delete('/delete', 'Admin/delete')->name('admin.delete');
    });

    /* 爬虫管理 */
    Route::group('/spider', function () {
        Route::get('/getList', 'Spider/getList')->name('spider.getList');
        Route::delete('/deleteAll', 'Spider/deleteAll')->name('spider.deleteAll');
        Route::delete('/delete', 'Spider/delete')->name('spider.delete');
    });

    /* 爬虫分类管理 */
    Route::group('/spidercate', function () {
        Route::get('/getList', 'Spidercate/getList')->name('spidercate.getList');
        Route::delete('/deleteAll', 'Spidercate/deleteAll')->name('spidercate.deleteAll');
        Route::delete('/delete', 'Spidercate/delete')->name('spidercate.delete');
    });

})->prefix('\app\admin\api\\');


return [

];
