<?php

namespace app\controller;

use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 动态 SVG Banner 生成控制器。
 *
 * GET /banners/{name}.svg — 按名称返回一张参数化生成的 SVG 轮播图。
 * 支持 ?title= 与 ?subtitle= 参数覆盖默认文案。
 */
class BannerController
{
    private const WIDTH = 640;
    private const HEIGHT = 200;

    /** 内置 banner 配置：name => [title, subtitle, 渐变起色, 渐变止色, 装饰色] */
    private const PRESETS = [
        'new-plugins'    => ['新插件上架', '每周都有新鲜工具', '#4A90D9', '#7B5BD6', '#B3C6F0'],
        'ai-tools'       => ['AI 工具推荐', '大模型能力加持', '#00CED1', '#4A90D9', '#A8E6E3'],
        'productivity'   => ['效率工具精选', '让工作事半功倍', '#FFA500', '#FF6B6B', '#FFD9A0'],
        'dev-tools'      => ['开发工具精选', '为开发者而生', '#2C3E50', '#4A90D9', '#A0C4FF'],
        'design-tools'   => ['设计工具精选', '释放你的创造力', '#FF1493', '#7B5BD6', '#FFB3D9'],
        'system-tools'   => ['系统工具精选', '掌控你的设备', '#27AE60', '#00CED1', '#A8E6CF'],
    ];

    public function svg(Request $request, string $name): Response
    {
        $config = self::PRESETS[$name] ?? null;
        if ($config === null) {
            return json(['error' => 'Banner 不存在'], 404);
        }

        [$title, $subtitle, $from, $to, $decor] = $config;
        $title = $this->sanitize((string)$request->get('title', $title));
        $subtitle = $this->sanitize((string)$request->get('subtitle', $subtitle));

        $svg = $this->buildSvg($title, $subtitle, $from, $to, $decor);

        return new Response(200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
        ], $svg);
    }

    private function buildSvg(string $title, string $subtitle, string $from, string $to, string $decor): string
    {
        $w = self::WIDTH;
        $h = self::HEIGHT;
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="{$h}" viewBox="0 0 {$w} {$h}">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$from}"/>
      <stop offset="100%" stop-color="{$to}"/>
    </linearGradient>
    <radialGradient id="glow" cx="80%" cy="20%" r="60%">
      <stop offset="0%" stop-color="{$decor}" stop-opacity="0.55"/>
      <stop offset="100%" stop-color="{$decor}" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="{$w}" height="{$h}" rx="12" fill="url(#bg)"/>
  <rect width="{$w}" height="{$h}" rx="12" fill="url(#glow)"/>
  <circle cx="520" cy="48" r="70" fill="{$decor}" opacity="0.18"/>
  <circle cx="570" cy="150" r="40" fill="#ffffff" opacity="0.12"/>
  <circle cx="90" cy="170" r="26" fill="#ffffff" opacity="0.10"/>
  <path d="M0 176 Q160 150 320 176 T640 172 V200 H0 Z" fill="#ffffff" opacity="0.08"/>
  <rect x="40" y="118" width="46" height="6" rx="3" fill="#ffffff" opacity="0.85"/>
  <text x="42" y="78" font-family="'PingFang SC','Microsoft YaHei',sans-serif" font-size="40" font-weight="700" fill="#ffffff">{$title}</text>
  <text x="42" y="108" font-family="'PingFang SC','Microsoft YaHei',sans-serif" font-size="18" fill="#ffffff" opacity="0.92">{$subtitle}</text>
  <g transform="translate(524, 86)">
    <rect x="0" y="0" width="80" height="28" rx="14" fill="#ffffff" opacity="0.22"/>
    <text x="40" y="19" font-family="'PingFang SC','Microsoft YaHei',sans-serif" font-size="13" fill="#ffffff" text-anchor="middle">立即查看</text>
  </g>
</svg>
SVG;
    }

    private function sanitize(string $value): string
    {
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
        return mb_substr($value, 0, 24);
    }
}
