<?php


namespace ModStart\Admin\Controller;


use Illuminate\Routing\Controller;
use ModStart\Admin\Auth\Admin;
use ModStart\Admin\Auth\AdminPermission;
use ModStart\Admin\Concern\HasAdminCRUD;
use ModStart\Admin\Model\AdminLog;
use ModStart\Admin\Type\AdminLogType;
use ModStart\Core\Input\Response;
use ModStart\Core\Util\CRUDUtil;
use ModStart\Detail\Detail;
use ModStart\Form\Form;
use ModStart\Grid\Grid;
use ModStart\Grid\GridFilter;

class AdminLogController extends Controller
{
    use HasAdminCRUD;

    /**
     * 是否允许删除管理日志
     * 默认禁止删除，仅 .env 中 ADMIN_LOG_DELETE_USER_IDS 配置的管理员用户ID可删除
     * @return boolean
     */
    public static function allowDelete()
    {
        if (AdminPermission::isNotPermit('AdminLogManage')) {
            return false;
        }
        $adminUserIds = config('modstart.admin.logDeleteUserIds', '');
        if (is_array($adminUserIds)) {
            $adminUserIds = join(',', $adminUserIds);
        }
        $adminUserIds = array_filter(array_map('intval', preg_split('/[\s,]+/', trim('' . $adminUserIds))));
        if (empty($adminUserIds)) {
            return false;
        }
        return in_array(Admin::id(), $adminUserIds, true);
    }

    protected function grid()
    {
        $grid = new Grid(AdminLog::class, function (Grid $grid) {
            $grid->display('id', L('ID'))->width(80);
            $grid->select('adminUserId', L('AdminUser'))->optionModel('admin_user', 'id', 'username');
            $grid->type('type', L('Type'))->type(AdminLogType::class)->width(100);
            $grid->display('summary', L('Title'));
            $grid->jsonKeyValue('data.content', L('Data'))->width(200);
            $grid->display('created_at', L('CreatedAt'))->width(160);
            $grid->gridFilter(function (GridFilter $filter) {
                $filter->eq('adminUserId', L('AdminUser'))->selectModel('admin_user', 'id', 'username');
                $filter->like('summary', L('Title'));
                $filter->eq('type', L('Type'))->radio(AdminLogType::class);
            });
        });
        $canDelete = self::allowDelete();
        $grid->canAdd(false)->canEdit(false);
        $grid->canDelete($canDelete);
        if ($canDelete) {
            $grid->canMultiSelectItem(true)->canBatchDelete(true);
        }
        $grid->title(L('AdminLog'));
        return $grid;
    }

    protected function form()
    {
        $form = new Form(AdminLog::class, function (Form $form) {
            $form->display('id', L('ID'))->editable(true);
        });
        $form->canDelete(self::allowDelete());
        return $form;
    }

    protected function detail()
    {
        $detail = new Detail(AdminLog::class, function (Detail $detail) {
            $detail->display('id', L('ID'));
            $detail->display('created_at', L('CreatedAt'));
            $detail->type('type', L('Type'))->type(AdminLogType::class);
            $detail->display('summary', L('Title'));
            $detail->jsonKeyValue('data.content', L('Data'));
        });
        $detail->title(L('AdminLog'));
        return $detail;
    }

    /**
     * 删除管理日志（默认禁止，仅允许 .env 白名单内的管理员）
     * @return mixed
     */
    public function delete()
    {
        if (!self::allowDelete()) {
            return Response::jsonError(L('AdminLogDeleteDisabled'));
        }
        AdminPermission::demoCheck();
        return $this->getForm()->deleteRequest(CRUDUtil::ids());
    }
}
