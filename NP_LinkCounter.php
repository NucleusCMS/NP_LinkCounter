<?php

if ( ! function_exists('try_define')) {
    function try_define($name, $value)
    {
        if ( ! defined($name)) {
            define($name, $value);
        }
    }
}

class NP_LinkCounter extends NucleusPlugin
{
    public function getName()
    {
        return 'Link Counter';
    }
    public function getAuthor()
    {
        return 'yu';
    }
    public function getURL()
    {
        return 'http://works.datoka.jp/';
    }
    public function getVersion()
    {
        return '0.6';
    }
    public function getMinNucleusVersion()
    {
        return 350;
    }
    public function getTableList()
    {
        return array( sql_table('plug_linkcounter') );
    }
    public function getEventList()
    {
        return array( 'PreItem' );
    }
    public function supportsFeature($feature)
    {
        return in_array($feature, ['SqlTablePrefix', 'HelpPage']);
    }

    public function getDescription()
    {
        return 'Link counter. [USAGE] mediavar - <%media(file|text|linkcnt=keyword)%> '.
            'or itemvar - <%LinkCounter(link,url,linktext,target,title)%> '.
            'or <%LinkCounter(total,keyword)%>';
    }

    public function install()
    {
        sql_query("CREATE TABLE IF NOT EXISTS ". sql_table('plug_linkcounter') ." (
            lkey VARCHAR(64)  NOT NULL,
            cnt  INT UNSIGNED NOT NULL DEFAULT 1,
            url  VARCHAR(255) NOT NULL DEFAULT '',
            primary key (lkey))");

        $this->createOption(
            'tpl_cnt',
            _LINKCOUNTER_OPT_DESC_TPL_CNT,
            'text',
            '[$cnt$word]'
        );
        $this->createOption(
            'tpl_word1',
            _LINKCOUNTER_OPT_DESC_TPL_WORD1,
            'text',
            'click'
        );
        $this->createOption(
            'tpl_word2',
            _LINKCOUNTER_OPT_DESC_TPL_WORD2,
            'text',
            'clicks'
        );
        $this->createOption(
            'flg_auto',
            _LINKCOUNTER_OPT_DESC_FLG_AUTO,
            'yesno',
            'yes'
        );
        $this->createOption(
            'exkey',
            _LINKCOUNTER_OPT_DESC_EXKEY,
            'text',
            'feeds'
        );
        $this->createOption(
            'flg_erase',
            _LINKCOUNTER_OPT_DESC_FLG_ERASE,
            'yesno',
            'no'
        );
    }

    public function unInstall()
    {
        if ($this->getOption('flg_erase') !== 'yes') {
            return;
        }
        sql_query('DROP TABLE ' . sql_table('plug_linkcounter'));
    }

    public $authorid;
    public $link;
    public $tpl_cnt;
    public $tpl_word1;
    public $tpl_word2;
    public $flg_auto;
    public $exkey;

    public function init()
    {
        $language = str_replace(["\\",'/', DIRECTORY_SEPARATOR ], '', getLanguageName());
        if (file_exists($this->getDirectory()."language/{$language}.php")) {
            include_once($this->getDirectory()."language/{$language}.php");
        } else {
            include_once($this->getDirectory().'language/english-utf8.php');
        }

        $this->tpl_cnt   = $this->getOption('tpl_cnt');
        $this->tpl_word1 = $this->getOption('tpl_word1');
        $this->tpl_word2 = $this->getOption('tpl_word2');
        $this->flg_auto  = ($this->getOption('flg_auto') === 'yes');
        $this->exkey     = $this->getOption('exkey');

        $query = sprintf("SHOW TABLES LIKE '%s'", sql_table('plug_linkcounter'));
        if (sql_num_rows(sql_query($query)) <= 0) {
            return;
        }

        $query = "SELECT * FROM " . sql_table('plug_linkcounter');
        $res   = sql_query($query);
        while ($link = sql_fetch_object($res)) { //copy all data
            if (!isset($this->link[$link->lkey])) {
                $this->link[$link->lkey] = array();
            }
            $this->link[$link->lkey]['cnt'] = (int)$link->cnt;
            $this->link[$link->lkey]['url'] = stripslashes($link->url);
        }
    }

    public function doSkinVar($skinType, $mode = 'total', $key = '', $url = '', $linktext = '', $target = '', $title = '')
    {
        if ($mode === 'link' && $key) {
            $cnt = (isset($this->link[$key]) && isset($this->link[$key]['cnt'])) ? (int)$this->link[$key]['cnt'] : 0;
            echo $this->_make_link($key, $url, $linktext, $target, $title);
            echo $this->_make_counter($cnt);
            return;
        }
        if ($mode !== 'total' || !$key) {
            return;
        }
        echo $this->_make_counter($this->_get_total($key));
    }

    public function doItemVar(&$item, $mode = 'total', $key = '', $url = '', $linktext = '', $target = '', $title = '')
    {
        $this->doSkinVar('', $mode, $key, $url, $linktext, $target, $title);
    }

    public function doTemplateVar(&$item, $mode = 'total', $key = '', $url = '', $linktext = '', $target = '', $title = '')
    {
        $this->doSkinVar('', $mode, $key, $url, $linktext, $target, $title);
    }

