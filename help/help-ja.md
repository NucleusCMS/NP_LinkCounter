##NP\_LinkCounter.php

リンクのクリック数を表示するタイプのカウンターです。 外部リンクへの参照数や、ファイルのダウンロード数をチェックできます。

このプラグインの使い方
----------------------

-   アイテムに記述されたメディアファイルのダウンロード数をカウントします。

-   アイテムに特殊なAタグを記述して、リンクのクリック数をカウントします。

-   スキンにプラグイン変数または特殊なAタグを記述して、リンクのクリック数をカウントします。

-   集計カウント数のみを表示できます。


アイテムへの記述
----------------

```
<%LinkCounter(link,KEYWORD,URL,linktext,target prop,title prop)%>
<%LinkCounter(total,KEYWORD)%>
```


```
<%media(file path|link text)%> //auto count mode
<%media(file path|link text|linkcnt=KEYWORD)%> //set original keyword
```

-   メディアの挿入時（&lt;%media(…)%&gt;）に、自動的にリンクカウンターとして機能します（ファイル名がキーワードになります）。

-   メディアの挿入時に、`<%media(ファイル名|リンクテキスト|linkcnt=キーワード)%>` と書くことで手動でキーワードをつけられます。

-   または次のようにAタグを手書きすることもできます。target属性やtitle属性も記述できます（順番は固定）。実際にブラウザに表示される際はlinkcnt属性が消され、hrefがリンクカウンターURLに置換されます。

``` code
<a href="http://リンク先（絶対URL）" linkcnt="キーワード">リンクテキスト</a>
<a href="http://リンク先（絶対URL）" linkcnt="キーワード" target="_blank" title="タイトル">リンクテキスト</a>
```

スキン/テンプレートへの記述
----------------

```
<%LinkCounter(link,KEYWORD,URL,linktext,target prop,title prop)%>
<%LinkCounter(total,KEYWORD)%>
```

-   アイテム内に `<#linkcnt_total(キーワード)#>` と書くことで、キーワードを元にした集計カウントを表示できます。


-   スキンに以下のように記述できます。モードは"link"、"total"のいずれか。必要のないパラメーターは省略できます。

-   totalについては「キーワードでカウント集計」と同じです。指定パラメーターはモード、キーワードの二つだけでOKです。

``` code
<%LinkCounter(モード,キーワード,URL,リンクテキスト,target属性,title属性)%>
```

-   アイテム記述と同様にAタグを手書きすることで、プラグインがリンクカウンター用URLに変換してくれます。

``` code
<a href="http://リンク先（絶対URL）" linkcnt="キーワード">リンクテキスト</a>
<a href="http://リンク先（絶対URL）" linkcnt="キーワード" target="_blank" title="タイトル">リンクテキスト</a>
```

オプション
----------

-   カウンター表示用テンプレート

-   単数形表示、複数形表示のテンプレート

-   メディアタグ（&lt;%media()%&gt;）の自動カウンター化（デフォルトはオン）

-   アンインストール時にデータを消去するか

Tipsと裏技
----------

-   リンクカウンターURLは、一度クリックされたあとは短縮化されます（URL部が省略される）。

-   キーワードに日本語を指定すると、URLエンコードされて見づらい＆URLが長くなりがちなことに注意。


wiki
----------

- http://japan.nucleuscms.org/wiki/plugins:linkcounter


開発履歴
--------

- Ver 0.5  : 詳細は、git コミットログを参照。

- 2008/05/14	Ver 0.4  :
  - [Chg] Unsupport <a linkcnt="*keyword*"> and <#linkcnt_total(*keyword*)#>.
  - [Add] Support `<%LinkCounter()%>` in item.
  - [Add] Option "Keyword of feeds skin name" (exkey).

-   2006/11/21 Ver 0.32 : セキュリティフィックス

-   2006/09/30 Ver 0.31 : セキュリティフィックス

-   2004/08/12 Ver 0.3 : メディアタグに対する自動カウンター化、リンクカウンターURLの短縮化

-   2004/04/14 Ver 0.2 : スキン記述対応、カウント集計の追加

-   2004/02/16 Ver 0.1 : 初期リリース

