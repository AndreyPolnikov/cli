<?php

require_once __DIR__ . '/WpCliLoader.php';

class ReparseHTagsRestoreData extends WpCliLoader
{
    public static function handle()
    {
        (new self())->process();
    }

    private function process()
    {
        $langs = $this->getLanguages();

        $i = 0;
        foreach ($langs as $lang) {
            $langPosts = $this->getLanguagesPost($lang, true);

            foreach ($langPosts as $post) {
                $constructorBlocksMeta = get_post_meta($post, 'constructor_blocks', true);

                if (!$constructorBlocksMeta) {
                    continue;
                }

                $restoreData = $this->findBlockDataIfExist($constructorBlocksMeta, 'restore_data');
                if ($restoreData) {
                    $this->handleRestoreDataBlock($restoreData, $i);
                    $this->changeBlockData($constructorBlocksMeta, 'restore_data', $restoreData);
                }

                update_post_meta($post, 'constructor_blocks', $constructorBlocksMeta);
            }
        }
        $this->adminInfo('Move inline color style from `restore_data` block title tag to additional titleCssClass field for ' . $i . ' pages');
    }

    private function changeBlockData(array &$constructorBlocksMeta, string $blockName, array $newData)
    {
        foreach (($constructorBlocksMeta['wrapper'] ?? []) as $in => $item) {
            if ($item[$blockName] ?? null) {
                $constructorBlocksMeta['wrapper'][$in][$blockName] = $newData;
            }
        }
    }

    private function findBlockDataIfExist(array $constructorBlocksMeta, string $blockName)
    {
        foreach (($constructorBlocksMeta['wrapper'] ?? []) as $item) {
            if ($item[$blockName] ?? null) {
                return $item[$blockName];
            }
        }
        return null;
    }

    private function handleRestoreDataBlock(&$restoreData, &$i)
    {
        if ($restoreData['title'] ?? null) {
            $titleColor = $this->handleInlineTags($restoreData['title']);
            if ($titleColor) {
                $restoreData['titleCssClass'] = $titleColor;
                $i++;
            }
        }
    }

    private function handleInlineTags(&$textString) {
        if (!is_string($textString)) {
            return null;
        }

        if (strpos($textString, 'color:') === false) {
            $textString = strip_tags($textString);
            return null;
        }

        $class = null;
        $matches = [];
        preg_match('/.*#(.*);/', $textString, $matches);
        if ($matches[1] ?? null) {
            $class = 'color-' . $matches[1];
        }
        $textString = strip_tags($textString);

        return $class;
    }
}

ReparseHTagsRestoreData::handle();