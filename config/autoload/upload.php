<?php
/**
 * user:cjw
 * time:2022/5/9 0:23
 */
declare(strict_types=1);

return [
    // 上传文件保存配置，本地local，云oss
    'upload_save' => env('UPLOAD_SAVE', 'local'),

    // 文件上传允许类型
    'file_allow_files' => [
        "png", "jpg", "jpeg", "gif", "bmp",
        "flv", "swf", "mkv", "avi", "rm", "rmvb", "mpeg", "mpg",
        "ogg", "ogv", "mov", "wmv", "mp4", "webm", "mp3", "wav", "mid",
        "rar", "zip", "tar", "gz", "7z", "bz2", "cab", "iso",
        "doc", "docx", "xls", "xlsx", "ppt", "pptx", "pdf", "txt", "md", "xml", "apk"
    ],
    // 文件上传大小限制（单位字节B） 5MB
    'file_max_size' => 1024 * 1024 * 5,
    // 上传文件根目录
    'upload_path' => UPDATE_FILE . '/public/static/',
    // 上传文件目录
    'attachments' => 'tmp',
    // 伪静态路径前缀
    'rewrite' => 'static',

];
