<?php

namespace Magento\Framework\Filesystem;

if (!class_exists(\Magento\Framework\Filesystem\DirectoryList::class)) {
    class DirectoryList
    {
        public function __construct($root, array $config = [])
        {
        }

        public function getPath($code)
        {
            return '';
        }

        public function getRoot()
        {
            return '';
        }
    }
}
