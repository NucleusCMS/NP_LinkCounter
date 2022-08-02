<?php
class NP_LinkCounter extends NucleusPlugin {
    function getName()      { return 'Link Counter'; }
    function getAuthor()    { return 'yu'; }
    function getURL()       { return 'http://works.datoka.jp/'; }
    function getVersion()   { return '0.5'; }
    function getMinNucleusVersion() { return 200; }
    function getTableList() { return array( sql_table('plug_linkcounter') ); }
    function getEventList() { return array( 'PreItem' ); }
    function supportsFeature($what) {
        if ($what !== 'SqlTablePrefix') {
            return 0;
        }
        return 1;
    }

    function getDescription() {
        return 'Link counter. [USAGE] mediavar - <%media(file|text|linkcnt=keyword)%> '.
            'or itemvar - <%LinkCounter(link,url,linktext,target,title)%> '.
            'or <%LinkCounter(total,keyword)%>';
    }

    function install(){
        sql_query ("CREATE TABLE IF NOT EXISTS ". sql_table('plug_linkcounter') ." (
            lkey VARCHAR(64)  NOT NULL,
            cnt  INT UNSIGNED NOT NULL DEFAULT 1,
            url  VARCHAR(255) NOT NULL DEFAULT '',
            primary key (lkey))");

        $this->createOption(
            'tpl_cnt',
            'Counter Template.',
            'text',
            '[$cnt$word]'
        );
        $this->createOption(
            'tpl_word1',
            'Unit word for template (singlar form).',
            'text',
            'click'
        );
        $this->createOption(
            'tpl_word2',
            'Unit word for template (plural form).',
            'text',
            'clicks'
        );
        $this->createOption(
            'flg_auto',
            'Auto count mode for media tag (no need to add "linkcnt" property).',
            'yesno',
            'yes'
        );
        $this->createOption(
            'exkey',
            'Keyword of feeds skin name (invalidate showing counter for XML syndication).',
            'text',
            'feeds'
        );
        $this->createOption(
            'flg_erase',
            'Erase data on uninstall.',
            'yesno',
            'no'
        );
    }

    function unInstall() {
        if ($this->getOption('flg_erase') !== 'yes') {
            return;
        }
        sql_query('DROP TABLE ' . sql_table('plug_linkcounter'));
    }

    function init() {
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
        $res = sql_query($query);
        while ($link = sql_fetch_object($res)) { //copy all data
            $this->link[$link->lkey]['cnt'] = (int)$link->cnt;
            $this->link[$link->lkey]['url'] = stripslashes($link->url);
        }
    }

    function doSkinVar($skinType, $mode='total', $key='', $url='', $linktext='', $target='', $title='') {
        if ($mode === 'link' && $key) {
            echo $this->_make_link($key, $url, $linktext, $target, $title);
            echo $this->_make_counter($this->link[$key]['cnt']);
            return;
        }
        if ($mode !== 'total' || !$key) {
            return;
        }
        echo $this->_make_counter($this->_get_total($key));
    }

    function doItemVar(&$item, $mode='total', $key='', $url='', $linktext='', $target='', $title='') {
        $this->doSkinVar('', $mode, $key, $url, $linktext, $target, $title);
    }

    function doTemplateVar(&$item, $mode='total', $key='', $url='', $linktext='', $target='', $title='') {
        $this->doSkinVar('', $mode, $key, $url, $linktext, $target, $title);
    }

    function event_PreItem($data) {
        // prepare
        $tgt  = '/<%media\((.+?)\)%>/';

        // convert to linkcounter
        $obj = &$data["item"];
        $this->authorid = $obj->authorid;
        $obj->body = preg_replace_callback($tgt, array(&$this, 'makelink_callback'), $obj->body);
        $obj->more = preg_replace_callback($tgt, array(&$this, 'makelink_callback'), $obj->more);
    }

    function doAction($type) {
        if ($type !== 'c') {
            redirect(serverVar('HTTP_REFERER'));
            return;
        }

        $key = urldecode(getVar('k'));
        $url = getVar('url');

        if (empty($this->link[$key]['cnt'])) {
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

    function makelink_callback($m) {
        global $CONF;

        $mcnt = count($m);

        if ($mcnt != 2) {
            return $m[0];
        }

        // media var
        $mvar = explode('|', $m[1]);
        if (!$mvar[2]) { // no extra property
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

        return $this->_make_link(
                $key,
                $CONF['MediaURL'] . $memberdir . $mvar[0],
                $linktext,
                $tgt,
                $tit
            )
            . $this->_make_counter($this->link[$key]['cnt']);
    }

    //helper function
    function _make_link($key, $url, $linktext, $tgt, $tit) {
        global $CONF;

        $saved_url = $this->link[$key]['url'];
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

    function _make_counter($cnt) {
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

    function _get_total($key) {
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

    function quote_smart($value) {
        $value = sql_real_escape_string($value);
        if (! is_numeric($value)) {
            return "'" . $value . "'";
        }
        return $value;
    }

    function encodeURI($url) {
        //  A-Z a-z 0-9 ; , / ? : @ & = + $ - _ . ! ~ * ' ( ) #
        // ;,/?:@&=+$-_.!~*'()#
        return strtr(rawurlencode($url), array(
                '%21' => '!', '%23' => '#', '%24' => '$', '%26' => '&', '%27' => "'",
                '%28' => '(', '%29' => ')', '%2A' => '*', '%2B' => '+', '%2C' => ',',
                '%2D' => '-', '%2E' => '.', '%2F' => '/', '%3A' => ':', '%3B' => ';',
                '%3D' => '=', '%3F' => '?', '%40' => '@', '%5F' => '_', '%7E' => '~' ));        
    }

}
