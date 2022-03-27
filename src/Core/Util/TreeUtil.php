<?php

namespace ModStart\Core\Util;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ModStart\Core\Dao\ModelUtil;

class TreeUtil
{
    /**
     * 默认子元素键
     * @var string
     */
    static $CHILD_KEY = '_child';

    /**
     * 设置子元素建
     * @param $key
     */
    public static function setChildKey($key)
    {
        self::$CHILD_KEY = $key;
    }

    /**
     * 将模型所有数据转换为Tree
     * @param $model
     * @param array $fieldsMap $fieldsMap = [title=>titleField,...]
     * @param string $keyId
     * @param string $keyPid
     * @param string $keySort
     * @param array $where
     * @return array
     */
    public static function modelToTree($model, $fieldsMap = [], $keyId = 'id', $keyPid = 'pid', $keySort = 'sort', $where = [])
    {
        $models = ModelUtil::all($model, $where);
        $nodes = [];
        foreach ($models as &$model) {
            $node = [];
            $node[$keyId] = $model[$keyId];
            $node[$keyPid] = $model[$keyPid];
            $node[$keySort] = $model[$keySort];
            foreach ($fieldsMap as $k => $v) {
                if (is_numeric($k)) {
                    $node[$v] = $model[$v];
                } else {
                    $node[$k] = $model[$v];
                }
            }
            $nodes[] = $node;
        }
        return self::nodesToTree($nodes, 0, $keyId, $keyPid, $keySort);
    }

    /**
     * 读取模型部分节点（根据pid筛选），组装成为一个Tree
     * @param $pid
     * @param $model
     * @param array $fieldsMap
     * @param string $keyId
     * @param string $keyPid
     * @param string $keySort
     * @return array
     */
    public static function modelToTreeByParentId($pid, $model, $fieldsMap = [], $keyId = 'id', $keyPid = 'pid', $keySort = 'sort')
    {
        $models = [];

        $m = ModelUtil::get($model, [$keyId => $pid]);
        if (empty($m)) {
            return [];
        }
        $topPid = $m[$keyPid];
        $models[] = $m;

        $ms = ModelUtil::all($model, [$keyPid => $pid]);
        do {
            $parentIds = [];
            foreach ($ms as &$m) {
                $parentIds[] = $m[$keyId];
                $models[] = $m;
            }
            if (empty($parentIds)) {
                $ms = null;
            } else {
                $ms = ModelUtil::model($model)->whereIn($keyPid, $parentIds)->get()->toArray();
            }
        } while (!empty($ms));

        $nodes = [];
        foreach ($models as &$model) {
            $node = [];
            $node[$keyId] = $model[$keyId];
            $node[$keyPid] = $model[$keyPid];
            $node[$keySort] = $model[$keySort];
            foreach ($fieldsMap as $k => $v) {
                $node[$k] = $model[$v];
            }
            $nodes[] = $node;
        }
        return self::nodesToTree($nodes, $topPid, $keyId, $keyPid, $keySort);
    }

    /**
     * 检测模型节点是否能够删除，以下情形不能删除
     * - 如果有子节点则不能删除
     * @param $model
     * @param $id
     * @param string $pidName
     * @param array $where
     * @return bool
     */
    public static function modelNodeDeleteAble($model, $id, $pidName = 'pid', $where = [])
    {
        return !ModelUtil::exists($model, array_merge($where, [$pidName => $id]));
    }

    /**
     * 建的模型节点是否能够修改，以下情况不能修改
     * - 如果修改后变成一个环
     * - 如果修改后节点断裂
     * @param $model
     * @param $id
     * @param $fromPid
     * @param $toPid
     * @param string $idName
     * @param string $pidName
     * @param array $where
     * @return bool
     */
    public static function modelNodeChangeAble($model, $id, $fromPid, $toPid, $idName = 'id', $pidName = 'pid', $where = [])
    {
        if ($fromPid == $toPid) {
            return true;
        }

        $_toPid = $toPid;

        while ($m = ModelUtil::get($model, array_merge($where, [$idName => $_toPid]))) {
            if ($m[$idName] == $id) {
                return false;
            }
            $_toPid = $m[$pidName];
        }

        return true;
    }

    /**
     * 将数组转换为Tree
     * @param $nodes
     * @param int $pid
     * @param string $idName
     * @param string $pidName
     * @param string $sortName
     * @param string $sortDirection
     * @return array
     */
    public static function nodesToTree(&$nodes, $pid = 0, $idName = 'id', $pidName = 'pid', $sortName = 'sort', $sortDirection = 'asc')
    {
        if ($sortName && $sortDirection) {
            $nodes = ArrayUtil::sortByKey($nodes, $sortName, $sortDirection);
        }
        $items = [];
        foreach ($nodes as $v) {
            $items[$v[$idName]] = $v;
        }
        $tree = [];
        foreach ($items as $item) {
            if (isset($items[$item[$pidName]])) {
                $items[$item[$pidName]][self::$CHILD_KEY][] = &$items[$item[$idName]];
            } else {
                $tree[] = &$items[$item[$idName]];
            }
        }
        return array_values(array_filter($tree, function ($o) use ($pidName, $pid) {
            return $o[$pidName] == $pid;
        }));
    }

