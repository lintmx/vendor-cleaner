<?php

namespace Cleaner\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;

class VendorCleanerPlugin implements PluginInterface, EventSubscriberInterface
{
    private $io;

    private $config;

    // 忽略列表
    private $skipDir = [
        'bin',
        'composer',
        'lintmx'
    ];

    // 需要删除的目录 正则
    private $rmDir = [
        'doc.*',
        'test.*',
        '.*git',
        'example.*',
        'demo'
    ];

    // 需要删除的文件 正则
    private $rmFile = [
        '.*git',
        'FAQ.*',
        'README.*',
        'support.*',
        '.*php_cs',
        '.*travis',
        'mkdocs.*',
        'CHANGELOG.*',
        'UPGRADING.*',
        'UPGRADE.*',
        'phpunit.*',
        'CONTRIBUTING.*',
    ];

    /**
     * Composer Plugin 的入口
     *
     * @param Composer $composer
     * @param IOInterface $io
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->config = $composer->getConfig();
        $this->io = $io;
    }

    /**
     * 绑定事件
     *
     * @return void
     */
    public static function getSubscribedEvents()
    {
        // 当执行 install/update 后调用
        return [
            'post-autoload-dump' => 'clean'
        ];
    }

    /**
     * 清理 Vendor 目录
     *
     * @return void
     */
    public function clean()
    {
        // 获取 Vendor 目录路径
        $vendorDir = $this->config->get('vendor-dir');
        $confirm = $this->io->askConfirmation("Clean Vendor Directory ? (Default: yes)");

        $rmDirPreg = '/' . implode('|', $this->rmDir) . '/i';
        $rmFilePreg = '/' . implode('|', $this->rmFile) . '/i';
        
        if ($confirm) {
            $fileTool = new Filesystem();
            $vendorDir = dir($vendorDir);

            // 扫描 vendor 层
            while (false !== ($vendor = $vendorDir->read())) {
                if ($vendor == '.' ||
                    $vendor == '..' ||
                    in_array($vendor, $this->skipDir) ||
                    'file' === filetype($vendorDir->path . '\\' . $vendor)
                    ) {
                    continue;
                }

                $vendor = dir($vendorDir->path . '\\' . $vendor);

                // 扫描 project 层
                while (false !== ($project = $vendor->read())) {
                    if ($project === '.' ||
                        $project === '..' ||
                        in_array($project, $this->skipDir) ||
                        'file' === filetype($vendor->path . '\\' . $project)
                        ) {
                        continue;
                    }

                    $project = dir($vendor->path . '\\' . $project);

                    // 扫描判断是否需要删除
                    while (false !== ($file = $project->read())) {
                        if ($file === '.' || $file === '..') {
                            continue;
                        }

                        if ('dir' === filetype($project->path . '\\' . $file) &&
                            preg_match($rmDirPreg, $file)
                            ) {
                            $fileTool->removeDirectory($project->path . '\\' . $file);
                            continue;
                        }

                        if ('file' === filetype($project->path . '\\' . $file) &&
                            preg_match($rmFilePreg, $file)
                            ) {
                            unlink($project->path . '\\' . $file);
                            continue;
                        }
                    }
                }
            }
        }
    }
}