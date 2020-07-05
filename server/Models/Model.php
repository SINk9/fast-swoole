<?php

/**
 * Model 涉及到数据有关的处理
 * 对象池模式，实例会被反复使用，成员变量缓存数据记得在销毁时清理
 * @Author: sink
 * @Date:   2019-08-29 18:33:58
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-05 17:59:34
 */

namespace Server\Models;

use Server\CoreBase\CoreBase;


class Model extends CoreBase
{

    /**
     * 销毁回归对象池
     */
    public function destroy()
    {
        parent::destroy();
        ModelFactory::getInstance()->revertModel($this);
    }

}