    /**
     * 将Tree转换为带缩进的List，主要用于select操作
     * @param $tree
     * @param string $keyId
     * @param string $keyTitle
     * @param int $level
     * @param array $keepKeys
     * @return array
     */
    public static function treeToListWithIndent(&$tree, $keyId = 'id', $keyTitle = 'title', $level = 0, $keepKeys = [])
    {
        $options = array();
        foreach ($tree as &$r) {
            $item = array(
                'id' => $r[$keyId],
                'title' => str_repeat('├', $level) . htmlspecialchars($r[$keyTitle]),
            );
            if (!empty($keepKeys)) {
                foreach ($keepKeys as $k) {
                    $item[$k] = $r[$k];
                }
            }
            $options[] = $item;
            if (!empty($r[self::$CHILD_KEY])) {
                $options = array_merge($options, self::treeToListWithIndent($r[self::$CHILD_KEY], $keyId, $keyTitle, $level + 1, $keepKeys));
            }
        }
        return $options;
    }

    /**
     * @param $tree
     * @param string $keyId
     * @param string $keyTitle
     * @param string $keyPid
     * @param int $level
     * @param array $fieldsMap
     * @return array
     * @since 1.7.0 add $fieldsMap
     */
    public static function treeToListWithLevel(&$tree, $keyId = 'id', $keyTitle = 'title', $keyPid = 'pid', $level = 0, $fieldsMap = [])
    {
        $options = array();
        foreach ($tree as &$r) {
            $option = array('id' => $r[$keyId], 'title' => $r[$keyTitle], 'level' => $level, 'pid' => $r[$keyPid],);
            if (!empty($fieldsMap)) {
                foreach ($fieldsMap as $k => $v) {
                    $option[$k] = $r[$v];
                }
            }
            $options[] = $option;
            if (!empty($r[self::$CHILD_KEY])) {
                $options = array_merge($options, self::treeToListWithLevel($r[self::$CHILD_KEY], $keyId, $keyTitle, $keyPid, $level + 1, $fieldsMap));
            }
        }
        return $options;
    }

    /**
     * 将Tree转换为Map，键为title-title，值为id
     * @param $tree
     * @param string $keyId
     * @param string $keyTitle
     * @param string $keyPid
     * @param string $join
     * @param array $prefix
     * @return array
     */
    public static function treeToTitleIdMap(&$tree, $keyId = 'id', $keyTitle = 'title', $keyPid = 'pid', $join = '-', $prefix = [])
    {
        $map = array();
        foreach ($tree as &$r) {
            $map[join($join, array_merge($prefix, [$r[$keyTitle]]))] = $r[$keyId];
            if (!empty($r[self::$CHILD_KEY])) {
                $map = array_merge($map, self::treeToTitleIdMap($r[self::$CHILD_KEY], $keyId, $keyTitle, $keyPid, $join, array_merge($prefix, [$r[$keyTitle]])));
            }
        }
        return $map;
    }

    public static function nodesChildrenIds(&$nodes, $id, $pk_name = 'id', $pid_name = 'pid')
    {
        $ids = [];
        foreach ($nodes as &$li) {
            if ($li[$pid_name] == $id) {
                $ids[] = $li[$pk_name];
                $childIds = self::nodesChildrenIds($nodes, $li[$pk_name], $pk_name, $pid_name);
                if (!empty($childIds)) {
                    $ids = array_merge($ids, $childIds);
                }
            }
        }
        return $ids;
    }

    /**
     * 根据id计算Tree的所有上级
     * @param $tree
     * @param $id
     * @param string $pk_name
     * @param string $pid_name
     * @param array $chain
     * @param int $level
     * @return array|mixed
     */
    public static function treeChain(&$tree, $id, $pk_name = 'id', $pid_name = 'pid', $chain = [], $level = 0)
    {
        // echo json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);exit();
        // echo "treeChain - $level - $id - " . json_encode($chain, JSON_UNESCAPED_UNICODE) . "\n";
        foreach ($tree as $item) {
            /**
             * Reset Root
             * @since 1.8.0
             */
            if (!$item[$pid_name]) {
                $chain = [];
            }
            if ($item[$pk_name] == $id) {
                $chain[] = $item;
                return $chain;
            }
            if (!empty($item[self::$CHILD_KEY])) {
                $chain[] = ArrayUtil::removeKeys($item, [self::$CHILD_KEY]);
                $results = self::treeChain($item[self::$CHILD_KEY], $id, $pk_name, $pid_name, $chain, $level + 1);
                if (!empty($results)) {
                    return $results;
                }
                array_pop($chain);
            }
        }
        return [];
    }

