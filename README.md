# NP_LinkCounter

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.
(see nucleus/documentation/index.html#license for more info)

## USAGE

In item:

```
<%media(file path|link text)%> //auto count mode
<%media(file path|link text|linkcnt=KEYWORD)%> //set original keyword
<%LinkCounter(link,KEYWORD,URL,linktext,target prop,title prop)%>
<%LinkCounter(total,KEYWORD)%>
```
Others:
```
<%LinkCounter(link,KEYWORD,URL,linktext,target prop,title prop)%>
<%LinkCounter(total,KEYWORD)%>
```


## HISTORY

- 2008/05/14	Ver 0.4  :
  - [Chg] Unsupport <a linkcnt="*keyword*"> and <#linkcnt_total(*keyword*)#>.
  - [Add] Support `<%LinkCounter()%>` in item.
  - [Add] Option "Keyword of feeds skin name" (exkey).
- 2006/11/21	Ver 0.32 : [Fix] Security fix. 
- 2006/09/30	Ver 0.31 : [Fix] Security fix. 
- 2004/08/12	Ver 0.3  : [Chg] Shorten linkcountURL, and [Add] Auto count mode for media tag. 
- 2004/04/14	Ver 0.2  : [Add] skin description, and total count. 
- 2004/02/16	Ver 0.1  : First release. 
