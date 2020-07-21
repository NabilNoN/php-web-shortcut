<?php
///
/// PHP SHORTCUT SERVICES
///
require_once 'config.php';
require_once 'init.php';
require_once 'utils.php';

function initShortcut($uri){
    $count=count($uri);
    $u1 = $uri[0];//shortcut|id
    try {
        $sql=initDatabase();

        //chick is id or shortcut
        if(strstart(strtolower($u1),"id")){
            $uid=substr($u1,2);
            if(is_numeric($uid))$u1=$uid;
        }

        //find url
        $url = $sql->where((is_numeric($u1)?'id':'shortcut'), $u1)->and_where('enabled','Y')->get('urls');
        if($sql->num_rows()===1){
            //add logs
            $counter=intval($url['counter'])+1;
            $sql->where('id', $url['id'])->update("urls",['counter'=>$counter,'last_visit'=>date(FORMAT)]);

            //goto original url
            header("Location: ".$url['original']);
        }else indexShortcutUrlPage("<span class='block error'>$u1($count) - 400-not-available-shortcut</span>");
    } catch (Exception $e) {
        $ec=$e->getCode();
        indexShortcutUrlPage("<span class='block error'>$u1 - $ec-exception-init-shortcut</span>");
    }
}

function newShortcutUrl($uri){
    try {
        $sql=initDatabase();

        $owner=$uri[1];
        $original="";
        for ($i=2;$i<count($uri);$i++){
            $original.=$uri[$i].($i===(count($uri)-1)?"":"/");
        }

        generate:
        $info=[
            "owner"=>$owner,
            "original"=>$original,
            "shortcut"=>generateRandomString(rand(3,6)),
            "date"=>date(FORMAT),
        ];
        $sql->where('shortcut', $info['shortcut'])->get('urls');
        if($sql->num_rows()>0)goto generate;
        $sql->insert('urls',$info);
        $id=$sql->insert_id();
        if($id>0){
            $info['id']=$id;
            showResult($info);
        }else indexShortcutUrlPage("<span class='block error'>$id - exception-insert-new-shortcut</span>");
    } catch (Exception $e) {
        $ec=$e->getCode();
        indexShortcutUrlPage("<span class='block error'>$ec - exception-new-shortcut</span>");
    }
}

function editShortcut($uri){
    $count=count($uri);
    try {
        $sql = initDatabase();
        $u1=$uri[1];//shortcut
        $owner=$uri[2];//newOwner
        $original="";//newOriginal optional

        if($count>3)for ($i=3;$i<$count;$i++){//build url
            $original.=$uri[$i].($i===($count-1)?"":"/");
        }

        //chick is id or shortcut
        if(strstart(strtolower($u1),"id")){
            $uid=substr($u1,2);
            if(is_numeric($uid))$u1=$uid;
        }

        //find url
        $url = $sql->where((is_numeric($u1)?'id':'shortcut'), $u1)->and_where('enabled','Y')->get('urls');
        if($sql->num_rows()===1){
            //edit shortcut
            $original=(empty($original)?$url['original']:$original);
            $date=date(FORMAT);
            $sql->where('id', $url['id'])->update("urls",[
                    'owner'=>$owner,
                    'original'=>$original,
                    'last_visit'=>$date,
                ]);
            $url['owner']=$owner;
            $url['original']=$original;
            $url['last_visit']=$date;

            showResult($url);
        }else indexShortcutUrlPage("<span class='block error'>$u1($count) - 400-not-available-shortcut</span>");
    } catch (Exception $e) {
        $ec=$e->getCode();
        indexShortcutUrlPage("<span class='block error'>$ec - exception-edit-shortcut</span>");
    }
}

