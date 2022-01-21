<?php


namespace ModStart\Admin;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use ModStart\Module\ModuleManager;

class ModStartAdmin
{
    public static function registerAuthRoutes()
    {
        // echo config('modstart.admin.prefix');exit();
        Route::group([
            'prefix' => config('modstart.admin.prefix'),
            'namespace' => '\\ModStart\\Admin\\Controller',
            'middleware' => ['admin.bootstrap', 'admin.auth'],
        ], function ($router) {

            /* @var \Illuminate\Routing\Router $router */
            $router->match(['get', 'post'], 'login', 'AuthController@login');
            $router->match(['get', 'post'], 'logout', 'AuthController@logout');
            $router->match(['get', 'post'], 'login/captcha', 'AuthController@loginCaptcha');

            $router->match(['get', 'post'], 'sso/client', 'AuthController@ssoClient');
            $router->match(['get', 'post'], 'sso/server', 'AuthController@ssoServer');
            $router->match(['get', 'post'], 'sso/server_success', 'AuthController@ssoServerSuccess');
            $router->match(['get', 'post'], 'sso/server_logout', 'AuthController@ssoServerLogout');

            $router->match(['get', 'post'], 'util/frame', 'UtilController@frame');

            $router->match(['get', 'post'], 'system/clear_cache', 'SystemController@clearCache');
            $router->match(['get', 'post'], 'system/security_fix', 'SystemController@securityFix');

            $router->match(['get', 'post'], 'admin_role', 'AdminRoleController@index');
            $router->match(['get', 'post'], 'admin_role/add', 'AdminRoleController@add');
            $router->match(['get', 'post'], 'admin_role/edit', 'AdminRoleController@edit');
            $router->match(['get', 'post'], 'admin_role/delete', 'AdminRoleController@delete');
            $router->match(['get', 'post'], 'admin_role/show', 'AdminRoleController@show');

            $router->match(['get', 'post'], 'admin_user', 'AdminUserController@index');
            $router->match(['get', 'post'], 'admin_user/add', 'AdminUserController@add');
            $router->match(['get', 'post'], 'admin_user/edit', 'AdminUserController@edit');
            $router->match(['get', 'post'], 'admin_user/delete', 'AdminUserController@delete');
            $router->match(['get', 'post'], 'admin_user/show', 'AdminUserController@show');

            $router->match(['get', 'post'], 'profile/change_password', 'ProfileController@changePassword');

            $router->match(['get', 'post'], 'admin_log', 'AdminLogController@index');
            $router->match(['get', 'post'], 'admin_log/show', 'AdminLogController@show');
            $router->match(['get', 'post'], 'admin_log/delete', 'AdminLogController@delete');

            $router->match(['get', 'post'], 'data/file_manager/{category}', 'DataController@fileManager');
            $router->match(['get', 'post'], 'data/ueditor', 'DataController@ueditor');

        });

    }

    private static function listModuleRoutes()
    {
        $modules = ModuleManager::listAllInstalledModulesInRequiredOrder();
        $files = [];
        foreach ($modules as $module) {
            if (file_exists($file = ModuleManager::path($module, 'Admin/routes.php'))) {
                $files[$module] = $file;
            }
        }
        return $files;
    }

    public static function registerModuleRoutes()
    {
        if (config('env.APP_DEBUG')) {
            $routesFiles = self::listModuleRoutes();
        } else {
            $routesFiles = Cache::rememberForever('ModStartAdminRoutes', function () {
                return self::listModuleRoutes();
            });
        }
        foreach ($routesFiles as $module => $file) {
            Route::group([
                'prefix' => config('modstart.admin.prefix'),
                'middleware' => ['admin.bootstrap', 'admin.auth'],
                'namespace' => "\\Module\\$module\\Admin\\Controller",
            ], function ($router) use ($file) {
                require $file;
            });
        }
        if (file_exists($routes = modstart_admin_path('routes.php'))) {
            require $routes;
        }
    }
}
