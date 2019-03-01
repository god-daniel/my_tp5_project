<?php

namespace app\admin\Transformers;

use App\admin\model\SpiderCate;
use League\Fractal\TransformerAbstract;

class SpiderCateTransformer extends TransformerAbstract
{
    public function transform(SpiderCate $spiderCate)
    {
        return [
            'id' => $spiderCate->sp_cateid,
            'name' => $spiderCate->cate_name,
            'description' => $spiderCate->cate_description,
            'created_at' => $spiderCate->created_at
        ];
    }
}