function sortShortcut($uri){
    $count=count($uri);
    $u1=$uri[0];//get
    $u2=$uri[1];//shortcut|sortAll

    //chick is id or shortcut
    if(strstart(strtolower($u2),"id")){
        $uid=substr($u2,2);
        if(is_numeric($uid))$u2=$uid;
    }

    try {
        $sql=initDatabase();

        $urls = [];
        if ($u2 === "sortAll") $urls = $sql->order_by('date')->and_where('enabled','Y')->get('urls');
        else $urls = $sql->where((is_numeric($u2) ? 'id' : 'shortcut'), $u2)->and_where('enabled','Y')->get('urls');
        if($sql->num_rows()>0){
            $sql->forceArray($urls);
            $prefix = "https://" . $_SERVER['HTTP_HOST'] . "/";
            $html="<table class='tbl'>";
            $html.="<thead><tr><th>NUM.</th><th>Owner</th><th>Shortcut</th><th>Official Url</th><th>Shortcut Url</th><th>&#x1F5FA;</th></tr></thead><tbody>";
            $length = count($urls);
            for ($i=0; $i< $length; $i++){
                $url=$urls[$i];
                $id=$url['id'];
                $owner=$url['owner'];
                $shortcut=$url['shortcut'];
                $officialUrl=$url['original'];
                $shortcutUrl=$prefix .$url['shortcut'];
                $link="<a href='$shortcutUrl' target='_blank'>&#x1F30D; OPEN</a>";

                $html.="<tr>";
                $html.="<td>$id</td>";
                $html.="<td>$owner</td>";
                $html.="<td>$shortcut</td>";
                $html.="<td><a target='_blank' href='$officialUrl'>$officialUrl</a></td>";
                $html.="<td><a target='_blank' href='$shortcutUrl'>$shortcutUrl</a></td>";
                $html.="<td>$link</td>";
                $html.="</tr>";
            }
            $html.="</tbody></table>";
            indexShortcutUrlPage($html);
        }else indexShortcutUrlPage("<span class='block error'>$u2($count) - 400-not-available-shortcut</span>");
    } catch (Exception $e) {
        $ec=$e->getCode();
        indexShortcutUrlPage("<span class='block error'>\{$u1, $u2\} - $ec-exception-sort-shortcut</span>");
    }
}

function deleteShortcut($uri){
    $count=count($uri);
    try {
        $sql = initDatabase();
        $u1=$uri[1];//shortcut

        //chick is id or shortcut
        if(strstart(strtolower($u1),"id")){
            $uid=substr($u1,2);
            if(is_numeric($uid))$u1=$uid;
        }

        //find url
        $url = $sql->where((is_numeric($u1)?'id':'shortcut'), $u1)/*->and_where('enabled','Y')*/->get('urls');
        if($sql->num_rows()===1){
            if($url['enabled']==='N'){
                indexShortcutUrlPage("<span class='block error'>Already Deleted!</span>");
                return;
            }

            //delete shortcut
            $sql->where('id', $url['id'])->update("urls",[
                    'enabled'=>'N',
                ]);
            $url['enabled']='N';

            indexShortcutUrlPage("<span class='block success'>Deleted Successful!</span>");
        }else indexShortcutUrlPage("<span class='block error'>$u1($count) - 400-not-available-shortcut</span>");
    } catch (Exception $e) {
        $ec=$e->getCode();
        indexShortcutUrlPage("<span class='block error'>$ec - exception-delete-shortcut</span>");
    }
}

function main(){
    $url = $_SERVER['REQUEST_URI'];
    $uri=explode("/",trim($url,"/"));

    if(count($uri)>0 && !empty($uri[0])){
        if($uri[0]==="new" && count($uri)>2){
            //https://facce.app/new/owner/https://original.url
            newShortcutUrl($uri);
        }else if($uri[0]==="edit" && count($uri)>2){
            //https://facce.app/edit/shortcut/newOwner[/https://neworiginal.url]
            editShortcut($uri);
        }else if($uri[0]==="get" && count($uri)>1){
            //https://facce.app/get/[shortcut|sortAll]
            sortShortcut($uri);
        }else if($uri[0]==="delete" && count($uri)>1){
            //https://facce.app/delete/shortcut
            deleteShortcut($uri);
        }else initShortcut($uri);
    }else{
        indexShortcutUrlPage();
    }
}

function showResult($info){
    $prefix = "https://" . $_SERVER['HTTP_HOST'] . "/";
    $id=$info['id'];
    $html="<table class='tbl'><caption class='success'>Successful!</caption>";
    $html.="<thead><tr><th>Key</th><th>Value</th></tr></thead><tbody>";
    $owner=$info['owner'];
    $shortcutUrl1=$prefix .$info['shortcut'];
    $shortcutUrl2=$prefix .'id'.$id;
    $shortcutUrl3=$prefix .$id;
    $more=$prefix."get/".$info['shortcut'];
    $link="<a href='$more' target='_blank'><b>&ctdot;</b> MORE</a>";

    $html.="<tr>";
    $html.="<th>NUM.</th>"."<td>$id</td>";
    $html.="</tr>";

    $html.="<tr>";
    $html.="<th>Owner</th>"."<td>$owner</td>";
    $html.="</tr>";

    $html.="<tr>";
    $html.="<th>Shortcut Url 1</th>"."<td><a target='_blank' href='$shortcutUrl1'>$shortcutUrl1</a></td>";
    $html.="</tr>";

    $html.="<tr>";
    $html.="<th>Shortcut Url 2</th>"."<td><a target='_blank' href='$shortcutUrl2'>$shortcutUrl2</a></td>";
    $html.="</tr>";

    $html.="<tr>";
    $html.="<th>Shortcut Url 3</th>"."<td><a target='_blank' href='$shortcutUrl3'>$shortcutUrl3</a></td>";
    $html.="</tr>";

    $html.="<tr>";
    $html.="<th>&ImaginaryI;</th>"."<td>$link</td>";
    $html.="</tr>";

    $html.="</tbody></table>";
    indexShortcutUrlPage($html);
}

