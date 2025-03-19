<div align="center">
    <a href="">
        <img
            alt="Lawtrace"
            src="https://github.com/user-attachments/assets/007b9a94-ffde-447b-89ac-301664534c54"
            width="300">
    </a>
</div>

# Lawtrace 立法歷程查詢

我們[透過研究](https://openfun.tw/ly-user-study/)發現，即使是倡議團體、媒體記者、立委助理、事實查核團體等經常查詢立法院資料的專業工作者，在檢索法案資料時，依舊需要耗費大量時間，跨越多個網站與開啟一長串的分頁，或是埋首在數百頁的文件中才能找到所需資訊。

對於不常使用立法院網站的一般民眾來說，想查法案資料更是困難重重。資料難以取得，背離立法院公開資料的初衷，公民因此難以關注修法進程，民主精神難以落實。

LawTrace 將資訊彙整在一處。找尋資料時，我們可以少開幾個分頁、更有效率，查找的更愉快！

## Quick start
```
php -S 0:8888 index.php
```

## Software stack
- Apache HTTP Server
- LYAPI (serve as data source. no DB required) [https://v2.ly.govapi.tw/](https://v2.ly.govapi.tw/)
- mini-engine (a PHP backend framework powered by OpenFun Ltd.) [https://github.com/openfunltd/mini-engine](https://github.com/openfunltd/mini-engine)

For details see: [SBOM.spdx](https://github.com/openfunltd/lawtrace/blob/main/SBOM.spdx)

## License
**BSD 3-Clause** 

Copyright (c) <2025> <OpenFun Ltd., supported by WFD>. 

For details see txt in repo: [LICENSE-BSD-3-Clause.txt](https://github.com/openfunltd/lawtrace/blob/main/LICENSE-BSD-3-Clause.txt), [LawTrace-NOTICE.txt](https://github.com/openfunltd/lawtrace/blob/main/LawTrace-NOTICE.txt)
