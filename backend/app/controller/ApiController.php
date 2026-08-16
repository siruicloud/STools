<?php

namespace app\controller;

use support\Request;
use support\Response;

class ApiController
{
    /**
     * 示例接口：GET /api/hello
     */
    public function hello(Request $request): Response
    {
        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'message' => 'Hello ZTools API',
                'time' => time(),
            ],
        ]);
    }

    /**
     * 示例接口：GET /api/plugins
     * 演示返回插件列表格式（对应 docs/plugin-list-format.md 中的 PluginMarketPlugin）
     */
    public function plugins(Request $request): Response
    {
        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                [
                    'name' => 'morse-code',
                    'version' => '1.0.0',
                    'title' => '莫斯密码',
                    'description' => '莫斯密码加解密工具',
                    'author' => 'Zing',
                    'size' => 51200,
                    'downloadCount' => 1520,
                    'categoryId' => 1,
                    'categoryTitle' => '效率工具',
                ],
            ],
        ]);
    }
}