function indexShortcutUrlPage($content=""){
    ?>
    <!doctype html>
    <html dir="ltr" lang="">
    <head>
        <meta charset="utf-8">
        <title>Shortcut Urls - اختصار الروابط</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            * {
                margin: 0;
                padding: 0;
            }

            body {
                text-align: center;
                max-width: 760px;
                margin: auto;
            }

            div.header {
                padding: 20px;
                background-color: #00b7e8;
                color: #ffffff;
            }

            div.body {
                padding: 20px;
                background-color: aliceblue;
            }

            div.footer {
                background-color: gray;
                padding: 20px;
                font-size: small;
                color: #f3f3f3;
            }

            ul.functions {
                display: block;
                list-style: none;
            }

            ul.functions li {
                display: block;
                border: 1px solid gray;
                padding: 10px 5px;
                margin: 5px 0;
                border-radius: 8px;
                box-shadow: inset lavender 0 0 2px 2px;
            }

            ul.functions li b {
                background-color: lavender;
                box-shadow: gray 0 0 1px 1px;
                margin: 0 4px;
                border-radius: 2px;
                padding: 3px 10px;
                cursor: default;
                transition: all 200ms ease-in-out;
            }

            ul.functions li b:hover {
                box-shadow: gray 0 0 2px 1px;
            }

            table.tbl {
                display: block;
                border-radius: 4px;
                box-shadow: 0 0 2px 2px gray;
                margin: 5px auto;
                overflow: hidden;
                overflow-x: auto;
            }

            table.tbl tr:nth-child(even) {
                background-color: lavender;
            }

            table.tbl tr:nth-child(odd) {
                background-color: aliceblue;
            }

            table.tbl tr {
            }

            table.tbl tr th {
                font-weight: bold;
                background-color: darkcyan;
                color: #ffffff;
            }

            table.tbl tr td {
                width: 100%;
            }

            table.tbl tr td:first-child {
                width: auto;
            }

            table.tbl tbody tr th:first-child {
                min-width: 120px;
            }

            table.tbl, table.tbl tr th, table.tbl tr td {
                border: 1px solid black;
                border-collapse: collapse;
            }

            table.tbl tr th, table.tbl tr td {
                text-align: center;
                padding: 10px 5px;
                cursor: default;
            }

            table.tbl tr td a {
                text-decoration: none;
                font-style: italic;
                cursor: pointer;
                transition: all 200ms ease-in-out;
            }

            table.tbl tr a:hover {
                text-decoration: underline;
            }

            table.tbl caption.success {
                background-color: #009900;
                color: #ffffff;
                padding: 10px 5px;
            }

            /*classes*/
            .block {
                display: block !important;
            }

            .error {
                font-weight: bold;
                color: #f00;
                text-outline: gray 0 0;
                padding: 5px;
            }

            .success {
                font-weight: bold;
                background-color: #009900;
                padding: 5px;
                color: #ffffff;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 0 0 1px gray;
            }
        </style>
    </head>
    <body>
    <div class="header"><h3>Shortcut Urls - اختصار الروابط</h3></div>
    <hr/>
    <div class="body">
        <?php if (!empty($content)) echo $content; ?>
        <ul class="functions">
            <li><b>new</b>&dash; https://<?php echo $_SERVER['HTTP_HOST']; ?>/new/owner/https://original.url</li>
            <li><b>edit</b>&dash; https://<?php echo $_SERVER['HTTP_HOST']; ?>/edit/shortcut/newOwner[/https://neworiginal.url]</li>
            <li><b>get</b>&dash; https://<?php echo $_SERVER['HTTP_HOST']; ?>/get/[shortcut|sortAll]</li>
            <li><b>delete</b>&dash; https://<?php echo $_SERVER['HTTP_HOST']; ?>/delete/shortcut</li>
        </ul>
    </div>
    <hr/>
    <div class="footer">nabil.jaran@gmail.com &copy;<?php echo date('Y'); ?></div>
    </body>
    </html>
    <?php
}

main();