    /**
     * 根据id计算节点的所有上级
     * @param $nodes
     * @param $id
     * @param string $pk_name
     * @param string $pid_name
     * @return array
     */
    public static function nodesChain(&$nodes, $id, $pk_name = 'id', $pid_name = 'pid')
    {
        $chain = [];
        $limit = 0;
        $found = true;
        while ($found && $limit++ < 999) {
            $found = false;
            foreach ($nodes as $li) {
                if ($li[$pk_name] == $id) {
                    $found = true;
                    $id = $li[$pid_name];
                    $chain[] = $li;
                    break;
                }
            }
        }
        return array_reverse($chain);
    }

    /**
     * 根据id计算节点所有上级，同时附带每一级的可选元素，通常用于无线级的分类前端展示
     * @param $nodes array
     * @param $id integer
     * @param string $idName
     * @param string $pidName
     * @param string $titleName
     * @param string $itemName
     * @return array
     */
    public static function nodesChainWithItems(&$nodes, $id, $idName = 'id', $pidName = 'pid', $titleName = 'title', $itemName = '_items')
    {
        $categoryChain = self::nodesChain($nodes, $id);
        if (empty($categoryChain)) {
            $categoryChain[] = [
                $idName => -1,
                $pidName => 0,
                $titleName => 'ROOT',
            ];
        }
        foreach ($categoryChain as $k => $v) {
            $categoryChain[$k][$itemName] = array_values(array_filter($nodes, function ($o) use ($v, $pidName) {
                return $o[$pidName] == $v[$pidName];
            }));
        }
        $categoryChainNext = array_values(array_filter($nodes, function ($o) use ($id, $pidName) {
            return $o[$pidName] == $id;
        }));
        if (!empty($categoryChainNext) && $id > 0) {
            $categoryChain[] = [
                $idName => -1,
                $pidName => $id,
                $titleName => 'NEXT',
                $itemName => $categoryChainNext,
            ];
        }
        return $categoryChain;
    }
    
    /**
     * 为列表增加_level属性
     * @param $items
     * @param string $idName
     * @param string $pidName
     * @param string $sortName
     * @param int $pid
     * @param int $level
     * @param null $newItems
     * @return Collection|mixed|null
     */
    public static function itemsMergeLevel($items, $idName = 'id', $pidName = 'pid', $sortName = 'sort', $pid = 0, $level = 1, $newItems = null)
    {
        if (!($items instanceof Collection)) {
            $items = collect($items);
        }
        if ($level == 1) {
            $items = $items->sortBy($sortName)->values();
        }
        if (null === $newItems) {
            $newItems = collect();
        }
        $items->each(function ($item) use ($idName, $pidName, $sortName, $pid, $level, $items, $newItems) {
            if ($item instanceof Model) {
                if (!isset($item->_level)) {
                    if ($item->{$pidName} == $pid) {
                        $item->_level = $level;
                        $newItems->push($item);
                        if ($level < 99) {
                            self::itemsMergeLevel($items, $idName, $pidName, $sortName, $item->{$idName}, $level + 1, $newItems);
                        }
                    }
                }
            } else {
                if (!property_exists($item, '_level')) {
                    if ($item->{$pidName} == $pid) {
                        $item->_level = $level;
                        $newItems->push($item);
                        if ($level < 99) {
                            self::itemsMergeLevel($items, $idName, $pidName, $sortName, $item->{$idName}, $level + 1, $newItems);
                        }
                    }
                }
            }
        });
        return $newItems;
    }

    /**
     * 检测模型节点是否能增加
     * - 如果有子节点则不能删除
     * @param Model $model
     * @param $id
     * @param string $idName
     * @param array $where
     * @return bool
     */
    public static function modelItemAddAble(Model $model, $pid, $idName = 'id')
    {
        return $model->newQuery()->where([$idName => $pid])->exists();
    }

    /**
     * 检测模型节点是否能够删除，以下情形不能删除
     * - 如果有子节点则不能删除
     * @param Model $model
     * @param $id
     * @param string $pidName
     * @param array $where
     * @return bool
     */
    public static function modelItemDeleteAble(Model $model, $id, $pidName = 'pid')
    {
        return !$model->newQuery()->where([$pidName => $id])->exists();
    }

    /**
     * 建的模型节点是否能够修改，以下情况不能修改
     * - 如果修改后变成一个环
     * - 如果修改后节点断裂
     * @param $model
     * @param $id
     * @param $fromPid
     * @param $toPid
     * @param string $idName
     * @param string $pidName
     * @param array $where
     * @return bool
     */
    public static function modelItemChangeAble(Model $model, $id, $fromPid, $toPid, $idName = 'id', $pidName = 'pid')
    {
        if ($fromPid == $toPid) {
            return true;
        }
        $columns = [$idName, $pidName];
        $_toPid = $toPid;
        while ($m = $model->newQuery()->where([$idName => $_toPid])->first($columns)) {
            if ($m->{$idName} == $id) {
                return false;
            }
            $_toPid = $m->{$pidName};
        }
        return true;
    }

}