    public function event_PreItem($data)
    {
        // prepare
        $tgt = '/<%media\((.+?)\)%>/';

        // convert to linkcounter
        $obj            = &$data["item"];
        $this->authorid = $obj->authorid;
        $obj->body      = preg_replace_callback($tgt, array(&$this, 'makelink_callback'), $obj->body);
        $obj->more      = preg_replace_callback($tgt, array(&$this, 'makelink_callback'), $obj->more);
    }

    public function doAction($type)
    {
        if ($type !== 'c') {
            redirect(serverVar('HTTP_REFERER'));
            return;
        }

        $key = urldecode(getVar('k'));
        $url = getVar('url');

        if (!isset($this->link[$key]) || empty($this->link[$key]['cnt'])) {
            if (!$url) {
                $url = serverVar('HTTP_REFERER');
            }
            sql_query(
                sprintf(
                    'INSERT INTO %s SET lkey=%s, cnt=1, url=%s',
                    sql_table('plug_linkcounter'),
                    $this->quote_smart($key),
                    $this->quote_smart($url)
                )
            );
            redirect($this->encodeURI($url));
            return;
        }
        $query = sprintf(
            "UPDATE %s SET cnt=%d WHERE lkey=%s",
            sql_table('plug_linkcounter'),
            $this->link[$key]['cnt'] + 1,
            $this->quote_smart($key)
        );
        sql_query($query);
        if (!$url) {
            $url = $this->link[$key]['url'];
        }
        redirect($this->encodeURI($url));
    }

    public function makelink_callback($m)
    {
        global $CONF;

        $mcnt = count($m);

        if ($mcnt != 2) {
            return $m[0];
        }

        // media var
        $mvar = explode('|', $m[1]);
        if (!isset($mvar[2]) || !$mvar[2]) { // no extra property
            if (!$this->flg_auto) {
                return $m[0];
            } // return as it is
            list($key, $tgt, $tit, $linktext) = array($mvar[0], '', '', $mvar[1]);
        } else {
            $lc = explode('linkcnt=', $mvar[2]);
            if (!$lc[1]) { // no linkcnt property
                return $m[0]; // return as it is
            }
            list($key, $tgt, $tit, $linktext) = array($lc[1], '', '', $mvar[1]);
        }

        if (strpos($mvar[0], '/') !== false) {
            $memberdir = '';
        } else {
            $memberdir = $this->authorid . '/';
        }

        $cnt = (isset($this->link[$key]) && isset($this->link[$key]['cnt'])) ? (int)$this->link[$key]['cnt'] : 0;

        return $this->_make_link(
            $key,
            $CONF['MediaURL'] . $memberdir . $mvar[0],
            $linktext,
            $tgt,
            $tit
        )
            . $this->_make_counter($cnt);
    }

    // helper function
    private function _make_link($key, $url, $linktext, $tgt, $tit)
    {
        global $CONF;

        $saved_url = (isset($this->link[$key]) && isset($this->link[$key]['url'])) ? $this->link[$key]['url'] : '';
        if ($saved_url && $url === $saved_url) {
            $url = '';
        }

        $retlink = sprintf(
            '<a href="%s&amp;k=%s%s" %s %s>%s</a>',
            $CONF['ActionURL'] .'?action=plugin&amp;name=LinkCounter&amp;type=c',
            urlencode($key),
            $url ? sprintf('&amp;url=%s', $url) : '',
            $tgt ? sprintf('target="%s"', $tgt) : '',
            $tit ? sprintf('title="%s"', $tit) : '',
            $linktext
        );

        return $retlink;
    }

    private function _make_counter($cnt)
    {
        global $currentSkinName;

        if (strpos($currentSkinName, $this->exkey) !== false) {
            return '';
        }

        return str_replace(
            array('$cnt',    '$word'),
            array(
                (int)$cnt,
                $cnt <= 1 ? $this->tpl_word1 : $this->tpl_word2
            ),
            $this->tpl_cnt
        );
    }

    private function _get_total($key)
    {
        $rcnt = sql_fetch_object(
            sql_query(
                sprintf(
                    'SELECT SUM(cnt) AS cnt FROM %s WHERE lkey LIKE %s',
                    sql_table('plug_linkcounter'),
                    $this->quote_smart('%'.$key.'%')
                )
            )
        );
        return $rcnt->cnt;
    }

    private function quote_smart($value)
    {
        $value = sql_real_escape_string($value);
        if (! is_numeric($value)) {
            return "'" . $value . "'";
        }
        return $value;
    }

    // encodeURI for Nucleus redirect function
    public function encodeURI($url)
    {
        //  Nucleus redirect pattern  $url = preg_replace('|[^a-z0-9-~+_.?#=&;,/:@%*]|i', '', $url);
        // -~+_.?#=&;,/:@*
        return strtr(rawurlencode($url), array(
                '%23' => '#', '%26' => '&', '%2A' => '*', '%2B' => '+', '%2C' => ',',
                '%2D' => '-', '%2E' => '.', '%2F' => '/', '%3A' => ':', '%3B' => ';',
                '%3D' => '=', '%3F' => '?', '%40' => '@', '%5F' => '_', '%7E' => '~' ));
    }
}
