<?php


namespace Codfrm\DzMarkdown;

use ParsedownExtra;


class ParsedownExt extends ParsedownExtra
{
    public function __construct()
    {
//        array_unshift($this->BlockTypes['<'], 'HtmlTag');
//        array_unshift($this->voidElements,'div');
    }

    public function inlineUrl($Excerpt)
    {
        $ret = parent::inlineUrl($Excerpt);
        if ($ret) {
            $ret['element']['attributes']['target'] = '_blank';
        }
        return $ret;
    }

    protected $callback = false;

    public function setImagecallback($callback)
    {
        $this->callback = $callback;
    }

    protected function inlineImage($Excerpt)
    {
        $ret = parent::inlineImage($Excerpt);
        if ($ret && $this->callback) {
            call_user_func($this->callback, $ret);
        }
        return $ret;
    }

    protected function blockListComplete(array $Block)
    {
        $list = parent::blockListComplete($Block);

        if (!isset($list)) {
            return $list;
        }

        if (!($list['element']['name'] == 'ul' || $list['element']['name'] == 'ol')) {
            return $list;
        }

        foreach ($list['element']['text'] as $key => $listItem) {
            $args = $listItem['text'];
            if (isset($args[0])) {
                $firstThree = mb_substr($args[0], 0, 3);
                $rest = trim(mb_substr($args[0], 3));
                if ($firstThree === '[x]' || $firstThree === '[ ]') {
                    $checked = $firstThree === '[x]' ? ' checked' : '';
                    $list['element']['text'][$key] = [
                        'name' => 'li',
                        'handler' => 'checkbox',
                        'text' => [
                            'checked' => $checked,
                            'text' => $rest
                        ],
                    ];
                }
            }
        }

        return $list;
    }

    public function checkbox($text)
    {
        return '<input type="checkbox" disabled ' . ($text['checked'] ? 'checked' : '') . ' /> ' . $text['text'];
    }


    protected $htmlTag = '/<(div|h[1-6]|font)(.*?)>/';
    protected $endHtmlTag = '/<\/(div|h[1-6]|font)>/';
    protected $allowAttr = '/(align|color)\s*=\s*"([#:\w]+?)"/';

    public function line($text, $nonNestables = array(), &$openTagNum = [], $main = true)
    {
        // 对html标签 div/h1-6 进行处理
        if (preg_match($this->htmlTag, $text, $match, PREG_OFFSET_CAPTURE)) {
            // 处理open tag
            // 替换掉html标签
            preg_match_all($this->allowAttr, $match[0][0], $attrMatches, PREG_SET_ORDER);
            $ret = "<{$match[1][0]} ";
            $openTagNum[$match[1][0]]++;
            foreach ($attrMatches as $k => $attrMatch) {
                $ret .= "{$attrMatch[1]}=\"{$attrMatch[2]}\"";
            }
            $ret = rtrim($ret);
            $ret .= ">" . $this->line(substr($text, $match[0][1] + strlen($match[0][0])), $nonNestables, $openTagNum, false);
            return $this->line(substr($text, 0, $match[0][1]), $nonNestables, $openTagNum, false) . $ret;
        } else if (preg_match($this->endHtmlTag, $text, $match, PREG_OFFSET_CAPTURE)) {
            // 处理close tag
            if (!$openTagNum[$match[1][0]]) {
                // 不存在opentag,直接返回空
                return '';
            }
            // 存在减去并处理
            $openTagNum[$match[1][0]]--;
            $ret = "</{$match[1][0]}>";
            $ret .= $this->line(substr($text, $match[0][1] + strlen($match[0][0])), $nonNestables, $openTagNum, false);
            return $this->line(substr($text, 0, $match[0][1]), $nonNestables, $openTagNum, false) . $ret;
        }
        $ret = parent::line($text, $nonNestables); // TODO: Change the autogenerated stub
        // 处理\n换行
        if ($main) {
            // 闭合标签
            foreach ($openTagNum as $k => $v) {
                $ret .= str_repeat("</{$k}>", $v);
            }
        }
        return str_replace("\n", "<br />", $ret);
    }

}
